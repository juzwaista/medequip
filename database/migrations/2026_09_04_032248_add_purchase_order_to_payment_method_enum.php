<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN for enums — we handle it by adding a check.
        // For MySQL, we modify the ENUM column.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
                'cash','cod','bank_transfer','gcash','paymaya','paymongo','card','grab_pay','purchase_order'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
                'cash','cod','bank_transfer','gcash','paymaya','paymongo','card','grab_pay'
            ) NOT NULL");
        }
    }
};
