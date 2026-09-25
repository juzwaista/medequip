<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * add_pending_po_verification_to_orders_status_enum's up() currently redefines the whole
     * orders.status ENUM to include 'completed' and 'ready_for_pickup' alongside
     * 'pending_po_verification'. But that migration already ran on this database at some
     * point in the past — verified via `SHOW COLUMNS FROM orders` — with content that did
     * NOT include those two values (they were presumably added to the file afterward, which
     * doesn't retroactively re-run an already-executed migration). The live column is
     * currently `enum('pending','pending_po_verification','approved','processing','packed',
     * 'shipped','delivered','cancelled','rejected')` — missing 'completed' and
     * 'ready_for_pickup' entirely. That silently breaks OrderController::confirmReceived()
     * and the ready_for_pickup transition in Owner\OrderController::updateStatus() on any
     * database that already ran the old version of that migration (confirmed: this database
     * had 8 orders stuck at 'delivered', unable to move to 'completed').
     *
     * This migration re-applies the full, correct enum unconditionally so it's safe to run
     * regardless of which version of the other migration a given environment already has.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_po_verification', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected', 'completed', 'ready_for_pickup') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally a no-op: reversing would risk re-truncating 'completed'/'ready_for_pickup'
        // for any order already in one of those states. The narrower enum was a bug, not a
        // deliberate prior state worth restoring.
    }
};
