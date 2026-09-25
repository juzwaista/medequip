<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOTP extends Notification
{
    use Queueable;

    protected $otp;

    /**
     * @param  string  $purpose  'login' (admin login code) or 'email_change' (confirming a new
     *                           email address; sent to an anonymous mail route, so it has no
     *                           user name and no database channel).
     */
    public function __construct($otp, protected string $purpose = 'login')
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->purpose === 'email_change' ? ['mail'] : ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->purpose === 'email_change') {
            return (new MailMessage)
                ->subject('MedEquip: Confirm Your New Email Address')
                ->greeting('Hello!')
                ->line('You asked to change the email address on your MedEquip account to this one.')
                ->line('Please use the following verification code to confirm the change:')
                ->line('**' . $this->otp . '**')
                ->line('This code is valid for **15 minutes**. If you did not request this change, you can ignore this email.')
                ->salutation('Stay secure, The MedEquip Security Team');
        }

        return (new MailMessage)
            ->subject('MedEquip Security: Your Login Verification Code')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('You are receiving this email because a login attempt was made for your administrative account on MedEquip.')
            ->line('Please use the following verification code to complete your login:')
            ->line('**' . $this->otp . '**')
            ->line('This code is valid for **15 minutes**. If it expires, you will need to request a new one.')
            ->line('If you did not attempt to log in, please secure your account immediately or contact support.')
            ->salutation('Stay secure,  
The MedEquip Security Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Login attempt detected',
            'message' => 'Your security verification code is ' . $this->otp,
            'type' => 'security',
        ];
    }
}
