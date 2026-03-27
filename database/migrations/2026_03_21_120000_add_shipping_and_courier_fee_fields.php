<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_fee')) {
                $table->decimal('shipping_fee', 12, 2)->default(0)->after('subtotal');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'shipping_fee')) {
                $table->decimal('shipping_fee', 12, 2)->default(0)->after('subtotal');
            }
        });

        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'courier_fee')) {
                $table->decimal('courier_fee', 12, 2)->default(0)->after('driver_contact');
            }
            if (!Schema::hasColumn('deliveries', 'courier_payout_status')) {
                $table->enum('courier_payout_status', ['pending', 'paid'])->default('pending')->after('courier_fee');
            }
            if (!Schema::hasColumn('deliveries', 'courier_paid_at')) {
                $table->timestamp('courier_paid_at')->nullable()->after('courier_payout_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_fee')) {
                $table->dropColumn('shipping_fee');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'shipping_fee')) {
                $table->dropColumn('shipping_fee');
            }
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $toDrop = [];
            if (Schema::hasColumn('deliveries', 'courier_fee')) {
                $toDrop[] = 'courier_fee';
            }
            if (Schema::hasColumn('deliveries', 'courier_payout_status')) {
                $toDrop[] = 'courier_payout_status';
            }
            if (Schema::hasColumn('deliveries', 'courier_paid_at')) {
                $toDrop[] = 'courier_paid_at';
            }
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
