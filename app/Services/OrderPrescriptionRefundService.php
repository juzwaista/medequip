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
        $order->loadMissing(['invoice.payments']);

        if (! $order->invoice) {
            return;
        }

        DB::transaction(function () use ($order, $reason) {
            $payMongo = app(\App\Services\PayMongoService::class);

            foreach ($order->invoice->payments as $payment) {
                if ($payment->status !== 'verified' || $payment->escrow_status !== 'held') {
                    continue;
                }

                if ($payment->payment_method !== 'cash' && $payment->payment_method !== 'cod' && $payment->paymongo_session_id) {
                    try {
                        $paymongoPaymentId = $payMongo->getPaymentIdFromSession($payment->paymongo_session_id);
                        if ($paymongoPaymentId) {
                            $payMongo->refundPayment(
                                $paymongoPaymentId,
                                (float) $payment->amount,
                                $reason === 'prescription_rejected' ? 'others' : 'requested_by_customer'
                            );
                        } else {
                            Log::warning('[OrderPrescriptionRefundService] Could not resolve payment ID for refund', [
                                'payment_id' => $payment->id,
                                'session_id' => $payment->paymongo_session_id
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('[OrderPrescriptionRefundService] PayMongo API refund failed', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $payment->refundEscrow();
            }

            // Mark invoice as cancelled — the order itself is cancelled/rejected.
            $invoice = $order->invoice;
            $invoice->update(['status' => 'cancelled']);
        });
    }
}
