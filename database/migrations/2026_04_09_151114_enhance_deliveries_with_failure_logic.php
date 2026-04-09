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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->integer('attempts_count')->default(0)->after('status');
            $table->timestamp('last_attempt_at')->nullable()->after('attempts_count');
            $table->string('failure_reason')->nullable()->after('last_attempt_at');
            $table->text('failure_note')->nullable()->after('failure_reason');
            $table->boolean('is_return_to_sender')->default(false)->after('failure_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['attempts_count', 'last_attempt_at', 'failure_reason', 'failure_note', 'is_return_to_sender']);
        });
    }
};
