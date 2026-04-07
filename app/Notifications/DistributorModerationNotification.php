<?php

namespace App\Notifications;

use App\Models\Distributor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DistributorModerationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $kind,
        public Distributor $distributor,
        public array $meta = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $body] = $this->resolveContent();

        return [
            'kind' => $this->kind,
            'title' => $title,
            'body' => $body,
            'action_href' => '/notifications',
            'distributor_id' => $this->distributor->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$title, $body] = $this->resolveContent();
        $mail = (new MailMessage)
            ->subject($title)
            ->greeting("Hi {$notifiable->name},")
            ->line($body);

        if ($this->kind === 'distributor_approved') {
            $mail->action('Visit Distributor Dashboard', url('/owner/dashboard'));
        } elseif ($this->kind === 'distributor_rejected') {
            $reason = $this->distributor->rejection_reason;
            if ($reason) {
                $mail->line("Reason for rejection: {$reason}");
            }
            $mail->line('Please address these details and feel free to re-apply.');
        } elseif (in_array($this->kind, ['distributor_suspended', 'distributor_warned', 'distributor_banned'])) {
            $mail->line('Please log in to your dashboard to view more details regarding this action.');
        }

        return $mail->line('If you have any questions, please contact our support team.')
            ->salutation('Best regards, The MedEquip Team');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveContent(): array
    {
        $shop = $this->distributor->company_name;

        return match ($this->kind) {
            'distributor_approved' => [
                'Success! You are now a verified MedEquip Distributor',
                "Congratulations! Your application for {$shop} has been approved. You can now list your products and reach thousands of healthcare providers. Visit your Distributor Dashboard to set up your first product listing.",
            ],
            'distributor_rejected' => [
                'Update regarding your MedEquip Application',
                "Thank you for your interest in MedEquip. After reviewing your application for {$shop}, we’re unable to approve it at this time.",
            ],
            'distributor_warned' => [
                'Admin warning',
                $this->warnBody($shop),
            ],
            'distributor_suspended' => [
                'Shop suspended',
                $this->suspendBody($shop),
            ],
            'distributor_suspension_lifted' => [
                'Suspension lifted',
                "The suspension on {$shop} has been lifted. You can accept new orders again.",
            ],
            'distributor_banned' => [
                'Shop banned',
                $this->banBody($shop),
            ],
            default => [
                'Account notice',
                'Your shop account was updated by MedEquip admin.',
            ],
        };
    }

    private function warnBody(string $shop): string
    {
        $preset = $this->meta['preset'] ?? 'Policy';
        $custom = trim((string) ($this->meta['custom_message'] ?? ''));

        $base = "MedEquip admin issued a warning for {$shop}: {$preset}.";
        if ($custom !== '') {
            return $base.' '.$custom;
        }

        return $base;
    }

    private function suspendBody(string $shop): string
    {
        $days = (int) ($this->meta['days'] ?? 0);
        $reason = trim((string) ($this->meta['reason'] ?? ''));

        return 'MedEquip admin suspended '.$shop.' for '.$days.' day'.($days === 1 ? '' : 's')
            .'. New orders are blocked until the suspension ends.'
            .($reason !== '' ? ' Reason: '.$reason : '');
    }

    private function banBody(string $shop): string
    {
        $reason = trim((string) ($this->meta['reason'] ?? ''));

        return $shop.' has been permanently banned from the platform.'
            .($reason !== '' ? ' Reason: '.$reason : '');
    }
}
