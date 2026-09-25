<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'distributor_id',
        'category_id',
        'name',
        'sku',
        'slug',
        'description',
        'brand',
        'model',
        'base_price',
        'wholesale_price',
        'wholesale_min_qty',
        'units_per_pack',
        'unit_label',
        'product_type',
        'has_expiry',
        'has_warranty',
        'warranty_months',
        'image_path',
        'barcode',
        'is_active',
        'is_featured',
        'variation_options',
        'requires_prescription',
        'vehicle_requirement',
        'is_vat_exempt',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'has_expiry' => 'boolean',
        'has_warranty' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'variation_options' => 'array',
        'requires_prescription' => 'boolean',
        'is_vat_exempt' => 'boolean',
    ];

    protected $appends = ['image_url', 'units_sold', 'reviews_avg_stars'];

    /**
     * Ensure reviews_avg_stars is always numeric and rounded.
     */
    public function getReviewsAvgStarsAttribute($value): float
    {
        return $value ? round((float) $value, 1) : 0.0;
    }

    /**
     * Get the distributor that owns this product
     */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get product images
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Optional SKU-level options (e.g. Color: Blue).
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->orderBy('sort_order');
    }

    public function activeVariations(): HasMany
    {
        return $this->variations()->where('is_active', true);
    }

    /**
     * Get the primary image
     */
    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get inventory records
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get order items
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get total units sold for completed/delivered orders.
     */
    public function getUnitsSoldAttribute(): int
    {
        // Use eager loaded sum if available, or calculate it
        if ($this->relationLoaded('orderItems')) {
            return (int) $this->orderItems
                ->filter(fn($item) => $item->order && in_array($item->order->status, ['completed', 'delivered']))
                ->sum('quantity');
        }

        return (int) \DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $this->id)
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->sum('quantity');
    }

    /**
     * Check if wholesale pricing is available
     */
    public function hasWholesalePricing(): bool
    {
        return ! is_null($this->wholesale_price) && ! is_null($this->wholesale_min_qty);
    }

    /**
     * Pieces contained in one selling unit of this product (1 = sold by the piece).
     */
    public function packSize(): int
    {
        return max(1, (int) ($this->units_per_pack ?? 1));
    }

    /**
     * Pieces in one selling unit of the given variation. A variation may override the product's
     * pack size (e.g. a "Box of 10" option); otherwise it inherits the product's.
     */
    public function linePackSize(?ProductVariation $variation = null): int
    {
        return max(1, (int) ($variation?->units_per_pack ?: $this->packSize()));
    }

    /**
     * What one selling unit of the given variation is called ("piece", "box", ...).
     */
    public function lineUnitLabel(?ProductVariation $variation = null): string
    {
        return $variation?->unit_label ?: ($this->unit_label ?: 'piece');
    }

    /**
     * Whether an order totalling $totalPieces pieces of this product earns the wholesale price.
     * The threshold (wholesale_min_qty) is expressed in pieces regardless of how they are packed.
     */
    public function wholesaleQualifies(int $totalPieces): bool
    {
        return $this->hasWholesalePricing() && $totalPieces >= (int) $this->wholesale_min_qty;
    }

    /**
     * Price of ONE selling unit of the given variation.
     *
     * The product's retail/wholesale price is per its own selling unit, so the per-piece price is
     * that price divided by the product's pack size; a line selling N pieces per unit costs
     * (per-piece price × N) plus the variation's price adjustment. With no pack override this is
     * exactly price + adjustment, the behaviour before pack sizes existed.
     */
    public function priceForLine(?ProductVariation $variation, bool $wholesale): float
    {
        $price = (float) ($wholesale ? $this->wholesale_price : $this->base_price);
        $scaled = $price * $this->linePackSize($variation) / $this->packSize();

        return round($scaled + (float) ($variation?->price_adjustment ?? 0), 2);
    }

    /**
     * Quantity + reserved totals for list views (no variations = base inventory rows only;
     * active variations = sum of inventory rows for those variations only).
     *
     * @return array{quantity: int, reserved: int}
     */
    public function stockTotals(): array
    {
        $variationIds = $this->activeVariations()->pluck('id');

        if ($variationIds->isNotEmpty()) {
            // When a product has active variations, the "main" stock comes from variation rows.
            // However, if the base inventory row still has reserved stock (pending orders that were placed
            // before variations were introduced), we include the base row too so the owner sees reality.
            $variationQ = $this->inventory()->whereIn('product_variation_id', $variationIds);
            $variationQty = (int) $variationQ->sum('quantity');
            $variationRes = (int) (clone $variationQ)->sum('reserved_quantity');

            $baseReserved = (int) $this->inventory()->whereNull('product_variation_id')->sum('reserved_quantity');

            if ($baseReserved > 0) {
                return [
                    'quantity' => $variationQty,
                    'reserved' => (int) ($variationRes + $baseReserved),
                ];
            }

            return [
                'quantity' => $variationQty,
                'reserved' => $variationRes,
            ];
        }

        return [
            'quantity' => (int) $this->inventory()->whereNull('product_variation_id')->sum('quantity'),
            'reserved' => (int) $this->inventory()->whereNull('product_variation_id')->sum('reserved_quantity'),
        ];
    }

    /**
     * True when this product is sold in more than one pack size (e.g. loose pieces AND boxes of
     * 10). Stock counts per option are then in different units and must not be added together.
     */
    public function hasMixedPacks(): bool
    {
        $variations = $this->relationLoaded('variations')
            ? $this->variations->where('is_active', true)
            : $this->activeVariations()->get();

        return $variations->map(fn ($v) => $this->linePackSize($v))->unique()->count() > 1;
    }

    /**
     * Total stock expressed in pieces, so options with different pack sizes can be summed.
     * Mirrors stockTotals(): active variations' rows when the product has variations, otherwise
     * the base rows.
     *
     * @return array{quantity: int, reserved: int}
     */
    public function stockInPieces(): array
    {
        $variations = $this->relationLoaded('variations') ? $this->variations : $this->variations()->get();
        $active = $variations->where('is_active', true)->keyBy('id');
        $rows = $this->relationLoaded('inventory') ? $this->inventory : $this->inventory()->get();

        $quantity = 0;
        $reserved = 0;
        foreach ($rows as $row) {
            if ($active->isNotEmpty()) {
                if (! $active->has($row->product_variation_id)) {
                    continue;
                }
                $pack = $this->linePackSize($active->get($row->product_variation_id));
            } else {
                if ($row->product_variation_id !== null) {
                    continue;
                }
                $pack = $this->packSize();
            }

            $quantity += (int) $row->quantity * $pack;
            $reserved += (int) $row->reserved_quantity * $pack;
        }

        return ['quantity' => $quantity, 'reserved' => $reserved];
    }

    public function aggregateStockQuantity(): int
    {
        return $this->stockTotals()['quantity'];
    }

    /**
     * Get total stock for display / POS (see stockTotals).
     */
    public function totalStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->aggregateStockQuantity()
        );
    }

    /**
     * Scope to order products by a popularity score.
     * Popularity = (Units Sold * 10) + (Avg Stars * 5).
     * Secondary sort by latest created_at for recency.
     */
    public function scopeOrderByPopularity($query)
    {
        return $query->addSelect([
            'popularity_score' => \App\Models\OrderItem::selectRaw('SUM(quantity) * 10')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereColumn('order_items.product_id', 'products.id')
                ->whereIn('orders.status', ['completed', 'delivered']),
            'avg_stars_component' => \App\Models\ProductReview::selectRaw('COALESCE(AVG(stars) * 5, 0)')
                ->whereColumn('product_reviews.product_id', 'products.id')
        ])
        ->orderByDesc('popularity_score')
        ->orderByDesc('avg_stars_component') // Also sort by stars as secondary popularity factor
        ->orderByDesc('products.created_at');
    }

    /**
     * Get full URL for product image.
     * Enhanced for resilience on shared hosting (Hostinger).
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $path = null;

                if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
                    $primary = $this->images->firstWhere('is_primary', true);
                    $path = ($primary ?? $this->images->first())?->image_path;
                } else {
                    $path = $this->image_path;
                }

                if (! $path) {
                    return null;
                }

                return PublicStorageUrl::url($path);
            }
        );
    }
}
