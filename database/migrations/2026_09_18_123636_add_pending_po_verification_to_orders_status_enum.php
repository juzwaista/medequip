<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Raw ENUM ALTER is MySQL-only syntax; SQLite (used in tests) stores the column as
        // plain text with no engine-level enum constraint, so there's nothing to alter there —
        // mirrors the guard used by the sibling enum migrations (e.g. add_completed_to_orders_status_enum).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_po_verification', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected', 'completed', 'ready_for_pickup') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down migration: if there are any orders with 'pending_po_verification', change them back to pending first to avoid errors.
        DB::table('orders')->where('status', 'pending_po_verification')->update(['status' => 'pending']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected', 'completed', 'ready_for_pickup') NOT NULL DEFAULT 'pending'");
        }
    }
};
