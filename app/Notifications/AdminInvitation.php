<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminInvitation extends Notification
{
    use Queueable;

    protected $token;
    protected $email;
    protected $roleName;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email, $roleName = null)
    {
        $this->token = $token;
        $this->email = $email;
        $this->roleName = $roleName;
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
        $roleText = $this->roleName ? " with the role of **{$this->roleName}**" : "";

        return (new MailMessage)
            ->subject('MedEquip Administrative Invitation')
            ->greeting('Hello!')
            ->line("You have been invited to join the MedEquip platform as an administrator{$roleText}.")
            ->line('To complete your account setup, please click the button below.')
            ->line('**Important:** You will be required to set a strong password to activate your account upon clicking the link.')
            ->action('Set Up My Account & Password', $setupUrl)
            ->line('This invitation link will expire in 24 hours.')
            ->line('If you were not expecting this invitation, no further action is required.')
            ->salutation('Best regards, The MedEquip Team');
    }
}
