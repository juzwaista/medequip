<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatDeliveredSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_outgoing_message_marked_delivered_when_recipient_last_seen_after_message(): void
    {
        [$buyer, $seller, $order, $distributor] = $this->createOrderFixture();

        $order->getOrCreateShopConversation();

        $this->actingAs($buyer)->postJson("/orders/{$order->id}/messages", [
            'body' => 'Hello shop',
        ])->assertCreated();

        $seller->refresh();
        $seller->forceFill(['last_seen_at' => now()->addSecond()])->save();

        $json = $this->actingAs($buyer)->getJson("/orders/{$order->id}/messages")->assertOk()->json();
        $messages = $json['messages'] ?? [];
        $this->assertNotEmpty($messages);
        $last = end($messages);
        $this->assertTrue($last['is_mine']);
        $this->assertTrue($last['delivered']);
    }

    /**
     * @return array{0: User, 1: User, 2: Order, 3: Distributor}
     */
    protected function createOrderFixture(): array
    {
        $seller = User::factory()->distributor()->create([
            'email_verified_at' => now(),
        ]);

        $distributor = Distributor::create([
            'user_id' => $seller->id,
            'company_name' => 'Test Pharmacy',
            'address' => '123 St',
            'contact_number' => '09123456789',
            'email' => 'shop@example.test',
            'status' => 'approved',
            'slug' => 'test-pharmacy-'.uniqid(),
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $buyer = User::factory()->customer()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_id' => $buyer->id,
            'distributor_id' => $distributor->id,
            'status' => 'pending',
            'subtotal' => 100,
            'discount' => 0,
            'total_amount' => 100,
            'delivery_address' => 'Cavite',
            'contact_number' => '09987654321',
            'payment_method' => 'cod',
        ]);

        return [$buyer, $seller, $order, $distributor];
    }
}
