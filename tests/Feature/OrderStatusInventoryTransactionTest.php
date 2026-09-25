<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Distributor;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Regression coverage for the Owner\OrderController::updateStatus /
 * confirmCodRemittance transaction + locking fix and the AutomatedPayoutService
 * failure-visibility fix (see PROJECT_CONTEXT.md §11 problems #1-#4).
 */
class OrderStatusInventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected Distributor $distributor;

    protected User $customer;

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

        $this->customer = User::factory()->customer()->create(['email_verified_at' => now()]);
    }

    protected function makeProductWithInventory(int $quantity, ?int $reservedQuantity = null): Product
    {
        $category = Category::create([
            'name' => 'General '.uniqid(),
            'slug' => 'general-'.uniqid(),
        ]);

        $product = Product::create([
            'distributor_id' => $this->distributor->id,
            'category_id' => $category->id,
            'name' => 'Test Product '.uniqid(),
            'description' => 'For testing',
            'sku' => 'TST-'.uniqid(),
            'base_price' => 500.00,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reorder_level' => 2,
            'reserved_quantity' => $reservedQuantity ?? 0,
        ]);

        return $product;
    }

    /**
     * @param  array<int, array{product: Product, quantity: int}>  $lines
     */
    protected function makePendingOrder(array $lines, string $status = 'pending'): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_id' => $this->customer->id,
            'distributor_id' => $this->distributor->id,
            'status' => $status,
            'subtotal' => 500.00,
            'shipping_fee' => 50.00,
            'total_amount' => 550.00,
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'payment_method' => 'gcash',
        ]);

        foreach ($lines as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $line['product']->id,
                'inventory_id' => $line['product']->inventory->first()->id,
                'quantity' => $line['quantity'],
                'unit_price' => 500.00,
                'total_price' => 500.00 * $line['quantity'],
                'subtotal' => 500.00 * $line['quantity'],
            ]);
        }

        return $order;
    }

    public function test_approve_rolls_back_stock_when_a_later_item_cannot_be_deducted(): void
    {
        // Both order items share the same inventory row (5 in stock). Each individually
        // passes the per-item "enough stock?" pre-check (5 >= 3), but the row can't cover
        // both: this is the deterministic way to hit the deduction-loop failure that the
        // missing DB::transaction() used to leave half-applied.
        $product = $this->makeProductWithInventory(quantity: 5, reservedQuantity: 6);
        $order = $this->makePendingOrder([
            ['product' => $product, 'quantity' => 3],
            ['product' => $product, 'quantity' => 3],
        ]);

        $response = $this->actingAs($this->seller)
            ->patch(route('owner.orders.updateStatus', $order), ['status' => 'approved']);

        $response->assertSessionHasErrors('error');

        $order->refresh();
        $this->assertSame('pending', $order->status);

        $inventory = $product->inventory->first()->fresh();
        $this->assertSame(5, $inventory->quantity, 'partial deduction from the first item must be rolled back');
        $this->assertSame(6, $inventory->reserved_quantity, 'reservation must be untouched since approval failed');
    }

    public function test_approve_deducts_stock_and_clears_reservation_on_success(): void
    {
        $product = $this->makeProductWithInventory(quantity: 10, reservedQuantity: 2);
        $order = $this->makePendingOrder([
            ['product' => $product, 'quantity' => 2],
        ]);

        $response = $this->actingAs($this->seller)
            ->patch(route('owner.orders.updateStatus', $order), ['status' => 'approved']);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('approved', $order->status);

        $inventory = $product->inventory->first()->fresh();
        $this->assertSame(8, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved_quantity);
    }

    public function test_rejecting_a_pending_order_releases_reservation_without_touching_quantity(): void
    {
        $product = $this->makeProductWithInventory(quantity: 10, reservedQuantity: 2);
        $order = $this->makePendingOrder([
            ['product' => $product, 'quantity' => 2],
        ]);

        $response = $this->actingAs($this->seller)
            ->patch(route('owner.orders.updateStatus', $order), ['status' => 'rejected']);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('rejected', $order->status);

        $inventory = $product->inventory->first()->fresh();
        $this->assertSame(10, $inventory->quantity, 'stock was only reserved, never deducted, for a pending order');
        $this->assertSame(0, $inventory->reserved_quantity);
    }

    public function test_cancelling_an_approved_order_restores_deducted_quantity(): void
    {
        // 'approved' can only transition to packed/ready_for_pickup/cancelled — not
        // 'rejected' — per the state machine in Owner\OrderController::updateStatus().
        $product = $this->makeProductWithInventory(quantity: 8, reservedQuantity: 0);
        $order = $this->makePendingOrder([
            ['product' => $product, 'quantity' => 2],
        ], status: 'approved');

        $response = $this->actingAs($this->seller)
            ->patch(route('owner.orders.updateStatus', $order), ['status' => 'cancelled']);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);

        $inventory = $product->inventory->first()->fresh();
        $this->assertSame(10, $inventory->quantity, 'previously-deducted stock must be restored');
    }

    public function test_confirm_received_releases_escrow_and_flags_failed_seller_payout(): void
    {
        // Distributor has no payout_bank on file, so AutomatedPayoutService::disburse()
        // is guaranteed to no-op (return false) — order completion / escrow release must
        // still proceed, but the failure must now be logged with order/payment context.
        $this->assertNull($this->distributor->payout_bank);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_id' => $this->customer->id,
            'distributor_id' => $this->distributor->id,
            'status' => 'delivered',
            'subtotal' => 500.00,
            'shipping_fee' => 50.00,
            'total_amount' => 550.00,
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'payment_method' => 'gcash',
        ]);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => 550.00,
            'total_amount' => 550.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'gcash',
            'amount' => 550.00,
            'status' => 'verified',
            'verified_at' => now(),
            'escrow_status' => 'held',
            'platform_fee_amount' => 27.50,
            'net_seller_amount' => 522.50,
        ]);

        Log::spy();

        // OrderController's constructor always injects PayMongoService even though
        // confirmReceived() never calls it; mock it so we don't need a real PayMongo
        // secret key configured just to hit this route in tests.
        $this->mock(PayMongoService::class);

        $response = $this->actingAs($this->customer)
            ->post(route('orders.confirmReceived', $order));

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->received_at);

        $payment->refresh();
        $this->assertSame('released', $payment->escrow_status);
        $this->assertNotNull($payment->released_at);
        $this->assertNull($payment->seller_payout_cleared_at, 'payout never actually ran, so this must stay null');

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => str_contains($message, 'Seller payout did not complete automatically'))
            ->once();
    }

    public function test_confirm_cod_remittance_completes_order_even_when_courier_payout_fails(): void
    {
        $courierUser = User::factory()->courier()->create(['email_verified_at' => now()]);
        $courier = Courier::create([
            'user_id' => $courierUser->id,
            'status' => 'active',
        ]);
        $this->assertNull($courier->payout_bank);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_id' => $this->customer->id,
            'distributor_id' => $this->distributor->id,
            'status' => 'delivered',
            'subtotal' => 500.00,
            'shipping_fee' => 50.00,
            'total_amount' => 550.00,
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'payment_method' => 'cod',
        ]);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => 550.00,
            'total_amount' => 550.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'cod',
            'amount' => 550.00,
            'status' => 'pending',
            'escrow_status' => 'held',
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'tracking_number' => Delivery::generateTrackingNumber(),
            'delivery_address' => $order->delivery_address,
            'courier_fee' => 40.00,
            'courier_payout_status' => 'pending',
            'status' => 'delivered',
            'cod_collected_at' => now(),
            'cod_remittance_sent_at' => now(),
        ]);

        Log::spy();

        $response = $this->actingAs($this->seller)
            ->post(route('owner.orders.confirmCodRemittance', $order));

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('completed', $order->status);

        $delivery->refresh();
        $this->assertNotNull($delivery->cod_remitted_at);
        $this->assertSame('pending', $delivery->courier_payout_status, 'payout never actually ran, so this must stay pending');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => str_contains($message, 'Courier payout did not complete automatically'))
            ->once();
    }
}
