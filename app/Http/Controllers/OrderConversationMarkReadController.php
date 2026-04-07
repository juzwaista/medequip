<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesOrderChat;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderConversationMarkReadController extends Controller
{
    use AuthorizesOrderChat;

    public function store(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderChatParticipant($order);

        $conversation = $order->getShopConversationForOrder();
        if ($conversation) {
            $conversation->markIncomingReadFor($request->user());
        }

        return response()->json(['ok' => true]);
    }
}
