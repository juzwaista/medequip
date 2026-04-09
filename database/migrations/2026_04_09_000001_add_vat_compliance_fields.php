<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_vat_exempt')->default(false)->after('requires_prescription');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vatable_sales', 15, 2)->default(0)->after('total_amount');
            $table->decimal('vat_amount', 15, 2)->default(0)->after('vatable_sales');
            $table->decimal('vat_exempt_sales', 15, 2)->default(0)->after('vat_amount');
            $table->string('tin')->nullable()->after('vat_exempt_sales');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_vat_exempt');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vatable_sales', 'vat_amount', 'vat_exempt_sales', 'tin']);
        });
    }
};
