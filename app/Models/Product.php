<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
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
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'has_expiry' => 'boolean',
        'has_warranty' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the distributor that owns this product
     */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
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
        return $this->hasMany(ProductImage::class);
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
     * Check if wholesale pricing is available
     */
    public function hasWholesalePricing(): bool
    {
        return !is_null($this->wholesale_price) && !is_null($this->wholesale_min_qty);
    }

    /**
     * Get total stock across all branches
     */
    public function totalStock(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->inventory()->sum('quantity')
        );
    }

    /**
     * Get full URL for product image
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image_path 
                ? \Storage::url($this->image_path) 
                : null
        );
    }
}
