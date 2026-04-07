<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public ConversationMessage $message,
        public User $sender,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (config('medequip.chat_notify_mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $preview = $this->previewText();

        return (new MailMessage)
            ->subject('New message on MedEquip')
            ->line($this->sender->name.' sent you a message.')
            ->line($preview)
            ->action('Open conversation', url($this->actionHref($notifiable)));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'new_chat_message',
            'title' => 'New message',
            'body' => $this->previewText(),
            'preview' => $this->previewText(),
            'sender_name' => $this->sender->name,
            'conversation_id' => $this->conversation->id,
            'order_id' => $this->message->order_id,
            'action_href' => $this->actionHref($notifiable),
        ];
    }

    protected function previewText(): string
    {
        $body = trim((string) $this->message->body);
        if ($body !== '') {
            return str($body)->limit(140)->toString();
        }
        if ($this->message->image_path) {
            return '[Image]';
        }

        return 'New message';
    }

    protected function actionHref(object $notifiable): string
    {
        $role = $notifiable->role ?? '';

        if (in_array($role, ['distributor', 'staff'], true)) {
            return '/owner/messages/'.$this->conversation->id;
        }

        return '/messages/'.$this->conversation->id;
    }
}
