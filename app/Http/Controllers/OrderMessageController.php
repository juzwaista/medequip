<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesOrderChat;
use App\Http\Controllers\Concerns\SerializesConversationMessages;
use App\Models\Order;
use App\Rules\SafeUpload;
use App\Services\ChatMessageNotifier;
use App\Services\ContentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderMessageController extends Controller
{
    use AuthorizesOrderChat;
    use SerializesConversationMessages;

    public function index(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderChatParticipant($order);

        $conversation = $order->getShopConversationForOrder();
        if (! $conversation) {
            return response()->json([
                'messages' => [],
                'read_state' => [],
                'counterpart_last_seen_at' => null,
                'counterpart_presence' => '',
            ]);
        }

        $afterId = (int) $request->query('after_id', 0);

        if ($afterId > 0) {
            $messages = $conversation->messages()
                ->with([
                    'user:id,name,role',
                    'order:id,customer_id,distributor_id,prescription_status,prescription_review_note,order_number,status',
                ])
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->get();
        } else {
            $messages = $conversation->messages()
                ->with([
                    'user:id,name,role',
                    'order:id,customer_id,distributor_id,prescription_status,prescription_review_note,order_number,status',
                ])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->sortBy('id')
                ->values();
        }

        $conversation->loadMissing('distributor:id,user_id,company_name,slug');

        return response()->json(array_merge([
            'messages' => $messages->map(fn ($m) => $this->serializeConversationMessage($m, $conversation)),
            'read_state' => $this->readStateMapForViewer($conversation, (int) $request->user()->id),
        ], $this->counterpartPresencePayload($conversation)));
    }

    public function store(Request $request, Order $order, ContentModerationService $moderation, ChatMessageNotifier $notifier): JsonResponse
    {
        $this->authorizeOrderChatParticipant($order);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120', SafeUpload::image()],
        ]);

        $rawInput = trim((string) ($validated['body'] ?? ''));
        $hasImage = $request->hasFile('image');

        if ($rawInput === '' && ! $hasImage) {
            throw ValidationException::withMessages([
                'body' => ['Enter a message or attach a photo.'],
            ]);
        }

        $body = $rawInput;
        if ($rawInput !== '') {
            $mod = $moderation->process($rawInput);
            if ($mod['blocked']) {
                throw ValidationException::withMessages([
                    'body' => ['This message contains language that is not allowed on MedEquip.'],
                ]);
            }
            $body = $mod['text'];
        }

        $contentCensored = $rawInput !== '' && $body !== $rawInput;

        $conversation = $order->getOrCreateShopConversation();

        $imagePath = null;
        if ($hasImage) {
            $imagePath = $request->file('image')->store("shop-chat/{$conversation->id}", 'public');
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'kind' => 'user',
            'order_id' => $order->id,
            'body' => $body === '' && $hasImage ? '' : $body,
            'image_path' => $imagePath,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('user:id,name,role');
        $conversation->loadMissing('distributor:id,user_id,company_name,slug');

        Log::info('[OrderMessageController] Chat message posted', [
            'order_id' => $order->id,
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'has_image' => (bool) $imagePath,
        ]);

        $notifier->notify($conversation, $message, $request->user());

        return response()->json([
            'message' => $this->serializeConversationMessage($message, $conversation),
            'content_censored' => $contentCensored,
        ], 201);
    }
}
