<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactFormSubmission extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contactData;

    /**
     * Create a new notification instance.
     */
    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Contact message from: ' . $this->contactData['name'])
            ->greeting('Hello, Admin!')
            ->line('A new message has been submitted via the contact form on MedEquip.')
            ->line('**From:** ' . $this->contactData['name'] . ' (' . $this->contactData['email'] . ')')
            ->line('**Message:**')
            ->line($this->contactData['message'])
            ->action('View User Info', url('/admin/users?search=' . $this->contactData['email']))
            ->line('Please follow up with this user at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contact_submission',
            'name' => $this->contactData['name'],
            'email' => $this->contactData['email'],
            'message' => $this->contactData['message'],
            'icon' => 'envelope',
        ];
    }
}
