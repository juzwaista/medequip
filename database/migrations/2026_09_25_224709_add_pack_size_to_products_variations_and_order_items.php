<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pack sizes, so wholesale thresholds can be expressed in pieces even when a distributor sells
     * by the box.
     *
     *  - products.units_per_pack / unit_label: how many pieces one *selling unit* of the product
     *    contains (default 1 = sold by the piece) and what to call it ("piece", "box", ...).
     *    Existing products keep 1 / 'piece', so their pricing and thresholds are unchanged.
     *  - product_variations.units_per_pack / unit_label: optional per-variation override (e.g. a
     *    "Box of 10" option on a product that is otherwise sold by the piece). NULL = inherit.
     *  - order_items.units_per_pack / unit_label: snapshot of what was actually sold on the line.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'units_per_pack')) {
                $table->unsignedInteger('units_per_pack')->default(1);
            }
            if (! Schema::hasColumn('products', 'unit_label')) {
                $table->string('unit_label', 30)->default('piece');
            }
        });

        Schema::table('product_variations', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variations', 'units_per_pack')) {
                $table->unsignedInteger('units_per_pack')->nullable();
            }
            if (! Schema::hasColumn('product_variations', 'unit_label')) {
                $table->string('unit_label', 30)->nullable();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'units_per_pack')) {
                $table->unsignedInteger('units_per_pack')->default(1);
            }
            if (! Schema::hasColumn('order_items', 'unit_label')) {
                $table->string('unit_label', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['units_per_pack', 'unit_label']);
        });
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['units_per_pack', 'unit_label']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['units_per_pack', 'unit_label']);
        });
    }
};
