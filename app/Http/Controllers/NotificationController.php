<?php

namespace App\Http\Controllers;

use App\Services\UnreadConversationMessageService;
use App\Support\NotificationFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $notifications = NotificationFilters::excludeNewChatMessages(
            $request->user()->notifications()
        )
            ->paginate(25)
            ->through(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
                'data' => $n->data,
            ]);

        if ($this->wantsJsonWithoutInertia($request)) {
            return response()->json([
                'notifications' => $notifications,
            ]);
        }

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => NotificationFilters::excludeNewChatMessages($user->unreadNotifications())->count(),
            'unread_chat_count' => UnreadConversationMessageService::countFor($user),
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->ensureOwnNotification($request, $notification);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        if ($this->wantsJsonWithoutInertia($request)) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($this->wantsJsonWithoutInertia($request)) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Inertia visits are XHR and look "ajax", but must receive a redirect/Inertia response — not bare JSON.
     */
    private function wantsJsonWithoutInertia(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->ajax() || $request->wantsJson();
    }

    protected function ensureOwnNotification(Request $request, DatabaseNotification $notification): void
    {
        $user = $request->user();
        if ((int) $notification->notifiable_id !== (int) $user->id
            || $notification->notifiable_type !== $user->getMorphClass()) {
            abort(403);
        }
    }
}
