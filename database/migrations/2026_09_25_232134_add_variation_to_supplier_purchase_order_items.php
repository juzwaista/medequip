<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A PO line for a product that has variations (piece / box of 10, sizes, ...) has to say which
     * variation is being ordered, otherwise received stock can't be put in the right inventory row.
     * NULL = the product has no variations (stock lives on its base inventory row).
     */
    public function up(): void
    {
        if (Schema::hasColumn('supplier_purchase_order_items', 'product_variation_id')) {
            return;
        }

        Schema::table('supplier_purchase_order_items', function (Blueprint $table) {
            $table->foreignId('product_variation_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_purchase_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variation_id');
        });
    }
};
