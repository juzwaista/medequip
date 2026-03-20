<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'courier_id',
        'tracking_number',
        'delivery_address',
        'scheduled_date',
        'actual_delivery_at',
        'driver_name',
        'driver_contact',
        'proof_of_delivery_path',
        'status', // pending, scheduled, in_transit, delivered, failed
        'notes',
    ];

    protected $casts = [
        'scheduled_date'     => 'date',
        'actual_delivery_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /**
     * Generate a unique tracking number for a delivery.
     */
    public static function generateTrackingNumber(): string
    {
        do {
            $number = 'TRK-' . strtoupper(\Illuminate\Support\Str::random(3)) . '-' . now()->format('ymd') . '-' . rand(1000, 9999);
        } while (static::where('tracking_number', $number)->exists());

        return $number;
    }
}
