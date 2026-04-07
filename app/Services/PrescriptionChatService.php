<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PrescriptionChatService
{
    public function postCustomerUpload(Order $order): void
    {
        $order->loadMissing(['distributor']);

        if (! $order->prescription_image_path) {
            return;
        }

        $conversation = $order->getOrCreateShopConversation();

        $conversation->messages()->create([
            'user_id' => $order->customer_id,
            'kind' => 'prescription',
            'order_id' => $order->id,
            'body' => 'Prescription photo submitted for order '.$order->order_number.'. Awaiting distributor review.',
            'image_path' => $order->prescription_image_path,
            'meta' => [
                'rx_event' => 'uploaded',
                'order_number' => $order->order_number,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);

        Log::info('[PrescriptionChat] Upload posted to conversation', [
            'order_id' => $order->id,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function postShopApproved(Order $order): void
    {
        $order->loadMissing('distributor');
        $distributor = $order->distributor;
        if (! $distributor?->user_id) {
            return;
        }

        $conversation = $order->getOrCreateShopConversation();

        $conversation->messages()->create([
            'user_id' => $distributor->user_id,
            'kind' => 'prescription',
            'order_id' => $order->id,
            'body' => 'Prescription approved for order '.$order->order_number.'. You can proceed with payment when ready.',
            'image_path' => null,
            'meta' => [
                'rx_event' => 'approved',
                'order_number' => $order->order_number,
                'shop_name' => $distributor->company_name,
                'slug' => $distributor->slug,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    public function postShopRejected(Order $order, string $reason): void
    {
        $order->loadMissing('distributor');
        $distributor = $order->distributor;
        if (! $distributor?->user_id) {
            return;
        }

        $conversation = $order->getOrCreateShopConversation();

        $conversation->messages()->create([
            'user_id' => $distributor->user_id,
            'kind' => 'prescription',
            'order_id' => $order->id,
            'body' => 'Prescription could not be accepted for order '.$order->order_number.'. '.$reason,
            'image_path' => null,
            'meta' => [
                'rx_event' => 'rejected',
                'order_number' => $order->order_number,
                'shop_name' => $distributor->company_name,
                'slug' => $distributor->slug,
                'reject_reason' => $reason,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);
    }
}
