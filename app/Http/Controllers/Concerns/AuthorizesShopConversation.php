<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Conversation;

trait AuthorizesShopConversation
{
    protected function authorizeShopConversation(Conversation $conversation): void
    {
        $user = auth()->user();

        if ((int) $conversation->customer_id === (int) $user->id) {
            return;
        }

        $distributor = $this->getDistributor();
        if ($distributor && (int) $conversation->distributor_id === (int) $distributor->id) {
            return;
        }

        abort(403);
    }

    protected function userWorksForDistributorId(?\App\Models\Distributor $distributor, int $distributorId): bool
    {
        return $distributor && (int) $distributor->id === (int) $distributorId;
    }
}
