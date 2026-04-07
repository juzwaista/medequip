<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesOrderChat;
use App\Models\ConversationMessage;
use App\Models\ConversationMessageReport;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderMessageReportController extends Controller
{
    use AuthorizesOrderChat;

    public function store(Request $request, Order $order, ConversationMessage $conversation_message): JsonResponse
    {
        $this->authorizeOrderChatParticipant($order);

        $conversation = $order->getShopConversationForOrder();
        if (! $conversation || (int) $conversation_message->conversation_id !== (int) $conversation->id) {
            abort(404);
        }

        if ($conversation_message->user_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot report your own message.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,harassment,inappropriate,scam,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        ConversationMessageReport::query()->updateOrCreate(
            [
                'conversation_message_id' => $conversation_message->id,
                'reporter_id' => $request->user()->id,
            ],
            [
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'status' => 'open',
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Thanks — our team will review this.']);
    }
}
