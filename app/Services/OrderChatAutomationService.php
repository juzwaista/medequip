<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderChatAutomationService
{
    public function sendOrderAcceptedMessage(Order $order): void
    {
        if ($order->chat_welcome_sent_at !== null) {
            return;
        }

        DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->find($order->id);
            if (! $locked || $locked->chat_welcome_sent_at !== null) {
                return;
            }

            $locked->loadMissing(['distributor', 'customer']);
            $distributor = $locked->distributor;
            if (! $distributor || ! $distributor->user_id) {
                return;
            }

            $template = $distributor->chat_template_order_accepted
                ?: (string) config('order_chat.defaults.order_accepted',
                    'Hello, this is {shop_name}. Your order {order_number} has been received. Please allow 1–3 business days for preparation. Thank you for your order!');

            $body = $this->interpolate($template, $locked, $distributor->company_name ?? 'Shop');

            $conversation = $locked->getOrCreateShopConversation();

            $conversation->messages()->create([
                'user_id' => $distributor->user_id,
                'kind' => 'automated',
                'order_id' => $locked->id,
                'body' => $body,
                'meta' => [
                    'automated_event' => 'order_accepted',
                    'shop_name' => $distributor->company_name,
                    'slug' => $distributor->slug,
                ],
            ]);

            $conversation->update(['last_message_at' => now()]);

            $locked->forceFill(['chat_welcome_sent_at' => now()])->save();

            Log::info('[OrderChatAutomation] Order accepted template posted', ['order_id' => $locked->id]);
        });
    }

    public function sendOrderShippedMessage(Order $order): void
    {
        if ($order->chat_shipped_sent_at !== null) {
            return;
        }

        DB::transaction(function () use ($order) {
            $locked = Order::query()->lockForUpdate()->find($order->id);
            if (! $locked || $locked->chat_shipped_sent_at !== null) {
                return;
            }

            $locked->loadMissing(['distributor']);
            $distributor = $locked->distributor;
            if (! $distributor || ! $distributor->user_id) {
                return;
            }

            $template = $distributor->chat_template_order_shipped
                ?: (string) config('order_chat.defaults.order_shipped',
                    'Your order {order_number} has been shipped. For COD orders, please prepare the exact amount for the courier. Thank you!');

            $body = $this->interpolate($template, $locked, $distributor->company_name ?? 'Shop');

            $conversation = $locked->getOrCreateShopConversation();

            $conversation->messages()->create([
                'user_id' => $distributor->user_id,
                'kind' => 'automated',
                'order_id' => $locked->id,
                'body' => $body,
                'meta' => [
                    'automated_event' => 'order_shipped',
                    'shop_name' => $distributor->company_name,
                    'slug' => $distributor->slug,
                ],
            ]);

            $conversation->update(['last_message_at' => now()]);

            $locked->forceFill(['chat_shipped_sent_at' => now()])->save();

            Log::info('[OrderChatAutomation] Order shipped template posted', ['order_id' => $locked->id]);
        });
    }

    private function interpolate(string $template, Order $order, string $shopName): string
    {
        $customerName = $order->customer?->name ?? 'customer';

        return str_replace(
            ['{shop_name}', '{order_number}', '{customer_name}'],
            [$shopName, $order->order_number, $customerName],
            $template
        );
    }
}
