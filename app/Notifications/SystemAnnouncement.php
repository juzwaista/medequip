<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $adminName;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $adminName)
    {
        $this->title = $title;
        $this->message = $message;
        $this->adminName = $adminName;
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
            ->subject('System Announcement: ' . $this->title)
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('An important announcement has been posted by the MedEquip administration:')
            ->line('**' . $this->title . '**')
            ->line($this->message)
            ->action('View on Dashboard', url('/dashboard'))
            ->line('Thank you for being a part of MedEquip!')
            ->salutation('Best regards, ' . $this->adminName);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'announcement',
            'title' => $this->title,
            'message' => $this->message,
            'admin_name' => $this->adminName,
            'icon' => 'megaphone',
        ];
    }
}
