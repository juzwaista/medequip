<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;
    protected $email;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
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
        $setupUrl = url('/admin/setup?token=' . $this->token . '&email=' . urlencode($this->email));

        return (new MailMessage)
            ->subject('MedEquip Administrative Invitation')
            ->greeting('Hello!')
            ->line('You have been invited to join the MedEquip platform as an administrator.')
            ->line('To complete your account setup, please click the button below to verify your email and set your password.')
            ->action('Set Up My Account', $setupUrl)
            ->line('This invitation link will expire in 24 hours.')
            ->line('If you were not expecting this invitation, no further action is required.')
            ->salutation('Best regards, The MedEquip Team');
    }
}
