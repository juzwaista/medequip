<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OrderController::placeOrder and Order::$fillable write `customer_name` (the recipient name
     * from the checkout form), but no earlier migration ever created the column, so every order
     * placement failed with "Unknown column 'customer_name'". The hasColumn guard keeps this safe
     * on any database where the column was added by hand.
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'customer_name')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'customer_name')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_name');
        });
    }
};
