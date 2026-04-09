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
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->string('dispute_status')->default('none')->after('body'); // none, pending, settled
            $table->text('dispute_reason')->nullable()->after('dispute_status');
            $table->timestamp('dispute_resolved_at')->nullable()->after('dispute_reason');
            $table->boolean('is_hidden')->default(false)->after('dispute_resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn(['dispute_status', 'dispute_reason', 'dispute_resolved_at', 'is_hidden']);
        });
    }
};
