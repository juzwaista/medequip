<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end coverage of OrderController::placeOrder (the "purchase process"), which had no
 * test at all: card/e-wallet checkout via PayMongo, B2B purchase-order checkout, wholesale
 * pricing, and behavior when PayMongo isn't configured.
 */
class PlaceOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'services.paymongo.secret_key' => 'sk_test_dummy',
            'services.paymongo.public_key' => 'pk_test_dummy',
            'services.paymongo.webhook_secret' => 'whsk_dummy',
        ]);

        $seller = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $distributor = Distributor::create([
            'user_id' => $seller->id,
            'company_name' => 'Test Medical Supplies',
            'slug' => 'test-medical-'.uniqid(),
            'address' => '123 Health St',
            'contact_number' => '09123456789',
            'email' => 'shop@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $category = Category::create(['name' => 'General', 'slug' => 'general']);

        $this->product = Product::create([
            'distributor_id' => $distributor->id,
            'category_id' => $category->id,
            'name' => 'Test Stethoscope',
            'description' => 'Medical-grade stethoscope for testing',
            'sku' => 'TST-'.uniqid(),
            'base_price' => 500.00,
            'wholesale_price' => 400.00,
            'wholesale_min_qty' => 10,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'reorder_level' => 5,
        ]);

        $this->customer = User::factory()->customer()->create([
            'email_verified_at' => now(),
            'phone_number' => '09171234567',
        ]);
    }

    private function fakePayMongo(): void
    {
        Http::fake([
            'api.paymongo.com/*' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_123'],
                ],
            ], 200),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Juan Dela Cruz',
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'delivery_latitude' => 14.4297,
            'delivery_longitude' => 120.9367,
            'payment_method' => 'gcash',
            'fulfillment_method' => 'delivery',
            'buy_now' => 1,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ], $overrides);
    }

    private function approveBusiness(): void
    {
        BusinessProfile::create([
            'user_id' => $this->customer->id,
            'company_name' => 'St. Jude Hospital',
            'business_type' => 'Hospital',
            'status' => 'approved',
        ]);
    }

    public function test_checkout_page_renders_for_a_cart(): void
    {
        $this->actingAs($this->customer)
            ->withSession(['cart' => ['p'.$this->product->id => [
                'product_id' => $this->product->id,
                'product_variation_id' => null,
                'quantity' => 2,
            ]]])
            ->get(route('checkout'))
            ->assertOk();
    }

    public function test_online_payment_order_is_created_and_redirects_to_paymongo(): void
    {
        $this->fakePayMongo();

        $response = $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload());

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_123');

        $order = Order::where('customer_id', $this->customer->id)->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame('gcash', $order->payment_method);
        $this->assertSame(2, $this->product->inventory()->first()->reserved_quantity);
    }

    public function test_purchase_order_checkout_creates_order_awaiting_po_verification(): void
    {
        $this->approveBusiness();

        $response = $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload([
                'payment_method' => 'purchase_order',
                'po_document' => UploadedFile::fake()->create('po.pdf', 100, 'application/pdf'),
            ]));

        $order = Order::where('customer_id', $this->customer->id)->firstOrFail();
        $response->assertRedirect(route('orders.confirmation', $order));

        $this->assertSame('pending_po_verification', $order->status);
        $this->assertNotNull($order->po_document_path);
        Storage::disk('public')->assertExists($order->po_document_path);
    }

    public function test_purchase_order_can_be_saved_for_reuse_and_confirmation_page_renders(): void
    {
        $this->approveBusiness();

        $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload([
                'payment_method' => 'purchase_order',
                'po_document' => UploadedFile::fake()->create('po.pdf', 100, 'application/pdf'),
                'save_purchase_order' => 1,
            ]));

        $order = Order::where('customer_id', $this->customer->id)->firstOrFail();

        $profiles = $this->customer->savedPurchaseOrders()->get();
        $this->assertCount(1, $profiles);
        $this->assertSame('St. Jude Hospital', $profiles->first()->company_name);
        $this->assertSame('09171234567', $profiles->first()->contact_number);
        $this->assertNotEmpty($profiles->first()->label);

        $this->actingAs($this->customer)
            ->get(route('orders.confirmation', $order))
            ->assertOk();
    }

    public function test_checkout_from_the_session_cart_works_when_selected_items_is_blank(): void
    {
        $this->fakePayMongo();

        // Opening /checkout directly (no ?selected_items=...) makes the form send an empty
        // selected_items; that must mean "the whole cart", not "nothing selected".
        $payload = $this->payload();
        unset($payload['buy_now'], $payload['product_id'], $payload['quantity']);
        $payload['selected_items'] = '';

        $this->actingAs($this->customer)
            ->withSession(['cart' => ['p'.$this->product->id => [
                'product_id' => $this->product->id,
                'product_variation_id' => null,
                'quantity' => 2,
            ]]])
            ->post(route('orders.place'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect('https://checkout.paymongo.com/cs_test_123');

        $this->assertSame(1, Order::where('customer_id', $this->customer->id)->count());
    }

    public function test_saved_purchase_order_profile_is_linked_to_the_order(): void
    {
        $this->approveBusiness();
        $profile = $this->customer->savedPurchaseOrders()->create([
            'label' => 'St. Jude PO',
            'company_name' => 'St. Jude Hospital',
        ]);

        $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload([
                'payment_method' => 'purchase_order',
                'po_document' => UploadedFile::fake()->create('po.pdf', 100, 'application/pdf'),
                'saved_purchase_order_id' => $profile->id,
            ]));

        $order = Order::where('customer_id', $this->customer->id)->firstOrFail();
        $this->assertSame($profile->id, $order->saved_purchase_order_id);
    }

    public function test_verified_distributor_buyer_gets_wholesale_price_in_cart_and_order(): void
    {
        $buyer = User::factory()->distributor()->create(['email_verified_at' => now(), 'phone_number' => '09171234568']);
        Distributor::create([
            'user_id' => $buyer->id,
            'company_name' => 'Buyer Pharmacy',
            'slug' => 'buyer-pharmacy-'.uniqid(),
            'address' => '1 Buyer St',
            'contact_number' => '09123456780',
            'email' => 'buyer@test.test',
            'status' => 'approved',
            'is_verified' => true,
            'shop_profile_onboarding_completed_at' => now(),
        ]);
        $this->fakePayMongo();

        $cart = [CartService::lineKey($this->product->id) => [
            'product_id' => $this->product->id,
            'product_variation_id' => null,
            'quantity' => 10,
        ]];
        $line = collect(CartService::enrichCartItems($cart, $buyer->fresh()))->first();
        $this->assertTrue((bool) $line['is_wholesale']);

        $this->actingAs($buyer)->post(route('orders.place'), $this->payload(['quantity' => 10]));

        $item = Order::where('customer_id', $buyer->id)->firstOrFail()->items()->firstOrFail();
        $this->assertTrue((bool) $item->is_wholesale);
        $this->assertEquals(400.00, (float) $item->unit_price);
    }

    public function test_purchase_order_requires_the_po_document(): void
    {
        $this->approveBusiness();

        $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload(['payment_method' => 'purchase_order']))
            ->assertSessionHasErrors('po_document');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_approved_business_gets_wholesale_price_at_min_quantity(): void
    {
        $this->approveBusiness();
        $this->fakePayMongo();

        $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload(['quantity' => 10]));

        $item = Order::where('customer_id', $this->customer->id)->firstOrFail()->items()->firstOrFail();
        $this->assertTrue((bool) $item->is_wholesale);
        $this->assertEquals(400.00, (float) $item->unit_price);
    }

    public function test_regular_customer_pays_retail_price_even_at_wholesale_quantity(): void
    {
        $this->fakePayMongo();

        $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload(['quantity' => 10]));

        $item = Order::where('customer_id', $this->customer->id)->firstOrFail()->items()->firstOrFail();
        $this->assertFalse((bool) $item->is_wholesale);
        $this->assertEquals(500.00, (float) $item->unit_price);
    }

    public function test_purchase_order_checkout_works_even_when_paymongo_is_not_configured(): void
    {
        config(['services.paymongo.secret_key' => null]);
        $this->approveBusiness();

        $response = $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload([
                'payment_method' => 'purchase_order',
                'po_document' => UploadedFile::fake()->create('po.pdf', 100, 'application/pdf'),
            ]));

        $order = Order::where('customer_id', $this->customer->id)->firstOrFail();
        $response->assertRedirect(route('orders.confirmation', $order));
    }

    public function test_unconfigured_paymongo_fails_gracefully_for_online_payment(): void
    {
        config(['services.paymongo.secret_key' => null]);

        $response = $this->actingAs($this->customer)
            ->post(route('orders.place'), $this->payload());

        // Order is placed and the customer is sent to the order page to retry payment later —
        // never a 500.
        $order = Order::where('customer_id', $this->customer->id)->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('warning');
    }
}
