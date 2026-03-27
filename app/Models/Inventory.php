<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'product_variation_id',
        'branch_id',
        'quantity',
        'reorder_level',
        'expiry_date',
        'batch_number',
        'reserved_quantity',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    /**
     * Get the branch (nullable for distributors without branches)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get order items using this inventory
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get available stock (total - reserved)
     */
    public function availableStock(): Attribute
    {
        return Attribute::make(
            get: fn() => max(0, $this->quantity - $this->reserved_quantity)
        );
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    /**
     * Check if expiring soon (within 60 days default)
     */
    public function isExpiringSoon(int $days = 60): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        $threshold = now()->addDays($days);
        return $this->expiry_date <= $threshold && $this->expiry_date > now();
    }

    /**
     * Reserve stock for an order
     */
    public function reserve(int $quantity): bool
    {
        if ($this->availableStock < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);
        return true;
    }

    /**
     * Release reserved stock (e.g., order cancelled)
     */
    public function releaseReservation(int $quantity): void
    {
        if ($quantity > $this->reserved_quantity) {
            \Log::error('[Inventory] Attempting to release more than reserved', [
                'inventory_id' => $this->id,
                'product_id' => $this->product_id,
                'requested_release' => $quantity,
                'current_reserved' => $this->reserved_quantity
            ]);
            
            throw new \Exception(
                "Cannot release {$quantity} units from inventory #{$this->id}. Only {$this->reserved_quantity} units are reserved."
            );
        }
        
        $this->decrement('reserved_quantity', $quantity);
        
        \Log::info('[Inventory] Reserved stock released', [
            'inventory_id' => $this->id,
            'released' => $quantity,
            'remaining_reserved' => $this->reserved_quantity - $quantity
        ]);
    }

    /**
     * Deduct stock (after order approval)
     */
    public function deduct(int $quantity): bool
    {
        if ($this->quantity < $quantity) {
            return false;
        }

        $this->decrement('quantity', $quantity);
        $this->decrement('reserved_quantity', min($quantity, $this->reserved_quantity));
        return true;
    }
}
