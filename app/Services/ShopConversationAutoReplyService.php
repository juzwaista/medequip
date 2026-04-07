<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Distributor;
use App\Models\User;

class ShopConversationAutoReplyService
{
    /**
     * Send the distributor's chat_auto_reply once, when the customer has exactly one
     * user message and the shop has not sent any message yet.
     */
    public function trySendFirstCustomerAutoReply(Conversation $conversation, User $sender): void
    {
        if ((int) $sender->id !== (int) $conversation->customer_id) {
            return;
        }

        $customerUserMessages = $conversation->messages()
            ->where('user_id', $conversation->customer_id)
            ->where('kind', 'user')
            ->count();

        if ($customerUserMessages !== 1) {
            return;
        }

        $shopHasSpoken = $conversation->messages()
            ->where('user_id', '!=', $conversation->customer_id)
            ->exists();

        if ($shopHasSpoken) {
            return;
        }

        $distributor = Distributor::query()->find($conversation->distributor_id);
        if (! $distributor) {
            return;
        }

        $template = $distributor->chat_auto_reply;
        if (! $template || trim($template) === '') {
            return;
        }

        $body = str_replace(
            ['{shop_name}', '{customer_name}'],
            [$distributor->company_name, $sender->name],
            $template
        );

        $conversation->messages()->create([
            'user_id' => $distributor->user_id,
            'kind' => 'system',
            'order_id' => null,
            'body' => $body,
            'image_path' => null,
        ]);

        $conversation->update(['last_message_at' => now()]);
    }
}
