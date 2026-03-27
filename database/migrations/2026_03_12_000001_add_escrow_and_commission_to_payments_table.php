<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add escrow and commission fields to payments (idempotent)
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'escrow_status')) {
                $table->enum('escrow_status', ['held', 'released', 'refunded'])
                      ->default('held')
                      ->after('status');
            }
            if (!Schema::hasColumn('payments', 'platform_fee_rate')) {
                $table->decimal('platform_fee_rate', 5, 4)
                      ->default(0.0500)
                      ->after('escrow_status');
            }
            if (!Schema::hasColumn('payments', 'platform_fee_amount')) {
                $table->decimal('platform_fee_amount', 12, 2)
                      ->default(0)
                      ->after('platform_fee_rate');
            }
            if (!Schema::hasColumn('payments', 'net_seller_amount')) {
                $table->decimal('net_seller_amount', 12, 2)
                      ->default(0)
                      ->after('platform_fee_amount');
            }
            if (!Schema::hasColumn('payments', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('released_at');
            }
        });

        // Add payment_method and received_at to orders (idempotent)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 30)
                      ->nullable()
                      ->after('notes');
            }
            if (!Schema::hasColumn('orders', 'received_at')) {
                $table->timestamp('received_at')
                      ->nullable()
                      ->after('delivered_at');
            }
        });

        // Convert existing 'cash' payments to 'bank_transfer' before altering enum
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('payments')
                ->where('payment_method', 'cash')
                ->update(['payment_method' => 'bank_transfer']);

            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
        }

        // Backfill net_seller_amount for existing verified payments
        DB::table('payments')
            ->where('status', 'verified')
            ->where('net_seller_amount', 0)
            ->update([
                'net_seller_amount' => DB::raw('amount - platform_fee_amount'),
            ]);
    }

    public function down(): void
    {
        // Restore cash enum option first
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'escrow_status',
                'platform_fee_rate',
                'platform_fee_amount',
                'net_seller_amount',
                'released_at',
                'refunded_at',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'received_at']);
        });
    }
};
