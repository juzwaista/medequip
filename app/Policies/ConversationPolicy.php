<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return null;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    private function isParticipant(User $user, Conversation $conversation): bool
    {
        if ($user->id === $conversation->customer_id) {
            return true;
        }

        $distributorId = $conversation->distributor_id;

        if ($user->role === 'distributor' && $user->distributor?->id === $distributorId) {
            return true;
        }

        if ($user->role === 'staff' && $user->employer?->id === $distributorId) {
            return true;
        }

        return false;
    }
}
