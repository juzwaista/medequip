<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Add subtotal as an alias/denormalized column used by POS
            $table->decimal('subtotal', 10, 2)->default(0)->after('total_price');
            // inventory_id is required in original schema but POS doesn't always have one
            $table->unsignedBigInteger('inventory_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('subtotal');
            $table->unsignedBigInteger('inventory_id')->nullable(false)->change();
        });
    }
};
