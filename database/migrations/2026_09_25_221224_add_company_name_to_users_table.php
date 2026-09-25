<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distributors give their company name at registration, before a Distributor record exists.
     * It used to be discarded, so the distributor application asked for it again. Keeping it on
     * the user lets that application form prefill it.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'company_name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'company_name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_name');
        });
    }
};
