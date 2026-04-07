<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WelcomeToMedequip extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $firstName = explode(' ', $notifiable->name)[0];
        return [
            'kind' => 'welcome',
            'title' => "Welcome aboard, {$firstName}! Let’s get started.",
            'body' => 'We’re glad to have you! Explore verified medical suppliers and message sellers directly. To unlock your wallet and start shopping, please verify your email.',
            'action_href' => '/products',
        ];
    }
}
