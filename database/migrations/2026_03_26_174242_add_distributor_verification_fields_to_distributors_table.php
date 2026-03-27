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
            $table->string('dti_sec_path')->nullable()->after('business_license_path');
            $table->string('bir_form_path')->nullable()->after('dti_sec_path');
            $table->string('fda_license_path')->nullable()->after('bir_form_path');
            $table->string('prc_id_path')->nullable()->after('fda_license_path');
            $table->string('authorization_letter_path')->nullable()->after('prc_id_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn([
                'dti_sec_path',
                'bir_form_path',
                'fda_license_path',
                'prc_id_path',
                'authorization_letter_path',
            ]);
        });
    }
};
