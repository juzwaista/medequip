<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesShopConversation;
use App\Http\Controllers\Concerns\SerializesConversationMessages;
use App\Models\Conversation;
use App\Models\Distributor;
use App\Models\Product;
use App\Services\ChatMessageNotifier;
use App\Services\ShopConversationAutoReplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopConversationController extends Controller
{
    use AuthorizesShopConversation;
    use SerializesConversationMessages;

    public function start(Request $request, ChatMessageNotifier $notifier, ShopConversationAutoReplyService $autoReply): RedirectResponse
    {
        $data = $request->validate([
            'distributor_id' => ['required', 'integer', 'exists:distributors,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $distributor = Distributor::query()
            ->where('id', $data['distributor_id'])
            ->where('status', 'approved')
            ->firstOrFail();

        if ($distributor->is_suspended) {
            return redirect()->back()->with('error', 'This shop is not accepting messages right now.');
        }

        $user = $request->user();
        $myDistributor = $user->role === 'staff' ? $user->employer : $user->distributor;
        if ($this->userWorksForDistributorId($myDistributor, (int) $distributor->id)) {
            return redirect()->back()->with('error', 'You cannot message your own shop.');
        }

        $productId = $data['product_id'] ?? null;
        if ($productId !== null) {
            Product::query()
                ->where('id', $productId)
                ->where('distributor_id', $distributor->id)
                ->where('is_active', true)
                ->firstOrFail();
        }

        $conversation = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $user->id,
                'distributor_id' => $distributor->id,
            ],
            [
                'context_product_id' => $productId,
            ]
        );

        if ($productId !== null && $conversation->context_product_id === null) {
            $conversation->update(['context_product_id' => $productId]);
        }

        if ($productId !== null) {
            $productPathMarker = '/products/'.$productId;
            $alreadyInquired = $conversation->messages()
                ->where('kind', 'user')
                ->where('body', 'like', '%'.$productPathMarker.'%')
                ->exists();

            if (! $alreadyInquired) {
                $product = Product::query()
                    ->with(['images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
                    ->where('id', $productId)
                    ->where('distributor_id', $distributor->id)
                    ->firstOrFail();

                $productUrl = url('/products/'.$product->id);
                $body = "I'm interested in this product:\n{$product->name}\n{$productUrl}";

                $imagePath = $product->image_path;
                if (! $imagePath && $product->relationLoaded('images')) {
                    $imagePath = $product->images->first()?->image_path;
                }

                $message = $conversation->messages()->create([
                    'user_id' => $user->id,
                    'kind' => 'user',
                    'order_id' => null,
                    'body' => $body,
                    'image_path' => $imagePath,
                ]);

                $conversation->update(['last_message_at' => now()]);
                $message->load('user:id,name,role');
                $notifier->notify($conversation, $message, $user);
                $autoReply->trySendFirstCustomerAutoReply($conversation, $user);
            }
        }

        return redirect()->route('messages.show', $conversation);
    }

    public function index(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where('customer_id', $user->id)
            ->with([
                'distributor:id,company_name,slug',
                'contextProduct:id,name',
                'latestMessage',
            ])
            ->withCount([
                'messages as unread_count' => function ($q) use ($user) {
                    $q->where('user_id', '!=', $user->id)->whereNull('read_at');
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Conversation $c) => $this->serializeConversationForCustomer($c));

        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'conversations' => $conversations,
            ]);
        }

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $this->authorizeShopConversation($conversation);

        $conversation->load([
            'distributor:id,company_name,slug,user_id',
            'distributor.user:id,last_seen_at',
            'contextProduct:id,name',
            'customer:id,name,last_seen_at',
        ]);

        $user = $request->user();
        $isCustomer = (int) $conversation->customer_id === (int) $user->id;
        $presence = $this->counterpartPresencePayload($conversation);

        return Inertia::render('Messages/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'shop' => [
                    'company_name' => $conversation->distributor?->company_name,
                    'slug' => $conversation->distributor?->slug,
                ],
                'context_product' => $conversation->contextProduct
                    ? ['id' => $conversation->contextProduct->id, 'name' => $conversation->contextProduct->name]
                    : null,
            ],
            'viewerRole' => $isCustomer ? 'customer' : 'distributor',
            'fetchUrl' => route('messages.messages.index', $conversation),
            'postUrl' => route('messages.messages.store', $conversation),
            'markReadUrl' => route('messages.mark-read', $conversation),
            'counterpart_presence' => $presence['counterpart_presence'],
            'counterpart_last_seen_at' => $presence['counterpart_last_seen_at'],
        ]);
    }

    protected function serializeConversationForCustomer(Conversation $c): array
    {
        $preview = '';
        if ($c->latestMessage) {
            $preview = $c->latestMessage->body !== ''
                ? str($c->latestMessage->body)->limit(120)->toString()
                : ($c->latestMessage->image_path ? '[Image]' : '');
        }

        return [
            'id' => $c->id,
            'shop' => [
                'company_name' => $c->distributor?->company_name,
                'slug' => $c->distributor?->slug,
            ],
            'context_product' => $c->contextProduct
                ? ['id' => $c->contextProduct->id, 'name' => $c->contextProduct->name]
                : null,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'preview' => $preview,
            'unread_count' => (int) ($c->unread_count ?? 0),
            'fetchUrl' => route('messages.messages.index', $c->id),
            'postUrl' => route('messages.messages.store', $c->id),
            'markReadUrl' => route('messages.mark-read', $c->id),
            'viewerRole' => 'customer',
        ];
    }
}
