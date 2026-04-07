<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'option_name',
        'option_value',
        'combination',
        'price_adjustment',
        'sku',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'combination' => 'array',
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['display_label'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_variation_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        if (! empty($this->combination) && is_array($this->combination)) {
            return collect($this->combination)
                ->map(fn ($val, $key) => "$key: $val")
                ->implode(' / ');
        }

        return $this->option_name.': '.$this->option_value;
    }
}
