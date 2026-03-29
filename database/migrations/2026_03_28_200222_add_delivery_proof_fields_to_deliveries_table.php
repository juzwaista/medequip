<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->timestamp('delivery_scanned_at')->nullable()->after('pickup_confirmed_at');
            $table->string('proof_photo')->nullable()->after('delivery_scanned_at');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['delivery_scanned_at', 'proof_photo']);
        });
    }
};
