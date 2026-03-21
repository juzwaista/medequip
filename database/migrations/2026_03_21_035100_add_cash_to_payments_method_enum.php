<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Re-add 'cash' to the payment_method ENUM for POS in-store transactions
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
    }

    public function down(): void
    {
        // Remove 'cash' again — migrate existing cash payments first
        DB::table('payments')->where('payment_method', 'cash')->update(['payment_method' => 'bank_transfer']);
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
    }
};
