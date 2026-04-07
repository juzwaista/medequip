<?php

namespace App\Http\Middleware;

use App\Models\ConversationMessageReport;
use App\Models\CourierReport;
use App\Models\DeliveryReview;
use App\Models\UserReport;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $interval = max(10, (int) config('medequip.last_seen_touch_interval_seconds', 60));
            $last = $user->last_seen_at;
            if ($last === null || $last->lt(now()->subSeconds($interval))) {
                \App\Models\User::query()->whereKey($user->id)->update(['last_seen_at' => now()]);
                $user->setAttribute('last_seen_at', now());
            }
        }
        $distributorStatus = null;
        $suspendedUntil = null;
        $suspensionReason = null;
        $warningReason = null;
        $warningMessage = null;
        if ($user && $user->role === 'distributor') {
            $distributorStatus = $user->distributor?->status;
            $suspendedUntil = $user->distributor?->suspended_until?->timestamp;
            $suspensionReason = $user->distributor?->suspension_reason;
            $warningReason = $user->distributor?->warning_reason;
            $warningMessage = $user->distributor?->warning_message;
        }

        return array_merge(parent::share($request), [
            // Fresh token on every Inertia response so axios / meta stay in sync after navigations.
            'csrf_token' => fn () => csrf_token(),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'role' => $user->role,
                    'is_suspended' => $user->distributor?->is_suspended ?? false,
                    'distributor_status' => $distributorStatus,
                    'suspended_until' => $suspendedUntil,
                    'suspension_reason' => $suspensionReason,
                    'warning_reason' => $warningReason,
                    'warning_message' => $warningMessage,
                ] : null,
            ],
            'needsTermsAcceptance' => $user ? ! $user->hasAcceptedTerms() : false,
            'unread_notifications_count' => fn () => $user
                ? $user->unreadNonChatNotificationsCount()
                : 0,
            'unread_chat_messages_count' => fn () => $user
                ? \App\Services\UnreadConversationMessageService::countFor($user)
                : 0,
            'open_message_reports_count' => fn () => $user && in_array($user->role, ['admin', 'super_admin'], true)
                ? ConversationMessageReport::query()->where('status', 'open')->count()
                : 0,
            'pending_verifications_count' => fn () => $user && in_array($user->role, ['admin', 'super_admin'], true)
                ? \App\Models\Distributor::where('status', 'pending')->count()
                : 0,
            'open_reports_hub_count' => fn () => $user && in_array($user->role, ['admin', 'super_admin'], true)
                ? ConversationMessageReport::query()->where('status', 'open')->count()
                    + UserReport::query()->where('status', 'open')->count()
                    + CourierReport::query()->where('status', 'open')->count()
                    + DeliveryReview::query()->where('stars', '<=', 2)->whereNull('admin_cleared_at')->count()
                    + \App\Models\ProductReport::query()->where('status', 'open')->count()
                : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
                'status' => fn () => $request->session()->get('status'),
            ],
        ]);
    }
}
