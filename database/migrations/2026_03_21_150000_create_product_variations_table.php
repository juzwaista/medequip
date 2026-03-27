<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('option_name', 100);
            $table->string('option_value', 100);
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->string('sku', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'option_name', 'option_value'], 'product_variations_unique_option');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
