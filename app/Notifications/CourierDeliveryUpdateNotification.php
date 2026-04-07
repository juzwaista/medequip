<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourierDeliveryUpdateNotification extends Notification
{
    use Queueable;

    public Delivery $delivery;
    public string $actionType;

    /**
     * Create a new notification instance.
     * actionType can be: 'accepted', 'started_pickup', 'cancelled'
     */
    public function __construct(Delivery $delivery, string $actionType)
    {
        $this->delivery = $delivery;
        $this->actionType = $actionType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $order = $this->delivery->order;
        $courierName = collect([
            $this->delivery->courier?->user?->first_name,
            $this->delivery->courier?->user?->last_name
        ])->filter()->join(' ') ?: 'A courier';

        $subject = match ($this->actionType) {
            'accepted' => "Courier accepted your order #{$order->order_number}",
            'started_pickup' => "Courier is on the way for order #{$order->order_number}",
            'cancelled' => "Courier cancelled pickup for order #{$order->order_number}",
            default => "Courier update for order #{$order->order_number}",
        };

        $line1 = match ($this->actionType) {
            'accepted' => "{$courierName} has accepted the delivery job for order #{$order->order_number}.",
            'started_pickup' => "{$courierName} has started heading to your location to pick up order #{$order->order_number}.",
            'cancelled' => "{$courierName} has cancelled their delivery job for order #{$order->order_number}. The order will be placed back into the dispatch pool.",
            default => "There is an update from the courier regarding order #{$order->order_number}.",
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello,')
            ->line($line1)
            ->action('View Order', url("/owner/orders/{$order->id}"))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        $order = $this->delivery->order;
        $courierName = collect([
            $this->delivery->courier?->user?->first_name,
            $this->delivery->courier?->user?->last_name
        ])->filter()->join(' ') ?: 'A courier';

        $title = match ($this->actionType) {
            'accepted' => 'Courier Accepted Request',
            'started_pickup' => 'Courier is On the Way',
            'cancelled' => 'Courier Cancelled Job',
            default => 'Courier Update',
        };

        $message = match ($this->actionType) {
            'accepted' => "{$courierName} accepted order #{$order->order_number}",
            'started_pickup' => "{$courierName} is heading to pick up order #{$order->order_number}",
            'cancelled' => "{$courierName} cancelled order #{$order->order_number}",
            default => "Update for order #{$order->order_number}",
        };

        return [
            'type' => 'courier_update',
            'title' => $title,
            'message' => $message,
            'order_id' => $order->id,
            'delivery_id' => $this->delivery->id,
            'action_type' => $this->actionType,
        ];
    }
}
