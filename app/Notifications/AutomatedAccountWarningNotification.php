<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AutomatedAccountWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $level,
        public array $reasons
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'account_warning',
            'title' => 'Automated Warning: ' . $this->level . ' Risk',
            'body' => 'Your account has been flagged for the following reasons: ' . implode(', ', $this->reasons) . '. Please resolve these to avoid account suspension.',
            'action_href' => '/owner/orders',
        ];
    }
}
