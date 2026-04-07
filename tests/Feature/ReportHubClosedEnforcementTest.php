<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationMessageReport;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReportHubClosedEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dismissed_message_report_show_has_no_available_actions(): void
    {
        $admin = User::factory()->admin()->create();
        [$buyer, $seller, , $distributor] = $this->createMinimalShopThread();

        $conv = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $buyer->id,
                'distributor_id' => $distributor->id,
            ],
            ['context_product_id' => null]
        );

        $msg = ConversationMessage::query()->create([
            'conversation_id' => $conv->id,
            'user_id' => $seller->id,
            'kind' => 'user',
            'body' => 'Shop says hi',
            'order_id' => null,
        ]);

        $report = ConversationMessageReport::query()->create([
            'conversation_message_id' => $msg->id,
            'reporter_id' => $buyer->id,
            'reason' => 'spam',
            'details' => 'Test',
            'status' => 'dismissed',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', ['bucket' => 'message', 'id' => $report->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Reports/Show')
                ->where('availableActions', []));
    }

    public function test_enforce_on_dismissed_message_report_returns_422(): void
    {
        $admin = User::factory()->admin()->create();
        [$buyer, $seller, , $distributor] = $this->createMinimalShopThread();

        $conv = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $buyer->id,
                'distributor_id' => $distributor->id,
            ],
            ['context_product_id' => null]
        );

        $msg = ConversationMessage::query()->create([
            'conversation_id' => $conv->id,
            'user_id' => $seller->id,
            'kind' => 'user',
            'body' => 'Shop says hi',
            'order_id' => null,
        ]);

        $report = ConversationMessageReport::query()->create([
            'conversation_message_id' => $msg->id,
            'reporter_id' => $buyer->id,
            'reason' => 'spam',
            'details' => 'Test',
            'status' => 'dismissed',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.enforce', ['bucket' => 'message', 'id' => $report->id]), [
                'action' => 'dismiss',
                'accountability_notes' => 'Trying again after triage closed the case.',
            ])
            ->assertStatus(422);
    }

    /**
     * @return array{0: User, 1: User, 2: Order, 3: Distributor}
     */
    protected function createMinimalShopThread(): array
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
