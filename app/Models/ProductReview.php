<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'stars',
        'body',
        'dispute_status',
        'dispute_reason',
        'dispute_resolved_at',
        'is_hidden',
    ];

    protected $casts = [
        'dispute_resolved_at' => 'datetime',
        'is_hidden' => 'boolean',
    ];

    public function isDisputed(): bool
    {
        return $this->dispute_status !== 'none';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
