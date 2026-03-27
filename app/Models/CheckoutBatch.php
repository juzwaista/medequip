<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutBatch extends Model
{
    protected $fillable = [
        'user_id',
        'primary_order_id',
        'paymongo_session_id',
        'order_ids',
        'invoice_ids',
        'total_amount',
        'status',
        'paid_at',
        'cancelled_at',
    ];

    protected $casts = [
        'order_ids' => 'array',
        'invoice_ids' => 'array',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'primary_order_id');
    }
}
