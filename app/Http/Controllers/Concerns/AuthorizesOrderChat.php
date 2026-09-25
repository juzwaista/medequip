<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;

trait AuthorizesOrderChat
{
    /**
     * Chatting about an order requires the same standing as viewing it — delegates to
     * OrderPolicy::view() rather than duplicating the customer/shop-member check here.
     */
    protected function authorizeOrderChatParticipant(Order $order): void
    {
        $this->authorize('view', $order);
    }
}
