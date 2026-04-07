<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter the ENUM to add 'picking_up' between 'scheduled' and 'in_transit'
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('scheduled','picking_up','in_transit','delivered','failed') NOT NULL DEFAULT 'scheduled'");
        }
    }

    public function down(): void
    {
        // Remove 'picking_up' — any rows with that value should be reverted to 'scheduled'
        DB::statement("UPDATE deliveries SET status = 'scheduled' WHERE status = 'picking_up'");
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('scheduled','in_transit','delivered','failed') NOT NULL DEFAULT 'scheduled'");
        }
    }
};
