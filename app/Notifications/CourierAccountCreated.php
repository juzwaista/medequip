<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourierAccountCreated extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
                    ->subject('Welcome to the MedEquip Delivery Team!')
                    ->greeting('Hi ' . $notifiable->name . '!')
                    ->line('Your courier account is ready to go. You can now log in to the delivery dashboard to view available routes and manage your shipments.')
                    ->line('**Your Username:** ' . $notifiable->username)
                    ->line('Before you get started, please set your password by clicking the button below:')
                    ->action('Set Password & Activate Account', $url)
                    ->line('Once activated, you can always find your dashboard at: ' . url('/courier/dashboard'))
                    ->line('Need help? Visit our [Courier FAQ & Support](/help/courier) for tips on getting started.')
                    ->salutation('Welcome to the team, The MedEquip Fleet');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
