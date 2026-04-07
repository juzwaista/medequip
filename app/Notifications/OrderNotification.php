<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $kind,
        public array $extra = [],
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
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'action_href' => $this->resolveActionHref($notifiable),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveContent(): array
    {
        $num = $this->order->order_number;
        $shop = $this->extra['shop_name'] ?? 'the seller';
        $amt = $this->extra['amount'] ?? $this->order->total_amount;
        $reason = $this->extra['reason'] ?? '';

        return match ($this->kind) {
            'order_placed' => [
                'New order received',
                "Order {$num} has been placed (\u{20B1}".number_format((float) $amt, 2).'). Review and process it.',
            ],
            'order_requires_prescription' => [
                'Prescription required',
                "Order {$num} contains items that need a prescription. Tap here to upload your prescription photo.",
            ],
            'prescription_uploaded' => [
                'Prescription submitted',
                "A customer uploaded a prescription for order {$num}. Please review it.",
            ],
            'prescription_approved' => [
                'Prescription approved',
                "Your prescription for order {$num} was approved by {$shop}.",
            ],
            'prescription_rejected' => [
                'Prescription not accepted',
                "Prescription for order {$num} was not accepted".($reason !== '' ? ": {$reason}" : '.'),
            ],
            'order_accepted' => [
                'Order accepted',
                "Order {$num} has been accepted by {$shop}. It will be prepared for shipping.",
            ],
            'order_rejected' => [
                'Order rejected',
                "Order {$num} was rejected by {$shop}.",
            ],
            'order_packed' => [
                'Order packed',
                "Order {$num} is packed and waiting for courier pickup.",
            ],
            'order_shipped' => [
                'Order on the way',
                "Order {$num} is on the way! Your package has been picked up by the courier.",
            ],
            'order_delivered' => [
                'Order delivered',
                "Order {$num} has been delivered. Please confirm receipt to complete your order.",
            ],
            'order_cancelled' => [
                'Order cancelled',
                "Order {$num} has been cancelled.",
            ],
            'payment_confirmed' => [
                'Payment confirmed',
                "Your payment of \u{20B1}".number_format((float) $amt, 2)." for order {$num} has been confirmed.",
            ],
            'order_completed' => [
                'Order completed',
                "Customer confirmed receipt of order {$num}. Payment held by the platform has been released to your wallet.",
            ],
            'review_prompt' => [
                'Rate your order',
                "How was your experience with order {$num}? Tap to leave a rating.",
            ],
            default => [
                'Order update',
                "Order {$num} has been updated.",
            ],
        };
    }

    private function resolveActionHref(object $notifiable): string
    {
        $role = $notifiable->role ?? 'customer';
        $orderId = $this->order->id;

        if ($this->kind === 'order_requires_prescription') {
            return "/orders/{$orderId}";
        }

        if (in_array($role, ['distributor', 'staff'], true)) {
            return "/owner/orders/{$orderId}";
        }

        if ($role === 'courier') {
            return '/courier/deliveries';
        }

        return "/orders/{$orderId}";
    }
}
