<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductCatalogSyncService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function syncImagesFromUploads(Product $product, array $files): void
    {
        $startOrder = (int) $product->images()->max('sort_order');
        $i = 0;
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => false,
                'sort_order' => $startOrder + $i + 1,
            ]);
            $i++;
        }

        $this->refreshPrimaryImagePath($product->fresh(['images']));
    }

    public function refreshPrimaryImagePath(Product $product): void
    {
        $images = $product->images()->orderBy('sort_order')->get();
        if ($images->isEmpty()) {
            $product->update(['image_path' => null]);

            return;
        }

        $images->each(function ($img, $idx) {
            $img->update([
                'is_primary' => $idx === 0,
                'sort_order' => $idx,
            ]);
        });

        $primary = $images->firstWhere('is_primary', true) ?? $images->first();
        $product->update(['image_path' => $primary->image_path]);
    }

    /**
     * Set which gallery image is primary (by product_images.id).
     */
    public function setPrimaryImage(Product $product, int $imageId): void
    {
        $images = $product->images()->orderBy('sort_order')->get();
        $target = $images->firstWhere('id', $imageId);
        if (! $target) {
            return;
        }

        $ordered = $images->filter(fn ($img) => $img->id !== $imageId)->values();
        $newOrder = collect([$target])->merge($ordered);

        $newOrder->values()->each(function ($img, $idx) {
            $img->update([
                'is_primary' => $idx === 0,
                'sort_order' => $idx,
            ]);
        });

        $this->refreshPrimaryImagePath($product->fresh(['images']));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncVariations(Product $product, array $rows): void
    {
        $incomingIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $toRemove = ProductVariation::where('product_id', $product->id)
            ->whereNotIn('id', $incomingIds)
            ->get();

        foreach ($toRemove as $v) {
            $invRows = $v->inventory()->get();
            $totalQty = (int) $invRows->sum('quantity');
            $totalReserved = (int) $invRows->sum('reserved_quantity');
            if ($totalQty > 0 || $totalReserved > 0) {
                throw new \RuntimeException(
                    'Cannot remove option "'.$v->display_label.'" while it still has on-hand or reserved stock. Complete or cancel related orders first.'
                );
            }
            foreach ($invRows as $invRow) {
                $invRow->delete();
            }
            $v->delete();
        }

        foreach ($rows as $i => $row) {
            $combination = $row['combination'] ?? null;

            if (is_array($combination) && ! empty($combination)) {
                $groupNames = implode(' / ', array_keys($combination));
                $groupValues = implode(' / ', array_values($combination));
            } else {
                $groupNames = $row['option_name'] ?? '';
                $groupValues = $row['option_value'] ?? '';
                $combination = null;
            }

            $payload = [
                'option_name' => $groupNames,
                'option_value' => $groupValues,
                'combination' => $combination,
                'price_adjustment' => $row['price_adjustment'] ?? 0,
                'sku' => $row['sku'] ?? null,
                'sort_order' => $i,
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            ];

            if (! empty($row['id'])) {
                $existing = ProductVariation::where('product_id', $product->id)
                    ->where('id', $row['id'])
                    ->first();
                if ($existing) {
                    $existing->update($payload);

                    continue;
                }
            }

            ProductVariation::create(array_merge($payload, [
                'product_id' => $product->id,
            ]));
        }
    }

    /**
     * Ensure one inventory row per variation with optional initial qty; remove orphan base row when variations exist.
     *
     * @param  array<int, int|string|null>  $stockByVariationIndex  Same order as variations (sort_order)
     */
    /**
     * @return list<string> Human-readable notices (e.g. stock raised to cover reserved units).
     */
    public function syncVariationInventory(Product $product, array $stockByVariationIndex, int $defaultReorderLevel = 10): array
    {
        $warnings = [];
        $variations = $product->variations()->orderBy('sort_order')->get();

        if ($variations->isEmpty()) {
            return $warnings;
        }

        // If the product already has reserved base inventory (legacy pending orders placed
        // before variations existed), we cannot delete that base row. We keep it to avoid
        // breaking pending reservations, and stockTotals() includes the base row only when
        // there is reserved quantity there.
        foreach (Inventory::where('product_id', $product->id)->whereNull('product_variation_id')->get() as $row) {
            if ($row->reserved_quantity > 0) {
                $warnings[] = sprintf(
                    'Base stock kept (reserved on open orders: %d). Variations were added/updated successfully.',
                    (int) $row->reserved_quantity
                );

                continue;
            }

            $row->delete();
        }

        foreach ($variations as $idx => $variation) {
            $qty = isset($stockByVariationIndex[$idx]) ? max(0, (int) $stockByVariationIndex[$idx]) : 0;

            $inv = Inventory::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'product_variation_id' => $variation->id,
                    'branch_id' => null,
                ],
                [
                    'quantity' => 0,
                    'reorder_level' => $defaultReorderLevel,
                    'reserved_quantity' => 0,
                ]
            );

            $reservedTotal = (int) Inventory::where('product_id', $product->id)
                ->where('product_variation_id', $variation->id)
                ->sum('reserved_quantity');

            if ($qty < $reservedTotal) {
                $warnings[] = sprintf(
                    '%s — %s: on-hand set to %d (cannot go below %d reserved on open orders).',
                    $variation->option_name,
                    $variation->option_value,
                    $reservedTotal,
                    $reservedTotal
                );
                $qty = $reservedTotal;
            }

            $inv->update(['quantity' => $qty, 'reorder_level' => $defaultReorderLevel]);
        }

        return $warnings;
    }

    /**
     * Single-SKU inventory row (no variations).
     */
    /**
     * @return list<string>
     */
    public function setBaseInventory(Product $product, int $quantity, int $reorderLevel): array
    {
        $warnings = [];

        foreach (Inventory::where('product_id', $product->id)->whereNotNull('product_variation_id')->get() as $row) {
            if ($row->reserved_quantity > 0) {
                throw new \RuntimeException(
                    'Cannot remove variations while an option still has reserved stock.'
                );
            }
            $row->delete();
        }

        $inv = Inventory::firstOrCreate(
            [
                'product_id' => $product->id,
                'product_variation_id' => null,
                'branch_id' => null,
            ],
            [
                'quantity' => 0,
                'reorder_level' => $reorderLevel,
                'reserved_quantity' => 0,
            ]
        );

        $reservedTotal = (int) Inventory::where('product_id', $product->id)
            ->whereNull('product_variation_id')
            ->sum('reserved_quantity');

        if ($quantity < $reservedTotal) {
            $warnings[] = sprintf(
                'Base stock was set to %d (minimum while %d unit(s) are reserved for pending orders).',
                $reservedTotal,
                $reservedTotal
            );
            $quantity = $reservedTotal;
        }

        $inv->update([
            'quantity' => $quantity,
            'reorder_level' => $reorderLevel,
        ]);

        return $warnings;
    }

    public function removeGalleryImages(Product $product, array $imageIds): void
    {
        foreach ($imageIds as $imageId) {
            $img = ProductImage::where('product_id', $product->id)->where('id', $imageId)->first();
            if ($img) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
            }
        }

        $this->refreshPrimaryImagePath($product->fresh(['images']));
    }
}
