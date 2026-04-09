<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $expiringDocs;
    private $distributor;

    public function __construct($distributor, array $expiringDocs)
    {
        $this->distributor = $distributor;
        $this->expiringDocs = $expiringDocs;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject('Urgent: Document Expiration Alert - MedEquip')
            ->greeting('Hello ' . $this->distributor->company_name . ',')
            ->line('One or more of your compliance documents are expiring soon or have already expired.')
            ->line('Maintaining valid documents is required to continue operating on the MedEquip platform.');

        foreach ($this->expiringDocs as $doc) {
            $message->line("- **{$doc['label']}**: Expires on {$doc['expiry']->format('M d, Y')}");
        }

        return $message
            ->action('Update Documents', route('owner.profile.edit'))
            ->line('Please upload updated documents as soon as possible to avoid service disruption.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'document_expiry',
            'message' => count($this->expiringDocs) . ' documents are expiring soon.',
            'expiring_docs' => collect($this->expiringDocs)->map(fn($d) => [
                'label' => $d['label'],
                'expiry' => $d['expiry']->toDateString(),
            ]),
            'action_url' => route('owner.profile.edit'),
        ];
    }
}
