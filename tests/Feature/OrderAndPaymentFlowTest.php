<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Distributor;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OrderInvoiceService;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class OrderAndPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $seller;

    protected Distributor $distributor;

    protected Product $product;

    protected Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->distributor()->create(['email_verified_at' => now()]);

        $this->distributor = Distributor::create([
            'user_id' => $this->seller->id,
            'company_name' => 'Test Medical Supplies',
            'slug' => 'test-medical-'.uniqid(),
            'address' => '123 Health St',
            'contact_number' => '09123456789',
            'email' => 'shop@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'General',
            'slug' => 'general',
        ]);

        $this->product = Product::create([
            'distributor_id' => $this->distributor->id,
            'category_id' => $category->id,
            'name' => 'Test Stethoscope',
            'description' => 'Medical-grade stethoscope for testing',
            'sku' => 'TST-'.uniqid(),
            'base_price' => 500.00,
            'is_active' => true,
        ]);

        $this->inventory = Inventory::create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'reorder_level' => 5,
        ]);

        $this->customer = User::factory()->customer()->create([
            'email_verified_at' => now(),
            'phone_number' => '09171234567',
        ]);

        $this->customer->addresses()->create([
            'label' => 'Home',
            'recipient_name' => $this->customer->name,
            'contact_number' => '09171234567',
            'address_line' => '456 Patient Rd',
            'barangay' => 'San Jose',
            'city' => 'Imus',
            'province' => 'Cavite',
            'zip_code' => '4103',
            'is_default' => true,
        ]);
    }

    protected function createOrderWithInvoice(string $paymentMethod = 'cod'): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_id' => $this->customer->id,
            'distributor_id' => $this->distributor->id,
            'status' => 'pending',
            'subtotal' => 500.00,
            'shipping_fee' => 50.00,
            'total_amount' => 550.00,
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'payment_method' => $paymentMethod,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'inventory_id' => $this->inventory->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'total_price' => 500.00,
            'subtotal' => 500.00,
        ]);

        return $order;
    }

    // ─── OrderInvoiceService ──────────────────────────────

    public function test_invoice_service_creates_invoice_and_payment(): void
    {
        $order = $this->createOrderWithInvoice('wallet');

        $adminUser = User::factory()->admin()->create(['email_verified_at' => now()]);
        $adminUser->wallet->update(['balance' => 10000]);

        $service = app(OrderInvoiceService::class);
        $invoice = $service->createInvoiceAndPayment($order);

        $this->assertNotNull($invoice);
        $this->assertEquals($order->id, $invoice->order_id);
        $this->assertEquals('paid', $invoice->status);

        $payment = $invoice->payments->first();
        $this->assertNotNull($payment);
        $this->assertEquals('wallet', $payment->payment_method);
        $this->assertEquals('verified', $payment->status);
        $this->assertEquals('held', $payment->escrow_status);
        $this->assertGreaterThan(0, (float) $payment->platform_fee_amount);
    }

    public function test_invoice_service_is_idempotent(): void
    {
        $order = $this->createOrderWithInvoice('wallet');

        $adminUser = User::factory()->admin()->create(['email_verified_at' => now()]);
        $adminUser->wallet->update(['balance' => 10000]);

        $service = app(OrderInvoiceService::class);
        $invoice1 = $service->createInvoiceAndPayment($order);
        $invoice2 = $service->createInvoiceAndPayment($order->fresh());

        $this->assertEquals($invoice1->id, $invoice2->id);
        $this->assertCount(1, Invoice::where('order_id', $order->id)->get());
    }

    // ─── Payment fee calculation ──────────────────────────

    public function test_payment_fees_are_calculated_correctly(): void
    {
        $fees = Payment::calculateFees(1000.00);

        $this->assertArrayHasKey('platform_fee_rate', $fees);
        $this->assertArrayHasKey('platform_fee_amount', $fees);
        $this->assertArrayHasKey('net_seller_amount', $fees);
        $this->assertEquals(1000.00, $fees['platform_fee_amount'] + $fees['net_seller_amount']);
    }

    // ─── Wallet ───────────────────────────────────────────

    public function test_wallet_credit_increases_balance(): void
    {
        $walletUser = User::factory()->customer()->create(['email_verified_at' => now()]);
        $wallet = $walletUser->wallet;
        $wallet->update(['balance' => 100]);

        $wallet->credit(50, 'test_credit', null, 'Test deposit');

        $wallet->refresh();
        $this->assertEquals('150.00', $wallet->balance);
        $this->assertCount(1, $wallet->transactions);
    }

    public function test_wallet_debit_decreases_balance(): void
    {
        $walletUser = User::factory()->customer()->create(['email_verified_at' => now()]);
        $wallet = $walletUser->wallet;
        $wallet->update(['balance' => 200]);

        $wallet->debit(75, 'test_debit', null, 'Test withdrawal');

        $wallet->refresh();
        $this->assertEquals('125.00', $wallet->balance);
    }

    public function test_wallet_debit_fails_on_insufficient_balance(): void
    {
        $walletUser = User::factory()->customer()->create(['email_verified_at' => now()]);
        $wallet = $walletUser->wallet;
        $wallet->update(['balance' => 50]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance.');

        $wallet->debit(100, 'test_debit');
    }

    // ─── Admin moderation ─────────────────────────────────

    public function test_admin_can_ban_user(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        $target = User::factory()->customer()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->post("/admin/users/{$target->id}/ban", [
            'reason' => 'Repeated policy violations in test',
        ]);

        $response->assertRedirect();
        $target->refresh();
        $this->assertNotNull($target->banned_at);
        $this->assertEquals('Repeated policy violations in test', $target->ban_reason);
    }

    public function test_admin_can_unban_user(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        $target = User::factory()->customer()->create(['email_verified_at' => now()]);
        $target->forceFill(['banned_at' => now(), 'ban_reason' => 'test'])->save();

        $response = $this->actingAs($admin)->post("/admin/users/{$target->id}/unban");

        $response->assertRedirect();
        $target->refresh();
        $this->assertNull($target->banned_at);
    }

    public function test_non_admin_cannot_ban_user(): void
    {
        $target = User::factory()->customer()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($this->customer)->post("/admin/users/{$target->id}/ban", [
            'reason' => 'Should not work',
        ]);

        $response->assertRedirect('/');
        $target->refresh();
        $this->assertNull($target->banned_at);
    }

    // ─── Customer order view ──────────────────────────────

    public function test_customer_can_view_own_order(): void
    {
        $order = $this->createOrderWithInvoice();

        $response = $this->actingAs($this->customer)->get("/orders/{$order->id}");
        $response->assertStatus(200);
    }

    public function test_customer_cannot_view_other_order(): void
    {
        $order = $this->createOrderWithInvoice();
        $otherCustomer = User::factory()->customer()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($otherCustomer)->get("/orders/{$order->id}");
        $response->assertStatus(403);
    }

    public function test_pay_now_flag_true_when_paymongo_order_has_no_invoice(): void
    {
        $order = $this->createOrderWithInvoice('paymongo');
        $this->assertNull(Invoice::where('order_id', $order->id)->first());

        $response = $this->actingAs($this->customer)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Orders/Show')
            ->where('order.can_pay_now', true));
    }

    public function test_pay_now_bucket_includes_paymongo_orders_without_invoice(): void
    {
        $order = $this->createOrderWithInvoice('paymongo');

        $response = $this->actingAs($this->customer)->get(route('orders.index', ['bucket' => 'pay']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $order->id));
    }

    public function test_pay_now_post_creates_invoice_and_redirects_to_checkout(): void
    {
        $this->mock(PayMongoService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'session_id' => 'sess_test_123',
                    'checkout_url' => 'https://checkout.paymongo.test/session',
                ]);
        });

        $order = $this->createOrderWithInvoice('paymongo');
        $this->assertNull(Invoice::where('order_id', $order->id)->first());

        $response = $this->actingAs($this->customer)->post(route('orders.payNow', $order));

        $this->assertNotNull(Invoice::where('order_id', $order->id)->first());
        $response->assertRedirect('https://checkout.paymongo.test/session');
    }

    // ─── Role protection ──────────────────────────────────

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/dashboard');
        $response->assertRedirect('/');
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_reports_hub_requires_admin(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/reports');
        $response->assertStatus(200);

        $response = $this->actingAs($this->customer)->get('/admin/reports');
        $response->assertRedirect('/');
    }
}
