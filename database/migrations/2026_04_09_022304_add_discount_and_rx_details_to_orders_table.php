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
            // SC/PWD Discount Fields
            $table->string('discount_type')->default('none')->after('prescription_reviewed_at');
            $table->string('discount_id_number')->nullable()->after('discount_type');
            $table->string('discount_id_name')->nullable()->after('discount_id_number');
            $table->string('discount_id_image_path')->nullable()->after('discount_id_name');
            $table->string('discount_status')->default('none')->after('discount_id_image_path');
            $table->text('discount_review_note')->nullable()->after('discount_status');
            $table->timestamp('discount_reviewed_at')->nullable()->after('discount_review_note');

            // Rx enhancements
            $table->string('prescription_patient_name')->nullable()->after('discount_reviewed_at');
            $table->string('prescription_id_image_path')->nullable()->after('prescription_patient_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'discount_id_number',
                'discount_id_name',
                'discount_id_image_path',
                'discount_status',
                'discount_review_note',
                'discount_reviewed_at',
                'prescription_patient_name',
                'prescription_id_image_path',
            ]);
        });
    }
};
