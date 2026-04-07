<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile invoice and payment statuses for orders that are already
 * completed/delivered but whose invoice record was never updated
 * (because the sync logic didn't exist yet at the time they were processed).
 *
 * Rules applied:
 *  - Order status IN ('completed', 'delivered') AND invoice.status = 'unpaid'/'partial'
 *    AND at least one verified payment exists → set invoice.status = 'paid'
 *  - Order status IN ('cancelled', 'rejected') AND invoice.status NOT 'cancelled'
 *    → set invoice.status = 'cancelled'
 *  - Payments that belong to a 'paid' invoice and have status = 'pending' with
 *    paymongo_status IN ('paid', 'active') → mark as 'verified'
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Mark invoices as PAID for completed/delivered orders ────────────
        // Find invoices linked to completed/delivered orders that have at least
        // one verified payment but invoice.status is still unpaid/partial.
        $reconciledPaid = DB::table('invoices')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->whereIn('invoices.status', ['unpaid', 'partial'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.invoice_id', 'invoices.id')
                    ->where('payments.status', 'verified');
            })
            ->pluck('invoices.id');

        if ($reconciledPaid->isNotEmpty()) {
            DB::table('invoices')
                ->whereIn('id', $reconciledPaid)
                ->update(['status' => 'paid', 'updated_at' => now()]);

            Log::info("[Reconciliation] Marked {$reconciledPaid->count()} invoice(s) as paid for completed/delivered orders.");
        }

        // ── 2. Mark invoices as PAID for completed/delivered orders ────────────
        // where ALL payments have paymongo_status = 'paid' (webhook confirmed)
        // but payment.status was never flipped to 'verified' internally.
        // First, flip those payments to verified.
        $paymentsToVerify = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->where('payments.status', 'pending')
            ->whereIn('payments.paymongo_status', ['paid', 'active'])
            ->pluck('payments.id');

        if ($paymentsToVerify->isNotEmpty()) {
            DB::table('payments')
                ->whereIn('id', $paymentsToVerify)
                ->update([
                    'status'      => 'verified',
                    'verified_at' => now(),
                    'updated_at'  => now(),
                ]);

            Log::info("[Reconciliation] Verified {$paymentsToVerify->count()} payment(s) that had paymongo_status=paid but status=pending.");

            // Now re-check invoices for these payments and mark as paid.
            $invoiceIds = DB::table('payments')
                ->whereIn('id', $paymentsToVerify)
                ->pluck('invoice_id');

            DB::table('invoices')
                ->whereIn('id', $invoiceIds)
                ->whereIn('status', ['unpaid', 'partial'])
                ->update(['status' => 'paid', 'updated_at' => now()]);
        }

        // ── 3. Mark invoices as PAID for completed/delivered non-digital orders ─
        // COD orders that are completed but invoice still unpaid — mark paid & verify payment.
        $codPaymentsToVerify = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->where('payments.payment_method', 'cod')
            ->where('payments.status', 'pending')
            ->pluck('payments.id');

        if ($codPaymentsToVerify->isNotEmpty()) {
            DB::table('payments')
                ->whereIn('id', $codPaymentsToVerify)
                ->update([
                    'status'      => 'verified',
                    'verified_at' => now(),
                    'updated_at'  => now(),
                ]);

            $codInvoiceIds = DB::table('payments')
                ->whereIn('id', $codPaymentsToVerify)
                ->pluck('invoice_id');

            DB::table('invoices')
                ->whereIn('id', $codInvoiceIds)
                ->whereIn('status', ['unpaid', 'partial'])
                ->update(['status' => 'paid', 'updated_at' => now()]);

            Log::info("[Reconciliation] Reconciled {$codPaymentsToVerify->count()} COD payment(s) for completed orders.");
        }

        // ── 4. Mark invoices as CANCELLED for cancelled/rejected orders ─────────
        $cancelledInvoices = DB::table('invoices')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.status', ['cancelled', 'rejected'])
            ->whereNotIn('invoices.status', ['cancelled'])
            ->pluck('invoices.id');

        if ($cancelledInvoices->isNotEmpty()) {
            DB::table('invoices')
                ->whereIn('id', $cancelledInvoices)
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            Log::info("[Reconciliation] Marked {$cancelledInvoices->count()} invoice(s) as cancelled for cancelled/rejected orders.");
        }
    }

    public function down(): void
    {
        // Irreversible data reconciliation — no rollback.
    }
};
