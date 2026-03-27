<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
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
        }

        return $out;
    }

    /**
     * @return array<int, array{product: Product, quantity: int, unit_price: float, is_wholesale: bool, subtotal: float, line_key: string, product_variation_id: int|null, variation: ProductVariation|null}>
     */
    public static function enrichCartItems(array $cart): array
    {
        $cart = self::normalizeCart($cart);
        $items = [];

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

            $adjustment = $variation ? (float) $variation->price_adjustment : 0.0;
            $isWholesale = $product->hasWholesalePricing() && $quantity >= (int) $product->wholesale_min_qty;
            $base = $isWholesale ? (float) $product->wholesale_price : (float) $product->base_price;
            $unitPrice = round($base + $adjustment, 2);

            $items[] = [
                'line_key' => $lineKey,
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'is_wholesale' => $isWholesale,
                'subtotal' => $unitPrice * $quantity,
                'product_variation_id' => $variationId,
                'variation' => $variation,
                'variation_label' => $variation ? $variation->display_label : null,
            ];
        }

        return $items;
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
        }

        return $out;
    }
}
