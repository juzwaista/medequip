<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'payment_method',       // bank_transfer | gcash | paymaya | paymongo | card | grab_pay
        'amount',
        'reference_number',
        'proof_of_payment_path',
        'status',               // pending | verified | rejected
        'escrow_status',        // held | released | refunded
        'platform_fee_rate',    // e.g. 0.0500 = 5%
        'platform_fee_amount',
        'net_seller_amount',
        'verified_at',
        'released_at',
        'refunded_at',
        'paymongo_session_id',
        'paymongo_status',      // active | paid | expired
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'platform_fee_rate'   => 'decimal:4',
        'platform_fee_amount' => 'decimal:2',
        'net_seller_amount'   => 'decimal:2',
        'verified_at'         => 'datetime',
        'released_at'         => 'datetime',
        'refunded_at'         => 'datetime',
    ];

    /**
     * Calculate platform fees for a given amount.
     */
    public static function calculateFees(float $amount): array
    {
        $defaultRate = config('services.platform.fee_rate', 0.05);
        $rate = \App\Models\SystemSetting::getSetting('platform_fee_rate', $defaultRate);
        $feeAmount = round($amount * $rate, 2);
        $netAmount = round($amount - $feeAmount, 2);

        return [
            'platform_fee_rate'   => $rate,
            'platform_fee_amount' => $feeAmount,
            'net_seller_amount'   => $netAmount,
        ];
    }

    /**
     * Apply fees and set escrow to held.
     */
    public function applyEscrowFees(): void
    {
        $fees = self::calculateFees((float)$this->amount);
        $this->update(array_merge($fees, [
            'escrow_status' => 'held',
        ]));
    }

    /**
     * Release escrow funds (after buyer confirms receipt).
     */
    public function releaseEscrow(): void
    {
        $this->update([
            'escrow_status' => 'released',
            'released_at'   => now(),
        ]);

        // Credit the seller's wallet
        $sellerWallet = $this->invoice->order->distributor->owner?->wallet;
        if ($sellerWallet && $this->net_seller_amount > 0) {
            $sellerWallet->credit(
                $this->net_seller_amount,
                'escrow_release',
                (string)$this->id,
                "Escrow released for Invoice #{$this->invoice->invoice_number}"
            );
        }
    }

    /**
     * Refund escrow (for returns/disputes).
     */
    public function refundEscrow(): void
    {
        $this->update([
            'escrow_status' => 'refunded',
            'refunded_at'   => now(),
        ]);
    }

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the full URL for proof of payment.
     */
    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_of_payment_path
            ? asset('storage/' . $this->proof_of_payment_path)
            : null;
    }

    /**
     * Allowed payment methods.
     * 'cash' is only used for in-store POS transactions.
     * Online orders use PayMongo (card, gcash, paymaya, etc.)
     */
    public static function allowedMethods(): array
    {
        return ['cash', 'bank_transfer', 'gcash', 'paymaya', 'paymongo', 'card', 'grab_pay'];
    }

    /**
     * PayMongo payment methods (online).
     */
    public static function paymongoMethods(): array
    {
        return ['card', 'gcash', 'paymaya'];
    }
}
