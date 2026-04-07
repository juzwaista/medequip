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
        return [
            'kind' => 'welcome',
            'title' => 'Welcome to MedEquip',
            'body' => 'Browse verified suppliers, message sellers anytime (including product questions), track orders, and use order chat when you buy. Verify your email to checkout and use your wallet.',
            'action_href' => '/products',
        ];
    }
}
