<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Wholesale thresholds are in PIECES, so a distributor who sells by the box (or by both piece and
 * box) still qualifies buyers correctly: 1 box of 10 counts as 10 pieces, and loose pieces and
 * boxes of the same product add up toward the threshold.
 */
class PackSizeWholesaleTest extends TestCase
{
    use RefreshDatabase;

    private Distributor $shop;

    private Category $category;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paymongo.secret_key' => 'sk_test_dummy']);

        $owner = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $this->shop = Distributor::create([
            'user_id' => $owner->id,
            'company_name' => 'Test Medical Supplies',
            'slug' => 'test-medical-'.uniqid(),
            'address' => '123 Health St',
            'contact_number' => '09123456789',
            'email' => 'shop@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);
        $this->category = Category::create(['name' => 'General', 'slug' => 'general']);

        $this->buyer = User::factory()->customer()->create(['email_verified_at' => now(), 'phone_number' => '09171234567']);
        BusinessProfile::create([
            'user_id' => $this->buyer->id,
            'company_name' => 'St. Jude Hospital',
            'business_type' => 'Hospital',
            'status' => 'approved',
        ]);
    }

    /** Sold per piece at 50, wholesale 40 from 100 pieces; has a "Box of 10" variation. */
    private function pieceProductWithBoxOption(): array
    {
        $product = Product::create([
            'distributor_id' => $this->shop->id,
            'category_id' => $this->category->id,
            'name' => 'Surgical Gloves',
            'description' => 'Nitrile gloves',
            'sku' => 'GLV-'.uniqid(),
            'slug' => 'surgical-gloves-'.uniqid(),
            'base_price' => 50,
            'wholesale_price' => 40,
            'wholesale_min_qty' => 100,
            'is_active' => true,
        ]);

        $piece = ProductVariation::create([
            'product_id' => $product->id, 'option_name' => 'Pack', 'option_value' => 'Piece',
            'combination' => ['Pack' => 'Piece'], 'price_adjustment' => 0, 'is_active' => true,
        ]);
        $box = ProductVariation::create([
            'product_id' => $product->id, 'option_name' => 'Pack', 'option_value' => 'Box of 10',
            'combination' => ['Pack' => 'Box of 10'], 'price_adjustment' => 0, 'is_active' => true,
            'units_per_pack' => 10, 'unit_label' => 'box',
        ]);

        foreach ([$piece, $box] as $variation) {
            Inventory::create(['product_id' => $product->id, 'product_variation_id' => $variation->id, 'quantity' => 1000, 'reorder_level' => 5]);
        }

        return [$product, $piece, $box];
    }

    private function cart(array $lines): array
    {
        $cart = [];
        foreach ($lines as [$product, $variation, $qty]) {
            $cart[CartService::lineKey($product->id, $variation?->id)] = [
                'product_id' => $product->id,
                'product_variation_id' => $variation?->id,
                'quantity' => $qty,
            ];
        }

        return $cart;
    }

    private function line(array $items, ?ProductVariation $variation): array
    {
        return collect($items)->first(fn ($i) => $i['product_variation_id'] === $variation?->id);
    }

    public function test_one_box_below_the_piece_threshold_is_priced_at_retail_per_box(): void
    {
        [$product, , $box] = $this->pieceProductWithBoxOption();

        $items = CartService::enrichCartItems($this->cart([[$product, $box, 1]]), $this->buyer);
        $line = $this->line($items, $box);

        $this->assertFalse($line['is_wholesale']);
        $this->assertEquals(500.00, $line['unit_price']);   // 10 pieces × ₱50
        $this->assertSame(10, $line['pieces']);
        $this->assertSame('box', $line['unit_label']);
    }

    public function test_enough_boxes_reach_the_piece_threshold_and_get_the_wholesale_box_price(): void
    {
        [$product, , $box] = $this->pieceProductWithBoxOption();

        // 10 boxes = 100 pieces, exactly the threshold — but only 10 "units" in the cart.
        $line = $this->line(CartService::enrichCartItems($this->cart([[$product, $box, 10]]), $this->buyer), $box);

        $this->assertTrue($line['is_wholesale']);
        $this->assertEquals(400.00, $line['unit_price']);   // 10 pieces × ₱40
    }

    public function test_pieces_and_boxes_of_the_same_product_add_up(): void
    {
        [$product, $piece, $box] = $this->pieceProductWithBoxOption();

        // 4 boxes (40 pcs) + 60 loose pieces = 100 pieces
        $items = CartService::enrichCartItems($this->cart([[$product, $piece, 60], [$product, $box, 4]]), $this->buyer);

        $this->assertTrue($this->line($items, $piece)['is_wholesale']);
        $this->assertEquals(40.00, $this->line($items, $piece)['unit_price']);
        $this->assertTrue($this->line($items, $box)['is_wholesale']);
        $this->assertEquals(400.00, $this->line($items, $box)['unit_price']);
    }

    public function test_one_piece_short_of_the_threshold_is_still_retail(): void
    {
        [$product, $piece, $box] = $this->pieceProductWithBoxOption();

        // 3 boxes (30) + 69 pieces = 99 pieces
        $items = CartService::enrichCartItems($this->cart([[$product, $piece, 69], [$product, $box, 3]]), $this->buyer);

        $this->assertFalse($this->line($items, $piece)['is_wholesale']);
        $this->assertEquals(50.00, $this->line($items, $piece)['unit_price']);
        $this->assertEquals(500.00, $this->line($items, $box)['unit_price']);
    }

    public function test_regular_customers_never_get_wholesale_however_many_pieces(): void
    {
        [$product, , $box] = $this->pieceProductWithBoxOption();
        $regular = User::factory()->customer()->create(['email_verified_at' => now()]);

        $line = $this->line(CartService::enrichCartItems($this->cart([[$product, $box, 50]]), $regular), $box);

        $this->assertFalse($line['is_wholesale']);
        $this->assertEquals(500.00, $line['unit_price']);
    }

    public function test_variation_price_adjustment_applies_after_pack_scaling(): void
    {
        [$product, , $box] = $this->pieceProductWithBoxOption();
        $box->update(['price_adjustment' => -20]);   // box discount

        $line = $this->line(CartService::enrichCartItems($this->cart([[$product, $box, 1]]), $this->buyer), $box);

        $this->assertEquals(480.00, $line['unit_price']);   // 500 − 20
    }

    public function test_product_sold_by_the_box_uses_its_own_pack_size_and_a_piece_option_scales_down(): void
    {
        // Sold by the box: ₱500 per box of 10, wholesale ₱400 per box from 100 pieces.
        $product = Product::create([
            'distributor_id' => $this->shop->id,
            'category_id' => $this->category->id,
            'name' => 'Face Masks',
            'description' => 'Surgical masks',
            'sku' => 'MSK-'.uniqid(),
            'base_price' => 500,
            'wholesale_price' => 400,
            'wholesale_min_qty' => 100,
            'units_per_pack' => 10,
            'unit_label' => 'box',
            'is_active' => true,
        ]);
        Inventory::create(['product_id' => $product->id, 'quantity' => 1000, 'reorder_level' => 5]);

        $nine = $this->line(CartService::enrichCartItems($this->cart([[$product, null, 9]]), $this->buyer), null);
        $this->assertFalse($nine['is_wholesale']);      // 90 pieces
        $this->assertEquals(500.00, $nine['unit_price']);

        $ten = $this->line(CartService::enrichCartItems($this->cart([[$product, null, 10]]), $this->buyer), null);
        $this->assertTrue($ten['is_wholesale']);        // 100 pieces
        $this->assertEquals(400.00, $ten['unit_price']);
        $this->assertSame(100, $ten['pieces']);
    }

    public function test_products_without_a_pack_size_behave_exactly_as_before(): void
    {
        $product = Product::create([
            'distributor_id' => $this->shop->id,
            'category_id' => $this->category->id,
            'name' => 'Thermometer',
            'description' => 'Digital',
            'sku' => 'THM-'.uniqid(),
            'base_price' => 500,
            'wholesale_price' => 400,
            'wholesale_min_qty' => 10,
            'is_active' => true,
        ]);
        Inventory::create(['product_id' => $product->id, 'quantity' => 100, 'reorder_level' => 5]);

        $this->assertSame(1, $product->packSize());
        $this->assertSame('piece', $product->fresh()->unit_label);

        $below = $this->line(CartService::enrichCartItems($this->cart([[$product, null, 9]]), $this->buyer), null);
        $at = $this->line(CartService::enrichCartItems($this->cart([[$product, null, 10]]), $this->buyer), null);

        $this->assertEquals(500.00, $below['unit_price']);
        $this->assertEquals(400.00, $at['unit_price']);
    }

    public function test_stock_is_totalled_in_pieces_when_options_have_different_pack_sizes(): void
    {
        [$product, $piece, $box] = $this->pieceProductWithBoxOption();
        Inventory::where('product_variation_id', $piece->id)->update(['quantity' => 500]);
        Inventory::where('product_variation_id', $box->id)->update(['quantity' => 100, 'reserved_quantity' => 3]);
        $product = $product->fresh();

        $this->assertTrue($product->hasMixedPacks());

        $pieces = $product->stockInPieces();
        $this->assertSame(1500, $pieces['quantity']);   // 500 pcs + 100 boxes x 10
        $this->assertSame(30, $pieces['reserved']);     // 3 boxes x 10
    }

    public function test_a_product_with_one_pack_size_is_not_mixed_and_counts_its_own_units(): void
    {
        $product = Product::create([
            'distributor_id' => $this->shop->id,
            'category_id' => $this->category->id,
            'name' => 'Face Masks',
            'description' => 'Surgical masks',
            'sku' => 'MSK-'.uniqid(),
            'base_price' => 500,
            'units_per_pack' => 10,
            'unit_label' => 'box',
            'is_active' => true,
        ]);
        Inventory::create(['product_id' => $product->id, 'quantity' => 40, 'reserved_quantity' => 5, 'reorder_level' => 5]);

        $this->assertFalse($product->hasMixedPacks());
        $this->assertSame(['quantity' => 400, 'reserved' => 50], $product->stockInPieces());
    }

    public function test_product_page_reports_the_piece_total_for_mixed_options(): void
    {
        [$product, $piece, $box] = $this->pieceProductWithBoxOption();
        Inventory::where('product_variation_id', $piece->id)->update(['quantity' => 500]);
        Inventory::where('product_variation_id', $box->id)->update(['quantity' => 100]);

        $this->actingAs($this->buyer)
            ->get(route('products.show', ['slug' => $product->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hasMixedPacks', true)
                ->where('availablePieces', 1500));
    }

    public function test_placing_an_order_prices_by_pieces_and_snapshots_the_pack(): void
    {
        [$product, $piece, $box] = $this->pieceProductWithBoxOption();
        Http::fake(['api.paymongo.com/*' => Http::response([
            'data' => ['id' => 'cs_1', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_1']],
        ], 200)]);

        $this->actingAs($this->buyer)->post(route('orders.place'), [
            'customer_name' => 'Juan Dela Cruz',
            'delivery_address' => '456 Patient Rd, Imus, Cavite',
            'contact_number' => '09171234567',
            'delivery_latitude' => 14.4297,
            'delivery_longitude' => 120.9367,
            'payment_method' => 'gcash',
            'fulfillment_method' => 'delivery',
            'buy_now' => 1,
            'product_id' => $product->id,
            'product_variation_id' => $box->id,
            'quantity' => 10,   // 10 boxes = 100 pieces
        ])->assertRedirect('https://checkout.paymongo.com/cs_1');

        $item = Order::where('customer_id', $this->buyer->id)->firstOrFail()->items()->firstOrFail();

        $this->assertTrue((bool) $item->is_wholesale);
        $this->assertEquals(400.00, (float) $item->unit_price);
        $this->assertSame(10, $item->units_per_pack);
        $this->assertSame('box', $item->unit_label);
        $this->assertEquals(4000.00, (float) $item->subtotal);
    }
}
