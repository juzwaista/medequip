<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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
        'seller_wallet_credited_at',
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
        'seller_wallet_credited_at' => 'datetime',
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

    public function creditSellerWalletOnVerification(): void
    {
        $this->refresh();

        if ($this->seller_wallet_credited_at !== null || $this->status !== 'verified') {
            return;
        }

        if ((float) $this->net_seller_amount <= 0) {
            return;
        }

        $superAdmin = \App\Models\User::whereIn('role', ['super_admin', 'superadmin', 'admin'])->first();
        $superAdminWallet = $superAdmin?->wallet;

        if (! $superAdminWallet) {
            return;
        }

        $superAdminWallet->credit(
            (float) $this->net_seller_amount,
            'escrow_held',
            (string) $this->id,
            "Payment held by platform (Invoice #{$this->invoice_id})"
        );

        // Intentionally DO NOT update seller_wallet_credited_at
        // This marks that the seller has not received it yet.
    }

    public function releaseEscrow(): void
    {
        if ($this->seller_wallet_credited_at !== null) {
            return;
        }

        DB::transaction(function () {
            $this->update([
                'escrow_status' => 'released',
                'released_at'   => now(),
            ]);
            $this->refresh();

            $amount = (float) $this->net_seller_amount;
            if ($amount <= 0) {
                $this->update(['seller_wallet_credited_at' => now()]);

                return;
            }

            $this->loadMissing('invoice.order.distributor.owner.wallet');
            $invoice = $this->invoice;
            $sellerWallet = $invoice?->order?->distributor?->owner?->wallet;
            $superAdmin = \App\Models\User::whereIn('role', ['super_admin', 'superadmin', 'admin'])->first();
            $superAdminWallet = $superAdmin?->wallet;

            if (! $sellerWallet) {
                Log::warning('[Payment] releaseEscrow: seller wallet missing', ['payment_id' => $this->id]);

                throw new RuntimeException('Seller wallet not found for payout.');
            }

            if (! $superAdminWallet || $superAdminWallet->balance < $amount) {
                Log::warning('[Payment] releaseEscrow: platform holding balance insufficient', [
                    'payment_id' => $this->id,
                    'required' => $amount,
                    'available' => $superAdminWallet?->balance ?? 0,
                ]);

                throw new RuntimeException('Platform holding balance insufficient to complete payout.');
            }

            $superAdminWallet->debit(
                $amount,
                'escrow_release_payout',
                (string) $this->id,
                "Payout from platform hold to seller (Invoice #{$invoice->invoice_number})"
            );

            $sellerWallet->credit(
                $amount,
                'escrow_release',
                (string) $this->id,
                "Order payout released (Invoice #{$invoice->invoice_number})"
            );

            $this->update(['seller_wallet_credited_at' => now()]);
        });
    }

    /**
     * Refund escrow (for returns/disputes).
     */
    public function refundEscrow(): void
    {
        $this->update([
            'escrow_status' => 'refunded',
            'refunded_at'   => now(),
            // Treat refunded payments as not payable anymore so invoice totals/balances stay consistent.
            'status'         => 'rejected',
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
        return ['cash', 'cod', 'wallet', 'bank_transfer', 'gcash', 'paymaya', 'paymongo', 'card', 'grab_pay'];
    }

    /**
     * Cash-on-delivery: no PayMongo session, no escrow.
     */
    public static function isCod(string $method): bool
    {
        return $method === 'cod';
    }

    /**
     * PayMongo payment methods (online).
     */
    public static function paymongoMethods(): array
    {
        return ['card', 'gcash', 'paymaya'];
    }
}
