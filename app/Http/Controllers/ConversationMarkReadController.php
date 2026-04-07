<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesShopConversation;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationMarkReadController extends Controller
{
    use AuthorizesShopConversation;

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeShopConversation($conversation);
        $conversation->markIncomingReadFor($request->user());

        return response()->json(['ok' => true]);
    }
}
