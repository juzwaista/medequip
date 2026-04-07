<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Release escrow for verified payments on completed/delivered orders
 * that are still stuck with escrow_status = 'held'.
 *
 * releaseEscrow() is safe to call here:
 *  - If seller_wallet_credited_at is already set → it just flips escrow_status to
 *    'released' without touching wallets (no double-pay risk).
 *  - If seller_wallet_credited_at is null → it debits admin wallet, credits seller
 *    wallet, and sets the timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        $paymentIds = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->where('payments.status', 'verified')
            ->where('payments.escrow_status', 'held')
            ->pluck('payments.id');

        if ($paymentIds->isEmpty()) {
            Log::info('[EscrowReconciliation] No payments to reconcile.');
            return;
        }

        Log::info("[EscrowReconciliation] Releasing escrow for {$paymentIds->count()} payment(s).");

        $succeeded = 0;
        $failed    = 0;

        foreach ($paymentIds as $id) {
            try {
                $payment = Payment::with('invoice.order.distributor.owner.wallet')->find($id);
                if (! $payment) continue;

                $payment->releaseEscrow();
                $succeeded++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error("[EscrowReconciliation] Failed to release escrow for payment #{$id}: {$e->getMessage()}");
            }
        }

        Log::info("[EscrowReconciliation] Done. Released: {$succeeded}, Failed: {$failed}.");
    }

    public function down(): void
    {
        // Irreversible financial operation — no rollback.
    }
};
