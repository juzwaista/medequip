<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CheckoutBatch;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PayMongoService $paymongo) {}

    /**
     * PayMongo webhook — receives payment events.
     * Must be excluded from CSRF verification.
     */
    public function webhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->paymongo->verifyWebhookSignature($request)) {
            Log::warning('[PaymentController] Webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event     = $request->json('data.attributes.type');
        $eventData = $request->json('data.attributes.data');

        Log::info('[PaymentController] Webhook received', ['event' => $event]);

        if ($event === 'checkout_session.payment.paid') {
            $sessionId = $eventData['id'] ?? null;
            $metadata  = $eventData['attributes']['metadata'] ?? [];
            $batchId = $metadata['batch_id'] ?? null;
            $invoiceId = $metadata['invoice_id'] ?? null;

            if (!$sessionId) {
                Log::warning('[PaymentController] Webhook missing session_id');
                return response()->json(['error' => 'Missing data'], 422);
            }

            if ($batchId) {
                DB::transaction(function () use ($sessionId, $batchId) {
                    $batch = CheckoutBatch::find($batchId);
                    if (!$batch) {
                        Log::warning('[PaymentController] Batch not found', ['batch_id' => $batchId]);
                        return;
                    }

                    if ($batch->paymongo_session_id !== $sessionId) {
                        Log::warning('[PaymentController] Batch/session mismatch', [
                            'batch_id' => $batchId,
                            'batch_session' => $batch->paymongo_session_id,
                            'webhook_session' => $sessionId,
                        ]);
                        return;
                    }

                    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments */
                    $payments = Payment::whereIn('invoice_id', $batch->invoice_ids ?? [])
                        ->where('status', 'pending')
                        ->get();

                    foreach ($payments as $payment) {
                        /** @var \App\Models\Payment $payment */
                        $payment->update([
                            'status'          => 'verified',
                            'paymongo_status' => 'paid',
                            'verified_at'     => now(),
                            'escrow_status'   => 'held',
                        ]);

                        if ((float) $payment->platform_fee_amount === 0.0) {
                            $payment->applyEscrowFees();
                        }

                        $payment->refresh()->creditSellerWalletOnVerification();

                        $invoice = $payment->invoice;
                        if ($invoice) {
                            $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
                            $invoice->update([
                                'status' => $totalPaid >= $invoice->total_amount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid')
                            ]);
                        }
                    }

                    $batch->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                });

                return response()->json(['received' => true]);
            }

            if (!$invoiceId) {
                Log::warning('[PaymentController] Webhook missing session_id or invoice_id');
                return response()->json(['error' => 'Missing data'], 422);
            }

            DB::transaction(function () use ($sessionId, $invoiceId) {
                $payment = Payment::where('paymongo_session_id', $sessionId)->first();

                if (!$payment) {
                    Log::warning('[PaymentController] No payment for session', ['session_id' => $sessionId]);
                    return;
                }

                if ((int) $payment->invoice_id !== (int) $invoiceId) {
                    Log::warning('[PaymentController] Session/invoice mismatch', [
                        'session_id' => $sessionId,
                        'expected_invoice_id' => $invoiceId,
                        'payment_invoice_id' => $payment->invoice_id,
                    ]);
                    return;
                }

                // Mark as verified, payment held in escrow
                $payment->update([
                    'status'          => 'verified',
                    'paymongo_status' => 'paid',
                    'verified_at'     => now(),
                    'escrow_status'   => 'held',
                ]);

                // Ensure fees are calculated
                if ($payment->platform_fee_amount == 0) {
                    $payment->applyEscrowFees();
                }

                $payment->refresh()->creditSellerWalletOnVerification();

                // Update invoice status
                $invoice = $payment->invoice;
                if (!$invoice) {
                    Log::warning('[PaymentController] Payment has no invoice', ['payment_id' => $payment->id]);
                    return;
                }
                $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');

                $invoiceStatus = match (true) {
                    $totalPaid >= $invoice->total_amount => 'paid',
                    $totalPaid > 0                      => 'partial',
                    default                             => 'unpaid',
                };

                $invoice->update(['status' => $invoiceStatus]);

                Log::info('[PaymentController] Payment verified via webhook (escrow held)', [
                    'payment_id'     => $payment->id,
                    'invoice_status' => $invoiceStatus,
                    'escrow_status'  => 'held',
                    'net_seller'     => $payment->net_seller_amount,
                ]);
            });
        }

        return response()->json(['received' => true]);
    }

    /**
     * PayMongo redirects here on successful payment — redirects to confirmation.
     */
    public function success(Invoice $invoice)
    {
        if ($invoice->order->customer_id !== auth()->id()) {
            abort(403);
        }

        // Fallback reconciliation: PayMongo may redirect before webhook is processed.
        $activePayment = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->where('paymongo_status', 'active')
            ->whereNotNull('paymongo_session_id')
            ->latest()
            ->first();

        if ($activePayment) {
            try {
                $session = $this->paymongo->getCheckoutSession($activePayment->paymongo_session_id);
                if ($this->isCheckoutSessionPaid($session)) {
                    DB::transaction(function () use ($activePayment) {
                        $this->finalizePaymentAsVerified($activePayment);
                    });
                }
            } catch (\Throwable $e) {
                Log::warning('[PaymentController] Success fallback reconciliation failed', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $activePayment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('orders.confirmation', $invoice->order)
            ->with('success', 'Payment successful! Your funds are securely held until delivery.');
    }

    /**
     * PayMongo redirects here on cancelled payment.
     */
    public function cancel(Invoice $invoice)
    {
        if ($invoice->order->customer_id !== auth()->id()) {
            abort(403);
        }

        // Expire the pending payment for this session
        Payment::where('invoice_id', $invoice->id)
            ->where('paymongo_status', 'active')
            ->update(['paymongo_status' => 'expired', 'status' => 'rejected']);

        return redirect()->route('orders.confirmation', $invoice->order)
            ->with('error', 'Payment was cancelled. You can retry from your orders.');
    }

    /**
     * PayMongo redirects here for checkout batches.
     */
    public function batchSuccess(CheckoutBatch $batch)
    {
        if ($batch->user_id !== auth()->id()) {
            abort(403);
        }

        // Fallback reconciliation for multi-order checkout session.
        try {
            $session = $this->paymongo->getCheckoutSession($batch->paymongo_session_id);
            if ($this->isCheckoutSessionPaid($session)) {
                DB::transaction(function () use ($batch) {
                    $payments = Payment::whereIn('invoice_id', $batch->invoice_ids ?? [])
                        ->where('status', 'pending')
                        ->get();

                    foreach ($payments as $payment) {
                        /** @var \App\Models\Payment $payment */
                        $this->finalizePaymentAsVerified($payment);
                    }

                    $batch->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                });
            }
        } catch (\Throwable $e) {
            Log::warning('[PaymentController] Batch success fallback reconciliation failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('orders.confirmation', $batch->primary_order_id)
            ->with('confirmation_order_ids', $batch->order_ids ?? [])
            ->with('success', 'Payment successful! Funds are held in escrow until delivery confirmation.');
    }

    public function batchCancel(CheckoutBatch $batch)
    {
        if ($batch->user_id !== auth()->id()) {
            abort(403);
        }

        Payment::whereIn('invoice_id', $batch->invoice_ids ?? [])
            ->where('paymongo_status', 'active')
            ->update(['paymongo_status' => 'expired', 'status' => 'rejected']);

        $batch->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('orders.confirmation', $batch->primary_order_id)
            ->with('confirmation_order_ids', $batch->order_ids ?? [])
            ->with('error', 'Payment was cancelled. You can retry from your orders.');
    }

    private function finalizePaymentAsVerified(Payment $payment): void
    {
        $payment->update([
            'status'          => 'verified',
            'paymongo_status' => 'paid',
            'verified_at'     => now(),
            'escrow_status'   => 'held',
        ]);

        if ((float) $payment->platform_fee_amount === 0.0) {
            $payment->applyEscrowFees();
        }

        $payment->refresh()->creditSellerWalletOnVerification();

        $invoice = $payment->invoice;
        if (!$invoice) {
            return;
        }

        $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
        $invoice->update([
            'status' => $totalPaid >= $invoice->total_amount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
        ]);
    }

    private function isCheckoutSessionPaid(array $session): bool
    {
        $attributes = $session['attributes'] ?? [];
        $sessionStatus = strtolower((string) ($attributes['status'] ?? ''));
        $paymentStatus = strtolower((string) ($attributes['payment_status'] ?? ''));

        if (in_array($sessionStatus, ['paid', 'succeeded', 'completed'], true)) {
            return true;
        }

        if (in_array($paymentStatus, ['paid', 'succeeded'], true)) {
            return true;
        }

        $paymentIntentStatus = strtolower((string) ($attributes['payment_intent']['attributes']['status'] ?? ''));
        if (in_array($paymentIntentStatus, ['succeeded', 'paid'], true)) {
            return true;
        }

        $payments = $attributes['payments'] ?? [];
        if (is_array($payments)) {
            foreach ($payments as $entry) {
                $entryStatus = strtolower((string) ($entry['attributes']['status'] ?? $entry['status'] ?? ''));
                if (in_array($entryStatus, ['paid', 'succeeded'], true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
