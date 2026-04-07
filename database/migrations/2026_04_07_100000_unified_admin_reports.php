<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_message_reports', function (Blueprint $table) {
            $table->string('resolution_action', 64)->nullable()->after('admin_notes');
            $table->text('resolution_notes')->nullable()->after('resolution_action');
        });

        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 64);
            $table->text('details')->nullable();
            $table->string('status', 32)->default('open');
            $table->text('admin_notes')->nullable();
            $table->string('resolution_action', 64)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('courier_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 64);
            $table->text('details')->nullable();
            $table->string('status', 32)->default('open');
            $table->text('admin_notes')->nullable();
            $table->string('resolution_action', 64)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::table('delivery_reviews', function (Blueprint $table) {
            $table->timestamp('admin_cleared_at')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_reviews', function (Blueprint $table) {
            $table->dropColumn('admin_cleared_at');
        });

        Schema::dropIfExists('courier_reports');
        Schema::dropIfExists('user_reports');

        Schema::table('conversation_message_reports', function (Blueprint $table) {
            $table->dropColumn(['resolution_action', 'resolution_notes']);
        });
    }
};
