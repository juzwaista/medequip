<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->timestamp('pickup_started_at')->nullable()->after('actual_delivery_at');
            $table->timestamp('item_scanned_at')->nullable()->after('pickup_started_at');
            $table->timestamp('pickup_confirmed_at')->nullable()->after('item_scanned_at');
            $table->string('seller_address')->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['pickup_started_at', 'item_scanned_at', 'pickup_confirmed_at', 'seller_address']);
        });
    }
};
