<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Refunds customer funds for prescription rejection and other owner-driven cancellations.
 */
class OrderPrescriptionRefundService
{
    public function refundAfterPrescriptionRejection(Order $order): void
    {
        $this->refundAfterOrderCancellation($order, 'prescription_rejected');
    }

    /**
     * Refund customer funds when an order is cancelled/rejected after payment.
     * Refunds are applied only for payments that are VERIFIED AND ESCROW HELD.
     */
    public function refundAfterOrderCancellation(Order $order, string $reason = 'order_cancelled'): void
    {
        $order->loadMissing(['invoice.payments', 'customer.wallet']);

        if (! $order->invoice) {
            return;
        }

        DB::transaction(function () use ($order, $reason) {
            foreach ($order->invoice->payments as $payment) {
                if ($payment->status !== 'verified' || $payment->escrow_status !== 'held') {
                    continue;
                }

                if ($order->customer?->wallet) {
                    // Per requirement: regardless of payment method, refund into the customer's wallet.
                    $order->customer->wallet->credit(
                        (float) $payment->amount,
                        'order_refund',
                        (string) $order->id,
                        'Wallet refund — '.($reason === 'prescription_rejected' ? 'prescription not accepted' : 'order cancelled/rejected').' (Order '.$order->order_number.')'
                    );
                } else {
                    Log::warning('[OrderPrescriptionRefundService] Customer wallet missing; skipping wallet credit', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                    ]);
                }

                // Claw back seller wallet proceeds if we already credited them on verification.
                $payment->loadMissing('invoice.order.distributor.owner.wallet');
                $sellerWallet = $payment->invoice?->order?->distributor?->owner?->wallet;
                if ($sellerWallet && $payment->seller_wallet_credited_at && (float) $payment->net_seller_amount > 0) {
                    try {
                        $sellerWallet->debit(
                            (float) $payment->net_seller_amount,
                            'escrow_refund_clawback',
                            (string) $payment->id,
                            'Clawback — cancellation refund (Order '.$order->order_number.')'
                        );
                    } catch (\Throwable $e) {
                        Log::error('[OrderPrescriptionRefundService] Seller wallet clawback failed', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $payment->refundEscrow();
            }

            // Update invoice status to valid enum values only.
            $invoice = $order->invoice;
            $verifiedRemaining = (float) $invoice->payments()->where('status', 'verified')->sum('amount');

            $invoice->update([
                'status' => match (true) {
                    $verifiedRemaining <= 0.0 => 'unpaid',
                    $verifiedRemaining < (float) $invoice->total_amount => 'partial',
                    default => 'paid',
                },
            ]);
        });
    }
}
