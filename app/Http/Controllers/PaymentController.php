<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
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
            $invoiceId = $metadata['invoice_id'] ?? null;

            if (!$sessionId || !$invoiceId) {
                Log::warning('[PaymentController] Webhook missing session_id or invoice_id');
                return response()->json(['error' => 'Missing data'], 422);
            }

            DB::transaction(function () use ($sessionId, $invoiceId) {
                $payment = Payment::where('paymongo_session_id', $sessionId)->first();

                if (!$payment) {
                    Log::warning('[PaymentController] No payment for session', ['session_id' => $sessionId]);
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

                // Update invoice status
                $invoice   = $payment->invoice()->with('payments')->first();
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
}
