<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesShopConversation;
use App\Http\Controllers\Concerns\SerializesConversationMessages;
use App\Models\Conversation;
use App\Rules\SafeUpload;
use App\Services\ChatMessageNotifier;
use App\Services\ContentModerationService;
use App\Services\ShopConversationAutoReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShopConversationMessageController extends Controller
{
    use AuthorizesShopConversation;
    use SerializesConversationMessages;

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeShopConversation($conversation);

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

    public function store(Request $request, Conversation $conversation, ContentModerationService $moderation, ChatMessageNotifier $notifier, ShopConversationAutoReplyService $autoReply): JsonResponse
    {
        $this->authorizeShopConversation($conversation);

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

        $imagePath = null;
        if ($hasImage) {
            $imagePath = $request->file('image')->store("shop-chat/{$conversation->id}", 'public');
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'kind' => 'user',
            'order_id' => null,
            'body' => $body === '' && $hasImage ? '' : $body,
            'image_path' => $imagePath,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('user:id,name,role');
        $conversation->loadMissing('distributor:id,user_id,company_name,slug');

        $notifier->notify($conversation, $message, $request->user());

        $autoReply->trySendFirstCustomerAutoReply($conversation, $request->user());

        return response()->json([
            'message' => $this->serializeConversationMessage($message, $conversation),
            'content_censored' => $contentCensored,
        ], 201);
    }
}
