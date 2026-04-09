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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('packaging_before_image_path')->nullable()->after('prescription_id_image_path');
            $table->string('packaging_after_image_path')->nullable()->after('packaging_before_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['packaging_before_image_path', 'packaging_after_image_path']);
        });
    }
};
