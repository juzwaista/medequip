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
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_po_verification', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down migration: if there are any orders with 'pending_po_verification', change them back to pending first to avoid errors.
        DB::table('orders')->where('status', 'pending_po_verification')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
