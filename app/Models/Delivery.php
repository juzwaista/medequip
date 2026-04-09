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
        'seller_address',
        'scheduled_date',
        'actual_delivery_at',
        'pickup_started_at',
        'item_scanned_at',
        'pickup_confirmed_at',
        'driver_name',
        'driver_contact',
        'courier_fee',
        'courier_payout_status',
        'courier_paid_at',
        'proof_of_delivery_path',
        'status', // pending, scheduled, picking_up, in_transit, delivered, failed
        'notes',
        'cod_amount',
        'cod_collected_at',
        'cod_remitted_at',
        'cod_remittance_sent_at',
        'proof_latitude',
        'proof_longitude',
        'is_location_flagged',
        'attempts_count',
        'last_attempt_at',
        'failure_reason',
        'failure_note',
        'is_return_to_sender',
        'proof_of_attempt_path',
    ];

    protected $casts = [
        'scheduled_date'      => 'date',
        'actual_delivery_at'  => 'datetime',
        'pickup_started_at'   => 'datetime',
        'item_scanned_at'     => 'datetime',
        'pickup_confirmed_at' => 'datetime',
        'cod_collected_at'    => 'datetime',
        'cod_remitted_at'     => 'datetime',
        'cod_remittance_sent_at' => 'datetime',
        'courier_fee'         => 'decimal:2',
        'courier_paid_at'     => 'datetime',
        'proof_latitude'      => 'decimal:8',
        'proof_longitude'     => 'decimal:8',
        'is_location_flagged' => 'boolean',
        'last_attempt_at'     => 'datetime',
        'attempts_count'      => 'integer',
        'is_return_to_sender' => 'boolean',
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
