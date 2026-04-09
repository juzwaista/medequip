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
        Schema::table('distributors', function (Blueprint $table) {
            // We'll keep the existing 'address' column as the full address for backward compatibility,
            // but add structured fields to match CustomerAddress.
            if (!Schema::hasColumn('distributors', 'barangay')) {
                $table->string('barangay')->nullable()->after('address');
            }
            if (!Schema::hasColumn('distributors', 'city')) {
                $table->string('city')->nullable()->after('barangay');
            }
            if (!Schema::hasColumn('distributors', 'province')) {
                $table->string('province')->default('Cavite')->after('city');
            }
            if (!Schema::hasColumn('distributors', 'zip_code')) {
                $table->string('zip_code')->nullable()->after('province');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['barangay', 'city', 'province', 'zip_code']);
        });
    }
};
