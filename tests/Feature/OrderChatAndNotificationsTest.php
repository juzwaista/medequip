<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use App\Services\OrderChatAutomationService;
use App\Services\UnreadConversationMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderChatAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_order_chat_notifies_shop_owner_database(): void
    {
        [$buyer, $seller, $order] = $this->createOrderFixture();

        $this->actingAs($buyer)->postJson("/orders/{$order->id}/messages", [
            'body' => 'Hello shop',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $seller->id,
            'type' => NewChatMessageNotification::class,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $buyer->id,
            'type' => NewChatMessageNotification::class,
        ]);
    }

    public function test_customer_can_report_shop_message_in_shop_thread(): void
    {
        [$buyer, $seller, , $distributor] = $this->createOrderFixture();

        $conv = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $buyer->id,
                'distributor_id' => $distributor->id,
            ],
            ['context_product_id' => null]
        );

        $this->actingAs($seller)->postJson("/owner/messages/{$conv->id}/messages", [
            'body' => 'Hi from shop',
        ])->assertCreated();

        $messageId = (int) $conv->messages()->latest('id')->value('id');

        $this->actingAs($buyer)->postJson("/messages/{$conv->id}/messages/{$messageId}/report", [
            'reason' => 'spam',
            'details' => 'Test',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('conversation_message_reports', [
            'conversation_message_id' => $messageId,
            'reporter_id' => $buyer->id,
        ]);
    }

    public function test_mark_read_sets_read_at_on_incoming_messages(): void
    {
        [$buyer, $seller, , $distributor] = $this->createOrderFixture();

        $conv = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $buyer->id,
                'distributor_id' => $distributor->id,
            ],
            ['context_product_id' => null]
        );

        $this->actingAs($seller)->postJson("/owner/messages/{$conv->id}/messages", [
            'body' => 'Unread line',
        ])->assertCreated();

        $msg = $conv->messages()->latest('id')->first();
        $this->assertNull($msg->read_at);

        $this->actingAs($buyer)->postJson("/messages/{$conv->id}/mark-read")->assertOk();
        $this->assertNotNull($msg->fresh()->read_at);
    }

    public function test_order_accepted_automation_posts_chat_message(): void
    {
        [, , $order] = $this->createOrderFixture();

        app(OrderChatAutomationService::class)->sendOrderAcceptedMessage($order);

        $this->assertDatabaseHas('conversation_messages', [
            'order_id' => $order->id,
            'kind' => 'automated',
        ]);

        $order->refresh();
        $this->assertNotNull($order->chat_welcome_sent_at);

        $this->assertSame(
            0,
            DB::table('notifications')->where('type', NewChatMessageNotification::class)->count()
        );
    }

    public function test_order_shipped_automation_posts_once(): void
    {
        [, , $order] = $this->createOrderFixture();
        $order->update(['chat_welcome_sent_at' => now()]);

        app(OrderChatAutomationService::class)->sendOrderShippedMessage($order);
        app(OrderChatAutomationService::class)->sendOrderShippedMessage($order->fresh());

        $shippedMsgs = $order->fresh()->chatMessages()->where('kind', 'automated')->get()
            ->filter(fn ($m) => ($m->meta['automated_event'] ?? '') === 'order_shipped');
        $this->assertCount(1, $shippedMsgs);
    }

    public function test_profanity_is_censored_in_stored_message(): void
    {
        [$buyer, , $order] = $this->createOrderFixture();

        $this->actingAs($buyer)->postJson("/orders/{$order->id}/messages", [
            'body' => 'This is damn late',
        ])->assertCreated();

        $row = $order->chatMessages()->latest('id')->first();
        $this->assertStringContainsString('****', $row->body);
        $this->assertStringNotContainsString('damn', mb_strtolower($row->body));
    }

    public function test_customer_can_report_shop_message(): void
    {
        [$buyer, $seller, $order] = $this->createOrderFixture();

        $this->actingAs($seller)->postJson("/owner/orders/{$order->id}/messages", [
            'body' => 'We are preparing your order',
        ])->assertCreated();

        $messageId = $order->chatMessages()->latest('id')->value('id');

        $this->actingAs($buyer)->postJson("/orders/{$order->id}/messages/{$messageId}/report", [
            'reason' => 'spam',
            'details' => 'Test report',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('conversation_message_reports', [
            'conversation_message_id' => $messageId,
            'reporter_id' => $buyer->id,
        ]);
    }

    public function test_image_upload_creates_message(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension required for fake image upload.');
        }

        Storage::fake('public');
        [$buyer, , $order] = $this->createOrderFixture();

        $file = UploadedFile::fake()->image('chat.jpg', 200, 200);

        $this->actingAs($buyer)->post("/orders/{$order->id}/messages", [
            'image' => $file,
        ])->assertStatus(201);

        $row = $order->chatMessages()->latest('id')->first();
        $this->assertNotNull($row->image_path);
        Storage::disk('public')->assertExists($row->image_path);
    }

    public function test_notifications_poll_includes_unread_welcome_notification(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->getJson('/notifications/poll')
            ->assertOk()
            ->assertJson(['unread_count' => 1, 'unread_chat_count' => 0]);
    }

    public function test_new_chat_database_notification_is_excluded_from_poll_unread_count(): void
    {
        [$buyer, $seller, $order] = $this->createOrderFixture();

        $this->actingAs($buyer)->postJson("/orders/{$order->id}/messages", [
            'body' => 'Hello shop',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $seller->id,
            'type' => NewChatMessageNotification::class,
        ]);

        $this->actingAs($seller)->getJson('/notifications/poll')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('unread_chat_count', 1);
    }

    public function test_unread_chat_messages_count_matches_incoming_messages(): void
    {
        [$buyer, $seller, , $distributor] = $this->createOrderFixture();

        $conv = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $buyer->id,
                'distributor_id' => $distributor->id,
            ],
            ['context_product_id' => null]
        );

        $this->actingAs($seller)->postJson("/owner/messages/{$conv->id}/messages", [
            'body' => 'Shop says hi',
        ])->assertCreated();

        $this->assertSame(1, UnreadConversationMessageService::countFor($buyer));
        $this->assertSame(0, UnreadConversationMessageService::countFor($seller));
    }
}
