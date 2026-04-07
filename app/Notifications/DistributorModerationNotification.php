<?php

namespace App\Notifications;

use App\Models\Distributor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
        return ['database'];
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

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveContent(): array
    {
        $shop = $this->distributor->company_name;

        return match ($this->kind) {
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
                'Your shop account was updated by MedEquip admin. Open notifications for details.',
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
