<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','wallet','bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('payments')->where('payment_method', 'wallet')->update(['payment_method' => 'bank_transfer']);
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
        }
    }
};
