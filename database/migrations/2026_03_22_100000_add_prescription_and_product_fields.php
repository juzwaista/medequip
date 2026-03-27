<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_prescription')->default(false)->after('is_active');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('prescription_status', 32)->default('not_required')->after('status');
            $table->string('prescription_image_path')->nullable()->after('prescription_status');
            $table->text('prescription_review_note')->nullable()->after('prescription_image_path');
            $table->timestamp('prescription_reviewed_at')->nullable()->after('prescription_review_note');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'prescription_status',
                'prescription_image_path',
                'prescription_review_note',
                'prescription_reviewed_at',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('requires_prescription');
        });
    }
};
