<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SavedPurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for wiring OrderPolicy/DeliveryPolicy/SavedPurchaseOrderPolicy into
 * the controllers that used to duplicate the same checks manually (see PROJECT_CONTEXT.md
 * §11 problem #8, and the SavedPurchaseOrderController::authorize() override that had to be
 * replaced with a real policy once AuthorizesRequests was added to the base Controller).
 */
class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrderFor(User $customer, Distributor $distributor): Order
    {
        $category = Category::create(['name' => 'General '.uniqid(), 'slug' => 'general-'.uniqid()]);
        $product = Product::create([
            'distributor_id' => $distributor->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'description' => 'For testing',
            'sku' => 'TST-'.uniqid(),
            'base_price' => 500.00,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'distributor_id' => $distributor->id,
            'status' => 'pending',
            'subtotal' => 500.00,
            'shipping_fee' => 50.00,
            'total_amount' => 550.00,
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'payment_method' => 'gcash',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'total_price' => 500.00,
            'subtotal' => 500.00,
        ]);

        return $order;
    }

    protected function makeDistributor(): Distributor
    {
        $owner = User::factory()->distributor()->create(['email_verified_at' => now()]);

        return Distributor::create([
            'user_id' => $owner->id,
            'company_name' => 'Test Medical Supplies',
            'slug' => 'test-medical-'.uniqid(),
            'address' => '123 Health St',
            'contact_number' => '09123456789',
            'email' => 'shop@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);
    }

    public function test_shop_owner_can_now_view_their_own_order_via_the_customer_facing_route(): void
    {
        // Previously CustomerOrderController::show only checked customer_id, so a
        // distributor viewing their own shop's order through this route got a hard 403.
        // OrderPolicy::view() also allows shop members, so wiring it in fixes that gap.
        $distributor = $this->makeDistributor();
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        $order = $this->makeOrderFor($customer, $distributor);

        $response = $this->actingAs($distributor->user)->get(route('orders.show', $order));

        $response->assertOk();
    }

    public function test_unrelated_customer_still_cannot_view_someone_elses_order(): void
    {
        $distributor = $this->makeDistributor();
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        $order = $this->makeOrderFor($customer, $distributor);

        $otherCustomer = User::factory()->customer()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($otherCustomer)->get(route('orders.show', $order));

        $response->assertForbidden();
    }

    public function test_only_the_order_customer_can_cancel_it_not_a_shop_member(): void
    {
        // OrderPolicy::cancel() is isCustomer-only, unlike view() — confirms the narrower
        // ability wasn't accidentally broadened when the manual check was replaced.
        $distributor = $this->makeDistributor();
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        $order = $this->makeOrderFor($customer, $distributor);

        $response = $this->actingAs($distributor->user)->post(route('orders.cancel', $order));

        $response->assertForbidden();
    }

    public function test_courier_not_assigned_to_a_delivery_cannot_act_on_it(): void
    {
        $distributor = $this->makeDistributor();
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        $order = $this->makeOrderFor($customer, $distributor);

        $assignedCourierUser = User::factory()->courier()->create(['email_verified_at' => now()]);
        $assignedCourier = Courier::create(['user_id' => $assignedCourierUser->id, 'status' => 'active']);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'courier_id' => $assignedCourier->id,
            'tracking_number' => Delivery::generateTrackingNumber(),
            'delivery_address' => $order->delivery_address,
            'status' => 'scheduled',
        ]);

        $otherCourierUser = User::factory()->courier()->create(['email_verified_at' => now()]);
        Courier::create(['user_id' => $otherCourierUser->id, 'status' => 'active']);

        $response = $this->actingAs($otherCourierUser)->post(route('courier.deliveries.startPickup', $delivery));

        $response->assertForbidden();
    }

    public function test_assigned_courier_can_act_on_their_own_delivery(): void
    {
        $distributor = $this->makeDistributor();
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        $order = $this->makeOrderFor($customer, $distributor);

        $courierUser = User::factory()->courier()->create(['email_verified_at' => now()]);
        $courier = Courier::create(['user_id' => $courierUser->id, 'status' => 'active']);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'tracking_number' => Delivery::generateTrackingNumber(),
            'delivery_address' => $order->delivery_address,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($courierUser)->post(route('courier.deliveries.startPickup', $delivery));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_saved_purchase_order_ownership_is_still_enforced_by_the_new_policy(): void
    {
        // SavedPurchaseOrderController used to override authorize() locally; that method had
        // to be replaced with a real SavedPurchaseOrderPolicy once AuthorizesRequests was added
        // to the base Controller (incompatible method signature). This confirms it still works.
        $owner = User::factory()->customer()->create(['email_verified_at' => now()]);
        $intruder = User::factory()->customer()->create(['email_verified_at' => now()]);

        $savedPO = SavedPurchaseOrder::create([
            'user_id' => $owner->id,
            'label' => 'Main Office',
            'company_name' => 'Test Co',
        ]);

        $response = $this->actingAs($intruder)->delete(route('purchase-orders.destroy', $savedPO));

        $response->assertForbidden();
    }
}
