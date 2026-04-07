<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify the ENUM to include 'cancelled'
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        // Remove 'cancelled' — rows with that value should be reverted first in a real rollback
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid','partial','paid','overdue') NOT NULL DEFAULT 'unpaid'");
    }
};
