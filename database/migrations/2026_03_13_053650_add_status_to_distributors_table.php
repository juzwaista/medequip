<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            // Status for admin approval flow: pending, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_verified');
            $table->text('rejection_reason')->nullable()->after('status');
        });

        // Set existing verified distributors to 'approved'
        DB::table('distributors')->where('is_verified', 1)->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason']);
        });
    }
};
