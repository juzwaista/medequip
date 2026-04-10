<?php

namespace App\Notifications;

use App\Models\DssAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DssAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DssAlert $alert
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'dss_alert',
            'alert_id' => $this->alert->id,
            'alert_type' => $this->alert->alert_type,
            'severity' => $this->alert->severity,
            'title' => $this->alert->title,
            'body' => $this->alert->message,
            'action_href' => '/owner/insights',
            'metadata' => $this->alert->metadata,
        ];
    }
}
