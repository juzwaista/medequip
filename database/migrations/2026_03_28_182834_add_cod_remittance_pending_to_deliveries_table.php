<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            // Courier marks that they've sent the cash to the distributor
            $table->timestamp('cod_remittance_sent_at')->nullable()->after('cod_remitted_at');
        });

        // Also add 'completed' to orders status if not already handled via ENUM
        // (Orders use a string column, no change needed)
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('cod_remittance_sent_at');
        });
    }
};
