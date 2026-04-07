<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Display payment management page.
     */
    public function index()
    {
        $distributorId = $this->getDistributor()?->id;

        $payments = Payment::whereHas('invoice.order', function ($q) use ($distributorId) {
            $q->where('distributor_id', $distributorId);
        })
            ->with(['invoice.order'])
            ->orderByDesc('created_at')
            ->paginate(20);

        // Stats
        $baseQuery = Payment::whereHas('invoice.order', function ($q) use ($distributorId) {
            $q->where('distributor_id', $distributorId);
        });

        $stats = [
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'verified' => (clone $baseQuery)->where('status', 'verified')->count(),
            'total_gross' => (clone $baseQuery)->where('status', 'verified')->sum('amount'),
            'total_fees' => (clone $baseQuery)->where('status', 'verified')->sum('platform_fee_amount'),
            'total_net' => (clone $baseQuery)->where('status', 'verified')->sum('net_seller_amount'),
            'escrow_held' => (clone $baseQuery)->where('status', 'verified')->where('escrow_status', 'held')->sum('net_seller_amount'),
            'escrow_released' => (clone $baseQuery)->where('escrow_status', 'released')->sum('net_seller_amount'),
        ];

        return Inertia::render('Owner/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    /**
     * Verify a bank transfer payment (sets escrow to held).
     */
    public function verify(Payment $payment)
    {
        if ($payment->invoice->order->distributor_id !== $this->getDistributor()?->id) {
            abort(403);
        }

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Payment is not pending.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
                'escrow_status' => 'held',
            ]);

            // Ensure fees are calculated
            if ($payment->platform_fee_amount == 0) {
                $payment->applyEscrowFees();
            }

            $payment->refresh()->creditSellerWalletOnVerification();

            // Update invoice status
            $invoice = $payment->invoice()->with('payments')->first();
            $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');

            $invoiceStatus = match (true) {
                $totalPaid >= $invoice->total_amount => 'paid',
                $totalPaid > 0 => 'partial',
                default => 'unpaid',
            };

            $invoice->update(['status' => $invoiceStatus]);
        });

        return back()->with('success', 'Payment verified. Net proceeds are credited to your wallet when the buyer confirms receipt (funds are held by the platform until then).');
    }

    /**
     * Reject a pending payment.
     */
    public function reject(Payment $payment)
    {
        if ($payment->invoice->order->distributor_id !== $this->getDistributor()?->id) {
            abort(403);
        }

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Payment is not pending.');
        }

        $payment->update([
            'status' => 'rejected',
            'escrow_status' => 'refunded',
            'refunded_at' => now(),
        ]);

        return back()->with('success', 'Payment rejected.');
    }
}
