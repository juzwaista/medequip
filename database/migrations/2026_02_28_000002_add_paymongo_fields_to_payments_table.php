<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add PayMongo-specific columns to payments table.
     * Also expands payment_method enum to include 'paymongo'.
     *
     * Existing records are unaffected — new columns are nullable.
     */
    public function up(): void
    {
        // MySQL does not support ALTER COLUMN on enums directly with Blueprint.
        // We use DB::statement to safely extend the enum.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
                'cash',
                'bank_transfer',
                'gcash',
                'paymaya',
                'paymongo'
            ) NOT NULL");
        }

        Schema::table('payments', function (Blueprint $table) {
            // PayMongo Checkout Session ID for webhook reconciliation
            $table->string('paymongo_session_id', 100)
                ->nullable()
                ->unique()
                ->after('reference_number');

            // Last raw status received from PayMongo (active | paid | expired | unpaid)
            $table->string('paymongo_status', 50)
                ->nullable()
                ->after('paymongo_session_id');

            $table->index('paymongo_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['paymongo_session_id']);
            $table->dropColumn(['paymongo_session_id', 'paymongo_status']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
                'cash',
                'bank_transfer',
                'gcash',
                'paymaya'
            ) NOT NULL");
        }
    }
};
