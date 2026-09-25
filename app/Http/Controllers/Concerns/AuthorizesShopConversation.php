<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Conversation;

trait AuthorizesShopConversation
{
    /**
     * Delegates to ConversationPolicy::view() rather than duplicating the
     * customer/shop-member participant check here.
     */
    protected function authorizeShopConversation(Conversation $conversation): void
    {
        $this->authorize('view', $conversation);
    }

    protected function userWorksForDistributorId(?\App\Models\Distributor $distributor, int $distributorId): bool
    {
        return $distributor && (int) $distributor->id === (int) $distributorId;
    }
}
