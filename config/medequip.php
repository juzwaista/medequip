<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email when someone sends a shop/order thread message
    |--------------------------------------------------------------------------
    |
    | Database notifications are always sent to the recipient. Set true to
    | also queue a mail (configure MAIL_* in .env).
    |
    */
    'chat_notify_mail' => (bool) env('CHAT_NOTIFY_MAIL', false),

    /*
    |--------------------------------------------------------------------------
    | COD — disable when customer rejection rate exceeds this percent
    |--------------------------------------------------------------------------
    |
    | Rejection rate = (Rx-rejected orders + status=rejected orders) / total orders.
    |
    */
    'cod_rejection_threshold_percent' => (float) env('COD_REJECTION_THRESHOLD_PERCENT', 15),

    /*
    |--------------------------------------------------------------------------
    | Real-time Broadcasting (WebSockets)
    |--------------------------------------------------------------------------
    |
    | Current state: polling (chat 12s, notifications 45s, dashboard 30s, cart 10s).
    |
    | Recommended upgrade path: Laravel Reverb + Echo.
    |   - Reverb is Laravel's first-party WebSocket server (zero external deps).
    |   - Replace polling in: OrderChatPanel, NotificationBell, OwnerDashboard, CartCount.
    |   - Broadcast events: NewChatMessage, NotificationCreated, OrderStatusChanged.
    |   - Estimated effort: 4-8 hours for initial integration.
    |   - Set BROADCAST_CONNECTION=reverb in .env when ready.
    |
    | Toggle to enable broadcasting (requires Reverb server running):
    |
    */
    'broadcasting_enabled' => (bool) env('BROADCASTING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Chat presence (last seen / online)
    |--------------------------------------------------------------------------
    |
    | "Online" when last_seen_at is within this many seconds of now.
    | last_seen_at is updated at most once per touch interval on Inertia navigations.
    |
    */
    'presence_online_seconds' => (int) env('PRESENCE_ONLINE_SECONDS', 120),

    'last_seen_touch_interval_seconds' => (int) env('LAST_SEEN_TOUCH_INTERVAL_SECONDS', 60),

];
