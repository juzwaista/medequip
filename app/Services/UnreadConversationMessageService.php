<?php

namespace App\Services;

use App\Models\ConversationMessage;
use App\Models\User;

class UnreadConversationMessageService
{
    public static function countFor(User $user): int
    {
        $role = $user->role ?? '';

        if ($role === 'customer') {
            return ConversationMessage::query()
                ->whereHas('conversation', fn ($q) => $q->where('customer_id', $user->id))
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
        }

        if (in_array($role, ['distributor', 'staff'], true)) {
            $distributorId = $role === 'staff'
                ? $user->distributor_id
                : $user->distributor?->id;

            if (! $distributorId) {
                return 0;
            }

            return ConversationMessage::query()
                ->whereHas('conversation', fn ($q) => $q->where('distributor_id', $distributorId))
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
        }

        return 0;
    }
}
