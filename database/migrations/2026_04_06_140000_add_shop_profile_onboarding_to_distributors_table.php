<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->timestamp('shop_profile_onboarding_completed_at')->nullable()->after('chat_auto_reply');
        });

        DB::table('distributors')
            ->where('status', 'approved')
            ->whereNull('shop_profile_onboarding_completed_at')
            ->update(['shop_profile_onboarding_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn('shop_profile_onboarding_completed_at');
        });
    }
};
