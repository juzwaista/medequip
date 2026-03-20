<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add missing columns to orders table:
     * - contact_number: stored during checkout, needed for delivery coordination
     * - delivered_at: timestamp marked when order status changes to 'delivered'
     *
     * Both columns are nullable to preserve existing order records.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add contact_number after delivery_address (where it logically belongs)
            $table->string('contact_number', 30)->nullable()->after('delivery_address');

            // Add delivered_at timestamp after cancelled_at
            $table->timestamp('delivered_at')->nullable()->after('cancelled_at');

            // Index for analytics queries on delivered orders
            $table->index('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivered_at']);
            $table->dropColumn(['contact_number', 'delivered_at']);
        });
    }
};
