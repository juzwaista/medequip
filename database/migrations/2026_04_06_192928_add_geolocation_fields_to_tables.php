<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('zip_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('status');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_address');
            $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->decimal('proof_latitude', 10, 8)->nullable()->after('proof_photo');
            $table->decimal('proof_longitude', 11, 8)->nullable()->after('proof_latitude');
            $table->boolean('is_location_flagged')->default(false)->after('proof_longitude')->comment('True if proof photo was uploaded >500m from destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['proof_latitude', 'proof_longitude', 'is_location_flagged']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_latitude', 'delivery_longitude']);
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
