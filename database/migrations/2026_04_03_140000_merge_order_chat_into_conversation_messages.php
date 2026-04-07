<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conversation_messages', 'order_id')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->foreignId('order_id')->nullable()->after('conversation_id')->constrained()->nullOnDelete();
                $table->string('kind', 24)->default('user')->after('user_id');
                $table->json('meta')->nullable()->after('image_path');
            });
        }

        Schema::dropIfExists('conversation_message_reports');
        Schema::create('conversation_message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_message_id')->constrained('conversation_messages')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 64);
            $table->text('details')->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamps();

            $table->unique(['conversation_message_id', 'reporter_id'], 'cmr_msg_reporter_uq');
        });

        if (! Schema::hasTable('order_messages')) {
            Schema::dropIfExists('order_message_reports');

            return;
        }

        $idMap = [];

        foreach (DB::table('order_messages')->orderBy('id')->cursor() as $om) {
            $order = DB::table('orders')->where('id', $om->order_id)->first();
            if (! $order) {
                continue;
            }

            $convId = DB::table('conversations')
                ->where('customer_id', $order->customer_id)
                ->where('distributor_id', $order->distributor_id)
                ->value('id');

            if (! $convId) {
                $now = now();
                $convId = DB::table('conversations')->insertGetId([
                    'customer_id' => $order->customer_id,
                    'distributor_id' => $order->distributor_id,
                    'context_product_id' => null,
                    'last_message_at' => $om->created_at ?? $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $metaVal = null;
            if ($om->meta !== null && $om->meta !== '') {
                $metaVal = is_string($om->meta) ? $om->meta : json_encode($om->meta);
            }

            $newId = DB::table('conversation_messages')->insertGetId([
                'conversation_id' => $convId,
                'order_id' => $om->order_id,
                'user_id' => $om->user_id,
                'kind' => $om->kind ?? 'user',
                'body' => $om->body,
                'image_path' => $om->image_path,
                'meta' => $metaVal,
                'created_at' => $om->created_at ?? now(),
                'updated_at' => $om->updated_at ?? now(),
            ]);

            $idMap[(int) $om->id] = $newId;

            $lastAt = $om->created_at ?? now();
            DB::table('conversations')->where('id', $convId)->update([
                'last_message_at' => $lastAt,
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('order_message_reports')) {
            foreach (DB::table('order_message_reports')->cursor() as $rep) {
                $newMsgId = $idMap[(int) $rep->order_message_id] ?? null;
                if (! $newMsgId) {
                    continue;
                }
                DB::table('conversation_message_reports')->insert([
                    'conversation_message_id' => $newMsgId,
                    'reporter_id' => $rep->reporter_id,
                    'reason' => $rep->reason,
                    'details' => $rep->details,
                    'status' => $rep->status ?? 'open',
                    'created_at' => $rep->created_at ?? now(),
                    'updated_at' => $rep->updated_at ?? now(),
                ]);
            }
        }

        Schema::dropIfExists('order_message_reports');
        Schema::dropIfExists('order_messages');
    }

    public function down(): void
    {
        //
    }
};
