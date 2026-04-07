<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->text('chat_template_order_accepted')->nullable()->after('auto_approve_orders');
            $table->text('chat_template_order_shipped')->nullable()->after('chat_template_order_accepted');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('chat_welcome_sent_at')->nullable()->after('received_at');
            $table->timestamp('chat_shipped_sent_at')->nullable()->after('chat_welcome_sent_at');
        });

        Schema::table('order_messages', function (Blueprint $table) {
            $table->string('kind', 24)->default('user')->after('user_id');
            $table->string('image_path')->nullable()->after('body');
            $table->json('meta')->nullable()->after('image_path');
        });

        Schema::create('order_message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_message_id')->constrained('order_messages')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 64);
            $table->text('details')->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamps();

            $table->unique(['order_message_id', 'reporter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_message_reports');

        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropColumn(['kind', 'image_path', 'meta']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['chat_welcome_sent_at', 'chat_shipped_sent_at']);
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['chat_template_order_accepted', 'chat_template_order_shipped']);
        });
    }
};
