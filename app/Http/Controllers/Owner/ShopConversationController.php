<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\AuthorizesShopConversation;
use App\Http\Controllers\Concerns\SerializesConversationMessages;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopConversationController extends Controller
{
    use AuthorizesShopConversation;
    use SerializesConversationMessages;

    public function index(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $distributor = $this->getDistributor();
        abort_unless($distributor, 403);

        $user = $request->user();

        $conversations = Conversation::query()
            ->where('distributor_id', $distributor->id)
            ->with([
                'customer:id,name',
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
            ->map(fn (Conversation $c) => $this->serializeConversationForOwner($c));

        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'conversations' => $conversations,
            ]);
        }

        return Inertia::render('Owner/Messages/Index', [
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

        return Inertia::render('Owner/Messages/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'customer' => [
                    'name' => $conversation->customer?->name,
                ],
                'shop' => [
                    'company_name' => $conversation->distributor?->company_name,
                    'slug' => $conversation->distributor?->slug,
                ],
                'context_product' => $conversation->contextProduct
                    ? ['id' => $conversation->contextProduct->id, 'name' => $conversation->contextProduct->name]
                    : null,
            ],
            'viewerRole' => $isCustomer ? 'customer' : 'distributor',
            'fetchUrl' => route('owner.messages.messages.index', $conversation),
            'postUrl' => route('owner.messages.messages.store', $conversation),
            'markReadUrl' => route('owner.messages.mark-read', $conversation),
            'counterpart_presence' => $presence['counterpart_presence'],
            'counterpart_last_seen_at' => $presence['counterpart_last_seen_at'],
        ]);
    }

    protected function serializeConversationForOwner(Conversation $c): array
    {
        $preview = '';
        if ($c->latestMessage) {
            $preview = $c->latestMessage->body !== ''
                ? str($c->latestMessage->body)->limit(120)->toString()
                : ($c->latestMessage->image_path ? '[Image]' : '');
        }

        return [
            'id' => $c->id,
            'customer' => [
                'name' => $c->customer?->name,
            ],
            'context_product' => $c->contextProduct
                ? ['id' => $c->contextProduct->id, 'name' => $c->contextProduct->name]
                : null,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'preview' => $preview,
            'unread_count' => (int) ($c->unread_count ?? 0),
            'fetchUrl' => route('owner.messages.messages.index', $c->id),
            'postUrl' => route('owner.messages.messages.store', $c->id),
            'markReadUrl' => route('owner.messages.mark-read', $c->id),
            'viewerRole' => 'distributor',
            'has_action_required' => \App\Models\Order::query()
                ->where('customer_id', $c->customer_id)
                ->where('distributor_id', $c->distributor_id)
                ->where('prescription_status', 'pending_review')
                ->exists(),
        ];
    }
}
