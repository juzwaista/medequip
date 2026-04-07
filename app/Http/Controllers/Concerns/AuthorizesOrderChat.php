<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;

trait AuthorizesOrderChat
{
    protected function authorizeOrderChatParticipant(Order $order): void
    {
        $user = auth()->user();

        if ($order->customer_id === $user->id) {
            return;
        }

        $distributor = $this->getDistributor();
        if ($distributor && (int) $order->distributor_id === (int) $distributor->id) {
            return;
        }

        abort(403);
    }
}
