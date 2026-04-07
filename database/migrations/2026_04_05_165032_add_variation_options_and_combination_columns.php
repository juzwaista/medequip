<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'variation_options')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('variation_options')->nullable()->after('is_featured');
            });
        }

        if (! Schema::hasColumn('product_variations', 'combination')) {
            Schema::table('product_variations', function (Blueprint $table) {
                $table->json('combination')->nullable()->after('option_value');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('combination');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variation_options');
        });
    }
};
