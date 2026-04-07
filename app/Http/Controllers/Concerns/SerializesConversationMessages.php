<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Order;
use App\Support\PublicStorageUrl;
use Carbon\Carbon;

trait SerializesConversationMessages
{
    /**
     * @return array<int, string>
     */
    protected function readStateMapForViewer(Conversation $conversation, int $userId): array
    {
        return $conversation->messages()
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->get(['id', 'read_at'])
            ->mapWithKeys(fn (ConversationMessage $m) => [
                $m->id => $m->read_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array{counterpart_last_seen_at: ?string, counterpart_presence: string}
     */
    protected function counterpartPresencePayload(Conversation $conversation): array
    {
        $lastSeen = $this->memoizedRecipientLastSeenAt($conversation);

        return [
            'counterpart_last_seen_at' => $lastSeen?->toIso8601String(),
            'counterpart_presence' => $this->formatPresenceLabel($lastSeen),
        ];
    }

    protected function formatPresenceLabel(?Carbon $lastSeen): string
    {
        if (! $lastSeen) {
            return '';
        }

        $now = now();
        $onlineSeconds = max(15, (int) config('medequip.presence_online_seconds', 120));
        if ($lastSeen->greaterThanOrEqualTo($now->copy()->subSeconds($onlineSeconds))) {
            return 'Online';
        }

        $secs = max(0, $now->getTimestamp() - $lastSeen->getTimestamp());
        if ($secs < 3600) {
            $m = max(1, (int) round($secs / 60));

            return "Active {$m} minute".($m === 1 ? '' : 's').' ago';
        }
        if ($secs < 86400) {
            $h = max(1, (int) round($secs / 3600));

            return "Active {$h} hour".($h === 1 ? '' : 's').' ago';
        }
        $d = max(1, (int) round($secs / 86400));

        return "Active {$d} day".($d === 1 ? '' : 's').' ago';
    }

    protected function recipientLastSeenAt(Conversation $conversation): ?Carbon
    {
        $conversation->loadMissing([
            'distributor.user:id,last_seen_at',
            'customer:id,last_seen_at',
        ]);
        $viewerId = (int) auth()->id();
        $isCustomerViewer = (int) $conversation->customer_id === $viewerId;

        return $isCustomerViewer
            ? $conversation->distributor?->user?->last_seen_at
            : $conversation->customer?->last_seen_at;
    }

    protected function memoizedRecipientLastSeenAt(Conversation $conversation): ?Carbon
    {
        $key = 'serializes_conversation.recipient_last_seen.'.$conversation->id;
        if (request()->attributes->has($key)) {
            return request()->attributes->get($key);
        }
        $seen = $this->recipientLastSeenAt($conversation);
        request()->attributes->set($key, $seen);

        return $seen;
    }

    protected function serializeConversationMessage(ConversationMessage $m, Conversation $conversation): array
    {
        $uid = auth()->id();
        $isMine = (int) $m->user_id === (int) $uid;
        $kind = $m->kind ?: 'user';

        $meta = is_array($m->meta) ? $m->meta : [];
        $slug = $meta['slug'] ?? null;
        $shopName = $meta['shop_name'] ?? null;

        $profileHref = null;
        if ($slug) {
            $profileHref = '/seller/'.$slug;
        } elseif ($m->user && in_array($m->user->role, ['distributor', 'staff'], true)) {
            $conversation->loadMissing('distributor');
            if ($conversation->distributor?->slug) {
                $profileHref = '/seller/'.$conversation->distributor->slug;
            }
        }

        $isPrescription = $kind === 'prescription';
        $rx = null;
        if ($isPrescription && $m->order_id) {
            $m->loadMissing('order');
            $ord = $m->order;
            $event = $meta['rx_event'] ?? 'uploaded';
            $canReview = false;
            if ($ord && $uid) {
                $user = auth()->user();
                $d = $user && $user->role === 'staff' ? $user->employer : $user?->distributor;
                $canReview = $event === 'uploaded'
                    && $ord->prescription_status === Order::PRESCRIPTION_PENDING_REVIEW
                    && $d
                    && (int) $d->id === (int) $ord->distributor_id
                    && (int) $conversation->distributor_id === (int) $ord->distributor_id
                    && (int) $conversation->customer_id === (int) $ord->customer_id;
            }
            $rx = [
                'event' => $event,
                'order_id' => $ord?->id,
                'order_number' => $ord?->order_number ?? ($meta['order_number'] ?? null),
                'prescription_status' => $ord?->prescription_status,
                'review_note' => $ord?->prescription_review_note,
                'can_review' => $canReview,
            ];
        }

        $recipientSeen = $this->memoizedRecipientLastSeenAt($conversation);
        $deliveredToRecipient = $isMine
            && $kind === 'user'
            && $recipientSeen
            && $recipientSeen->greaterThanOrEqualTo($m->created_at);

        return [
            'id' => $m->id,
            'kind' => $kind,
            'body' => trim((string) $m->body) === '' ? '' : $m->body,
            'image_url' => PublicStorageUrl::url($m->image_path),
            'created_at' => $m->created_at->toIso8601String(),
            'read_at' => $isMine ? $m->read_at?->toIso8601String() : null,
            'delivered' => $deliveredToRecipient,
            'user' => $m->user ? [
                'id' => $m->user->id,
                'name' => $m->user->name,
                'role' => $m->user->role,
            ] : null,
            'is_mine' => $isMine,
            'is_automated' => $kind === 'automated',
            'is_prescription' => $isPrescription,
            'rx' => $rx,
            'profile_href' => $profileHref,
            'shop_label' => $shopName ?? $conversation->distributor?->company_name,
        ];
    }
}
