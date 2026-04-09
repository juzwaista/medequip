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

    protected $appends = ['image_url', 'units_sold'];

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
        return $query->select('products.*')
            ->selectRaw('
                (
                    SELECT COALESCE(SUM(order_items.quantity), 0)
                    FROM order_items
                    JOIN orders ON orders.id = order_items.order_id
                    WHERE order_items.product_id = products.id
                    AND orders.status IN ("completed", "delivered")
                ) * 10 +
                (
                    SELECT COALESCE(AVG(product_reviews.stars), 0)
                    FROM product_reviews
                    WHERE product_reviews.product_id = products.id
                ) * 5 as popularity_score
            ')
            ->orderByDesc('popularity_score')
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
