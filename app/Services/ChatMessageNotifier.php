<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;

class ChatMessageNotifier
{
    public function notify(Conversation $conversation, ConversationMessage $message, User $sender): void
    {
        if (($message->kind ?? 'user') !== 'user') {
            return;
        }

        $recipient = $this->resolveRecipient($conversation, $sender);
        if (! $recipient || (int) $recipient->id === (int) $sender->id) {
            return;
        }

        $recipient->notify(new NewChatMessageNotification(
            conversation: $conversation,
            message: $message,
            sender: $sender,
        ));
    }

    protected function resolveRecipient(Conversation $conversation, User $sender): ?User
    {
        if ((int) $sender->id === (int) $conversation->customer_id) {
            $conversation->loadMissing('distributor.user');

            return $conversation->distributor?->user;
        }

        return User::query()->find($conversation->customer_id);
    }
}
