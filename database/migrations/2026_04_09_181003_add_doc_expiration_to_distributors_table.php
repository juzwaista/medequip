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
            $table->date('valid_id_expires_at')->nullable();
            $table->date('business_license_expires_at')->nullable();
            $table->date('dti_sec_expires_at')->nullable();
            $table->date('bir_form_expires_at')->nullable();
            $table->date('fda_license_expires_at')->nullable();
            $table->date('prc_id_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn([
                'valid_id_expires_at',
                'business_license_expires_at',
                'dti_sec_expires_at',
                'bir_form_expires_at',
                'fda_license_expires_at',
                'prc_id_expires_at',
            ]);
        });
    }
};
