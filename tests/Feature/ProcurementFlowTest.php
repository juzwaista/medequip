<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Distributor;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Supplier;
use App\Models\SupplierPurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The procurement models (Supplier, SupplierPurchaseOrder, SupplierPurchaseOrderItem) used to be
 * empty stubs with no fillable attributes or relations, so no supplier could be created at all
 * (MassAssignmentException) and every PO screen depended on relations that didn't exist.
 */
class ProcurementFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Distributor $shop;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $this->shop = Distributor::create([
            'user_id' => $this->owner->id,
            'company_name' => 'Acme Medical Supplies',
            'slug' => 'acme-'.uniqid(),
            'address' => '1 Acme St',
            'contact_number' => '09123456789',
            'email' => 'acme@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $this->product = Product::create([
            'distributor_id' => $this->shop->id,
            'category_id' => $category->id,
            'name' => 'Surgical Gloves',
            'description' => 'Nitrile gloves',
            'sku' => 'GLV-'.uniqid(),
            'base_price' => 50,
            'is_active' => true,
        ]);
        Inventory::create(['product_id' => $this->product->id, 'quantity' => 100, 'reorder_level' => 5]);
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $this->actingAs($this->owner)
            ->post(route('owner.suppliers.store'), array_merge([
                'name' => 'Sunrise Glove Factory',
                'contact_person' => 'Maria Santos',
                'email' => null,
                'phone' => '09181234567',
                'address' => 'Laguna Technopark',
                'notes' => 'Net 30',
            ], $overrides))
            ->assertSessionHasNoErrors();

        return Supplier::where('distributor_id', $this->shop->id)->firstOrFail();
    }

    public function test_distributor_can_create_a_supplier(): void
    {
        $supplier = $this->createSupplier();

        $this->assertSame('Sunrise Glove Factory', $supplier->name);
        $this->assertSame($this->shop->id, $supplier->distributor_id);
    }

    public function test_supplier_list_renders_for_the_owner(): void
    {
        $this->createSupplier();

        $this->actingAs($this->owner)
            ->get(route('owner.suppliers.index'))
            ->assertOk();
    }

    public function test_purchase_order_can_be_created_viewed_and_received_into_stock(): void
    {
        $supplier = $this->createSupplier();

        $this->actingAs($this->owner)
            ->post(route('owner.procurement.store'), [
                'supplier_id' => $supplier->id,
                'notes' => 'Deliver to main warehouse',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity_ordered' => 200, 'unit_cost' => 35],
                ],
            ])
            ->assertRedirect(route('owner.procurement.index'));

        $po = SupplierPurchaseOrder::where('distributor_id', $this->shop->id)->firstOrFail();
        $this->assertSame('draft', $po->status);
        $this->assertEquals(7000.00, (float) $po->total_amount);
        $this->assertCount(1, $po->items);
        $this->assertSame($supplier->id, $po->supplier->id);

        $this->actingAs($this->owner)
            ->get(route('owner.procurement.show', $po))
            ->assertOk();

        // Send (supplier has no email, so no PDF/email is attempted), then receive.
        $this->actingAs($this->owner)
            ->post(route('owner.procurement.status', $po), ['status' => 'sent'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('owner.procurement.status', $po), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $po->fresh()->status);
        $this->assertSame(200, $po->items()->first()->quantity_received);
        $this->assertSame(300, (int) $this->product->inventory()->first()->quantity);
    }

    /** A gloves product sold as "Piece" and "Box of 10", each with its own stock row. */
    private function productWithVariations(): array
    {
        $product = Product::create([
            'distributor_id' => $this->shop->id,
            'category_id' => $this->product->category_id,
            'name' => 'Nitrile Gloves',
            'description' => 'Nitrile gloves',
            'sku' => 'NG-'.uniqid(),
            'base_price' => 50,
            'is_active' => true,
        ]);
        $piece = ProductVariation::create(['product_id' => $product->id, 'option_name' => 'Pack', 'option_value' => 'Piece', 'combination' => ['Pack' => 'Piece'], 'is_active' => true, 'sort_order' => 0]);
        $box = ProductVariation::create(['product_id' => $product->id, 'option_name' => 'Pack', 'option_value' => 'Box of 10', 'combination' => ['Pack' => 'Box of 10'], 'is_active' => true, 'units_per_pack' => 10, 'unit_label' => 'box', 'sort_order' => 1]);
        Inventory::create(['product_id' => $product->id, 'product_variation_id' => $piece->id, 'quantity' => 500, 'reorder_level' => 5]);
        Inventory::create(['product_id' => $product->id, 'product_variation_id' => $box->id, 'quantity' => 100, 'reorder_level' => 5]);

        return [$product, $piece, $box];
    }

    private function draftPo(Supplier $supplier, array $items): SupplierPurchaseOrder
    {
        $this->actingAs($this->owner)
            ->post(route('owner.procurement.store'), ['supplier_id' => $supplier->id, 'items' => $items])
            ->assertSessionHasNoErrors();

        return SupplierPurchaseOrder::latest('id')->firstOrFail();
    }

    private function setStatus(SupplierPurchaseOrder $po, string $status)
    {
        return $this->actingAs($this->owner)->post(route('owner.procurement.status', $po), ['status' => $status]);
    }

    public function test_an_option_must_be_chosen_for_a_product_with_variations(): void
    {
        [$product] = $this->productWithVariations();
        [, $otherPiece] = $this->productWithVariations();
        $supplier = $this->createSupplier();

        $this->actingAs($this->owner)
            ->post(route('owner.procurement.store'), [
                'supplier_id' => $supplier->id,
                'items' => [['product_id' => $product->id, 'quantity_ordered' => 5, 'unit_cost' => 400]],
            ])
            ->assertSessionHasErrors('items.0.product_variation_id');

        // ...and it has to be one of that product's own options.
        $this->actingAs($this->owner)
            ->post(route('owner.procurement.store'), [
                'supplier_id' => $supplier->id,
                'items' => [['product_id' => $product->id, 'product_variation_id' => $otherPiece->id, 'quantity_ordered' => 5, 'unit_cost' => 400]],
            ])
            ->assertSessionHasErrors('items.0.product_variation_id');

        $this->assertDatabaseCount('supplier_purchase_orders', 0);
    }

    public function test_received_stock_goes_to_the_ordered_variation_only(): void
    {
        [$product, $piece, $box] = $this->productWithVariations();
        $po = $this->draftPo($this->createSupplier(), [
            ['product_id' => $product->id, 'product_variation_id' => $box->id, 'quantity_ordered' => 20, 'unit_cost' => 350],
        ]);

        $this->setStatus($po, 'sent')->assertSessionHasNoErrors();
        $this->setStatus($po, 'completed')->assertSessionHasNoErrors();

        $stock = fn ($variation) => (int) Inventory::where('product_id', $product->id)->where('product_variation_id', $variation->id)->value('quantity');
        $this->assertSame(120, $stock($box));   // 100 + 20 boxes
        $this->assertSame(500, $stock($piece)); // untouched
    }

    public function test_a_delivery_can_be_received_in_parts(): void
    {
        [$product, , $box] = $this->productWithVariations();
        $po = $this->draftPo($this->createSupplier(), [
            ['product_id' => $product->id, 'product_variation_id' => $box->id, 'quantity_ordered' => 20, 'unit_cost' => 350],
        ]);
        $itemId = $po->items()->first()->id;
        $this->setStatus($po, 'sent');

        $receive = fn (int $qty) => $this->actingAs($this->owner)
            ->post(route('owner.procurement.receive', $po), ['quantities' => [$itemId => $qty]]);
        $boxStock = fn () => (int) Inventory::where('product_variation_id', $box->id)->value('quantity');

        $receive(8)->assertSessionHasNoErrors();
        $this->assertSame('partially_received', $po->fresh()->status);
        $this->assertSame(108, $boxStock());

        // Can't receive more than is outstanding: 12 left, asking for 50 only adds 12.
        $receive(50)->assertSessionHasNoErrors();
        $this->assertSame('completed', $po->fresh()->status);
        $this->assertSame(120, $boxStock());
        $this->assertSame(20, $po->items()->first()->quantity_received);
    }

    public function test_receive_all_remaining_after_a_partial_delivery_only_adds_the_rest(): void
    {
        $po = $this->draftPo($this->createSupplier(), [
            ['product_id' => $this->product->id, 'quantity_ordered' => 50, 'unit_cost' => 10],
        ]);
        $itemId = $po->items()->first()->id;
        $this->setStatus($po, 'sent');

        $this->actingAs($this->owner)->post(route('owner.procurement.receive', $po), ['quantities' => [$itemId => 30]]);
        $this->setStatus($po, 'completed')->assertSessionHasNoErrors();

        $this->assertSame(150, (int) $this->product->inventory()->first()->quantity); // 100 + 30 + 20
    }

    public function test_status_cannot_be_moved_out_of_a_final_state_or_restocked_twice(): void
    {
        $po = $this->draftPo($this->createSupplier(), [
            ['product_id' => $this->product->id, 'quantity_ordered' => 50, 'unit_cost' => 10],
        ]);

        $this->setStatus($po, 'completed')->assertSessionHas('error');   // draft can't jump to completed
        $this->setStatus($po, 'sent');
        $this->setStatus($po, 'completed');
        $this->assertSame(150, (int) $this->product->inventory()->first()->quantity);

        $this->setStatus($po, 'sent')->assertSessionHas('error');        // completed is final
        $this->setStatus($po, 'completed')->assertSessionHas('error');
        $this->assertSame(150, (int) $this->product->inventory()->first()->quantity);
    }

    public function test_orders_cannot_use_another_shops_supplier_or_product(): void
    {
        $mine = $this->createSupplier();

        $otherOwner = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $other = Distributor::create([
            'user_id' => $otherOwner->id, 'company_name' => 'Other Shop', 'slug' => 'other-'.uniqid(),
            'address' => '2 Other St', 'contact_number' => '09123456780', 'email' => 'o@test.test', 'status' => 'approved',
        ]);
        $theirSupplier = Supplier::create(['distributor_id' => $other->id, 'name' => 'Their Supplier']);
        $theirProduct = Product::create([
            'distributor_id' => $other->id, 'category_id' => $this->product->category_id, 'name' => 'Theirs',
            'description' => 'x', 'sku' => 'T-'.uniqid(), 'base_price' => 1, 'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->post(route('owner.procurement.store'), [
                'supplier_id' => $theirSupplier->id,
                'items' => [['product_id' => $this->product->id, 'quantity_ordered' => 1, 'unit_cost' => 1]],
            ])->assertSessionHasErrors('supplier_id');

        $this->actingAs($this->owner)
            ->post(route('owner.procurement.store'), [
                'supplier_id' => $mine->id,
                'items' => [['product_id' => $theirProduct->id, 'quantity_ordered' => 1, 'unit_cost' => 1]],
            ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_sending_a_po_emails_the_supplier_a_pdf(): void
    {
        Mail::fake();
        $supplier = $this->createSupplier(['email' => 'orders@sunrise-gloves.test']);
        $po = $this->draftPo($supplier, [
            ['product_id' => $this->product->id, 'quantity_ordered' => 5, 'unit_cost' => 10],
        ]);

        $this->setStatus($po, 'sent')->assertSessionHas('success');

        $this->assertSame('sent', $po->fresh()->status);
        Mail::assertSent(\App\Mail\SupplierPurchaseOrderMail::class, fn ($mail) => $mail->hasTo('orders@sunrise-gloves.test') && str_starts_with($mail->pdfContent, '%PDF'));
    }

    public function test_a_mail_failure_does_not_undo_the_status_change(): void
    {
        $supplier = $this->createSupplier(['email' => 'orders@sunrise-gloves.test']);
        $po = $this->draftPo($supplier, [
            ['product_id' => $this->product->id, 'quantity_ordered' => 5, 'unit_cost' => 10],
        ]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->setStatus($po, 'sent')->assertSessionHas('warning');

        $this->assertSame('sent', $po->fresh()->status);
    }

    public function test_the_create_form_lists_stock_and_options_for_the_owners_products_only(): void
    {
        $this->productWithVariations();
        $this->createSupplier();

        $this->actingAs($this->owner)
            ->get(route('owner.procurement.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Procurement/CreatePO')
                ->has('products', 2)
                ->where('products.0.name', 'Nitrile Gloves')
                ->has('products.0.variations', 2)
                ->where('products.0.variations.1.units_per_pack', 10)
                ->where('products.1.stock', 100)
                ->where('products.1.variations', []));
    }

    public function test_another_shops_purchase_order_is_not_accessible(): void
    {
        $supplier = $this->createSupplier();
        $po = SupplierPurchaseOrder::create([
            'distributor_id' => $this->shop->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-TEST-1',
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        $intruder = User::factory()->distributor()->create(['email_verified_at' => now()]);
        Distributor::create([
            'user_id' => $intruder->id,
            'company_name' => 'Other Shop',
            'slug' => 'other-'.uniqid(),
            'address' => '2 Other St',
            'contact_number' => '09123456780',
            'email' => 'other@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->get(route('owner.procurement.show', $po))
            ->assertForbidden();
    }
}
