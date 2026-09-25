<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use InvalidArgumentException;

class CartService
{
    /**
     * Build a stable cart line key.
     */
    public static function lineKey(int $productId, ?int $variationId = null): string
    {
        return $variationId ? "p{$productId}_v{$variationId}" : "p{$productId}";
    }

    /**
     * Parse normalized line key into [productId, variationId|null].
     */
    public static function parseLineKey(string $key): array
    {
        if (preg_match('/^p(\d+)_v(\d+)$/', $key, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        if (preg_match('/^p(\d+)$/', $key, $m)) {
            return [(int) $m[1], null];
        }
        if (ctype_digit($key)) {
            return [(int) $key, null];
        }

        throw new InvalidArgumentException('Invalid cart line key: ' . $key);
    }

    /**
     * Migrate legacy cart keys (numeric product id) to p{id} format and ensure structure.
     *
     * @param  array<string, array>  $cart
     * @return array<string, array{product_id: int, product_variation_id: int|null, quantity: int}>
     */
    public static function normalizeCart(array $cart): array
    {
        $out = [];

        foreach ($cart as $key => $item) {
            if (! is_array($item) || ! isset($item['quantity'])) {
                continue;
            }

            $productId = $item['product_id'] ?? null;
            $variationId = $item['product_variation_id'] ?? null;

            if ($productId === null) {
                if (is_numeric($key)) {
                    $productId = (int) $key;
                } else {
                    continue;
                }
            }

            $productId = (int) $productId;
            $variationId = $variationId ? (int) $variationId : null;

            $lineKey = self::lineKey($productId, $variationId);

            $out[$lineKey] = [
                'product_id' => $productId,
                'product_variation_id' => $variationId,
                'quantity' => max(1, (int) $item['quantity']),
            ];

            if (isset($item['rfq_price'])) {
                $out[$lineKey]['rfq_price'] = $item['rfq_price'];
            }
            if (isset($item['rfq_message_id'])) {
                $out[$lineKey]['rfq_message_id'] = $item['rfq_message_id'];
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{product: Product, quantity: int, unit_price: float, is_wholesale: bool, subtotal: float, line_key: string, product_variation_id: int|null, variation: ProductVariation|null}>
     */
    public static function enrichCartItems(array $cart, ?User $user = null): array
    {
        $cart = self::normalizeCart($cart);
        $items = [];
        $prepared = [];

        foreach ($cart as $lineKey => $cartItem) {
            [$productId, $variationId] = self::parseLineKey($lineKey);

            $product = Product::with(['distributor', 'images', 'inventory', 'variations'])
                ->find($productId);

            if (! $product) {
                continue;
            }

            $variation = null;
            if ($variationId) {
                $variation = $product->variations->firstWhere('id', $variationId);
                if (! $variation || ! $variation->is_active) {
                    continue;
                }
            }

            $quantity = (int) $cartItem['quantity'];

            $hasVariations = $product->variations->where('is_active', true)->isNotEmpty();
            if ($hasVariations && ! $variationId) {
                continue;
            }

            $availableStock = self::availableStockForLine($product, $variationId);

            if ($availableStock <= 0) {
                continue;
            }

            $quantity = min($quantity, $availableStock);

            $rfqPrice = isset($cartItem['rfq_price']) ? (float) $cartItem['rfq_price'] : null;

            $prepared[] = [
                'lineKey' => $lineKey,
                'product' => $product,
                'variation' => $variation,
                'variationId' => $variationId,
                'quantity' => $quantity,
                'rfqPrice' => $rfqPrice,
                'rfqMessageId' => $cartItem['rfq_message_id'] ?? null,
            ];
        }

        // Wholesale pricing is ONLY available to B2B buyers (verified distributors, their staff,
        // and customers with an approved business profile).
        $canBuyWholesale = $user && $user->canAccessWholesale();

        // The wholesale threshold is in pieces and counts every line of the same product, so a
        // mix of loose pieces and boxes adds up. RFQ lines carry their own negotiated price and
        // don't count toward it.
        $piecesByProduct = self::piecesByProduct($prepared);

        foreach ($prepared as $line) {
            /** @var Product $product */
            $product = $line['product'];
            $variation = $line['variation'];
            $quantity = $line['quantity'];
            $rfqPrice = $line['rfqPrice'];

            $isWholesale = $canBuyWholesale
                && $rfqPrice === null
                && $product->wholesaleQualifies($piecesByProduct[$product->id] ?? 0);

            // RFQ negotiated price overrides everything else (no variation adjustment applies to custom quotes)
            $unitPrice = $rfqPrice !== null
                ? round($rfqPrice, 2)
                : $product->priceForLine($variation, $isWholesale);

            $packSize = $product->linePackSize($variation);

            $items[] = [
                'line_key'             => $line['lineKey'],
                'product'              => $product,
                'quantity'             => $quantity,
                'unit_price'           => $unitPrice,
                'is_wholesale'         => $isWholesale,
                'is_rfq'               => $rfqPrice !== null,
                'rfq_message_id'       => $line['rfqMessageId'],
                'subtotal'             => $unitPrice * $quantity,
                'product_variation_id' => $line['variationId'],
                'variation'            => $variation,
                'variation_label'      => $variation ? $variation->display_label : null,
                'retail_unit_price'    => $rfqPrice !== null ? $unitPrice : $product->priceForLine($variation, false),
                'units_per_pack'       => $packSize,
                'unit_label'           => $product->lineUnitLabel($variation),
                'pieces'               => $quantity * $packSize,
            ];
        }

        return $items;
    }

    /**
     * Total pieces per product across the given lines (quantity × pieces per selling unit).
     * RFQ lines are skipped: they are priced by negotiation, not by quantity thresholds.
     *
     * @param  iterable<array{product: Product, variation: ProductVariation|null, quantity: int, rfqPrice?: float|null}>  $lines
     * @return array<int, int>  product id => total pieces
     */
    public static function piecesByProduct(iterable $lines): array
    {
        $totals = [];

        foreach ($lines as $line) {
            if (($line['rfqPrice'] ?? null) !== null) {
                continue;
            }

            $product = $line['product'];
            $totals[$product->id] = ($totals[$product->id] ?? 0)
                + (int) $line['quantity'] * $product->linePackSize($line['variation'] ?? null);
        }

        return $totals;
    }

    public static function availableStockForLine(Product $product, ?int $variationId): int
    {
        $query = $product->inventory;

        $hasVariations = $product->relationLoaded('variations')
            ? $product->variations->where('is_active', true)->isNotEmpty()
            : $product->variations()->where('is_active', true)->exists();

        if ($hasVariations) {
            if (! $variationId) {
                return 0;
            }

            return (int) $query->where('product_variation_id', $variationId)->sum(function ($inv) {
                return $inv->quantity - $inv->reserved_quantity;
            });
        }

        if ($variationId) {
            return (int) $query->where('product_variation_id', $variationId)->sum(function ($inv) {
                return $inv->quantity - $inv->reserved_quantity;
            });
        }

        return (int) $query->filter(function ($inv) {
            return $inv->product_variation_id === null;
        })->sum(function ($inv) {
            return $inv->quantity - $inv->reserved_quantity;
        });
    }

    public static function calculateSubtotal(array $cartItems): float
    {
        return (float) array_sum(array_column($cartItems, 'subtotal'));
    }

    /**
     * Remove dead lines and clamp quantities to available stock.
     *
     * @param  array<string, mixed>  $cart
     * @return array<string, array{product_id: int, product_variation_id: int|null, quantity: int}>
     */
    public static function pruneCart(array $cart): array
    {
        $cart = self::normalizeCart($cart);
        $out = [];

        foreach ($cart as $lineKey => $item) {
            try {
                [$pid, $vid] = self::parseLineKey($lineKey);
            } catch (\Throwable $e) {
                continue;
            }

            $product = Product::with(['inventory', 'variations'])->find($pid);
            if (! $product) {
                continue;
            }

            $avail = self::availableStockForLine($product, $vid);
            if ($avail <= 0) {
                continue;
            }

            $qty = min((int) $item['quantity'], $avail);
            $out[$lineKey] = [
                'product_id' => $pid,
                'product_variation_id' => $vid,
                'quantity' => $qty,
            ];

            if (isset($item['rfq_price'])) {
                $out[$lineKey]['rfq_price'] = $item['rfq_price'];
            }
            if (isset($item['rfq_message_id'])) {
                $out[$lineKey]['rfq_message_id'] = $item['rfq_message_id'];
            }
        }

        return $out;
    }
}
