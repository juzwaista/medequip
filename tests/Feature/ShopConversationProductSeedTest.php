<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopConversationProductSeedTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        return Category::query()->create([
            'name' => 'Test Category',
            'slug' => 'test-cat-'.uniqid(),
            'description' => null,
            'parent_id' => null,
        ]);
    }

    public function test_messages_start_with_product_seeds_opening_message_once(): void
    {
        $seller = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $distributor = Distributor::create([
            'user_id' => $seller->id,
            'company_name' => 'Seed Shop',
            'address' => '1 St',
            'contact_number' => '09123456789',
            'email' => 'seed@example.test',
            'status' => 'approved',
            'slug' => 'seed-shop-'.uniqid(),
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $buyer = User::factory()->customer()->create(['email_verified_at' => now()]);

        $product = Product::create([
            'distributor_id' => $distributor->id,
            'category_id' => $this->makeCategory()->id,
            'name' => 'Test Oximeter',
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'test-oximeter-'.uniqid(),
            'description' => 'Desc',
            'base_price' => 10,
            'is_active' => true,
            'image_path' => null,
        ]);

        $this->actingAs($buyer)->get(route('messages.start', [
            'distributor_id' => $distributor->id,
            'product_id' => $product->id,
        ]))->assertRedirect();

        $conv = Conversation::query()
            ->where('customer_id', $buyer->id)
            ->where('distributor_id', $distributor->id)
            ->firstOrFail();

        $messages = $conv->messages()->orderBy('id')->get();
        $this->assertCount(1, $messages);
        $this->assertSame($buyer->id, (int) $messages[0]->user_id);
        $this->assertStringContainsString('Test Oximeter', $messages[0]->body);
        $this->assertStringContainsString('/products/'.$product->id, $messages[0]->body);

        $this->actingAs($buyer)->get(route('messages.start', [
            'distributor_id' => $distributor->id,
            'product_id' => $product->id,
        ]))->assertRedirect();

        $this->assertSame(1, $conv->messages()->count());
    }

    public function test_product_seed_appends_when_conversation_already_has_messages(): void
    {
        $seller = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $distributor = Distributor::create([
            'user_id' => $seller->id,
            'company_name' => 'Thread Shop',
            'address' => '1 St',
            'contact_number' => '09123456789',
            'email' => 'thread@example.test',
            'status' => 'approved',
            'slug' => 'thread-shop-'.uniqid(),
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $buyer = User::factory()->customer()->create(['email_verified_at' => now()]);

        $product = Product::create([
            'distributor_id' => $distributor->id,
            'category_id' => $this->makeCategory()->id,
            'name' => 'Pulse Monitor',
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'pulse-monitor-'.uniqid(),
            'description' => 'Desc',
            'base_price' => 12,
            'is_active' => true,
            'image_path' => null,
        ]);

        $this->actingAs($buyer)->get(route('messages.start', [
            'distributor_id' => $distributor->id,
        ]))->assertRedirect();

        $conv = Conversation::query()
            ->where('customer_id', $buyer->id)
            ->where('distributor_id', $distributor->id)
            ->firstOrFail();

        $this->actingAs($buyer)->postJson(route('messages.messages.store', $conv), [
            'body' => 'Do you have this in stock?',
        ])->assertCreated();

        $this->assertSame(1, $conv->refresh()->messages()->count());

        $this->actingAs($buyer)->get(route('messages.start', [
            'distributor_id' => $distributor->id,
            'product_id' => $product->id,
        ]))->assertRedirect();

        $this->assertSame(2, $conv->refresh()->messages()->count());
        $productMessage = $conv->messages()->orderByDesc('id')->first();
        $this->assertSame($buyer->id, (int) $productMessage->user_id);
        $this->assertStringContainsString('Pulse Monitor', $productMessage->body);
        $this->assertStringContainsString('/products/'.$product->id, $productMessage->body);
    }

    public function test_auto_reply_fires_after_product_seed_when_template_set(): void
    {
        $seller = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $distributor = Distributor::create([
            'user_id' => $seller->id,
            'company_name' => 'Auto Shop',
            'address' => '1 St',
            'contact_number' => '09123456789',
            'email' => 'auto@example.test',
            'status' => 'approved',
            'slug' => 'auto-shop-'.uniqid(),
            'chat_auto_reply' => 'Hi {customer_name}, thanks for messaging {shop_name}.',
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $buyer = User::factory()->customer()->create([
            'email_verified_at' => now(),
            'name' => 'Jamie Buyer',
        ]);

        $product = Product::create([
            'distributor_id' => $distributor->id,
            'category_id' => $this->makeCategory()->id,
            'name' => 'Widget',
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'widget-'.uniqid(),
            'description' => 'Desc',
            'base_price' => 5,
            'is_active' => true,
            'image_path' => null,
        ]);

        $this->actingAs($buyer)->get(route('messages.start', [
            'distributor_id' => $distributor->id,
            'product_id' => $product->id,
        ]))->assertRedirect();

        $conv = Conversation::query()
            ->where('customer_id', $buyer->id)
            ->where('distributor_id', $distributor->id)
            ->firstOrFail();

        $messages = $conv->messages()->orderBy('id')->get();
        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]->kind);
        $this->assertSame('system', $messages[1]->kind);
        $this->assertStringContainsString('Jamie Buyer', $messages[1]->body);
        $this->assertStringContainsString('Auto Shop', $messages[1]->body);
    }

    public function test_first_manual_customer_message_still_triggers_auto_reply_when_no_seed(): void
    {
        $seller = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $distributor = Distributor::create([
            'user_id' => $seller->id,
            'company_name' => 'Plain Shop',
            'address' => '1 St',
            'contact_number' => '09123456789',
            'email' => 'plain@example.test',
            'status' => 'approved',
            'slug' => 'plain-shop-'.uniqid(),
            'chat_auto_reply' => 'Hello {customer_name} from {shop_name}.',
            'shop_profile_onboarding_completed_at' => now(),
        ]);

        $buyer = User::factory()->customer()->create([
            'email_verified_at' => now(),
            'name' => 'Pat Customer',
        ]);

        $this->actingAs($buyer)->get(route('messages.start', [
            'distributor_id' => $distributor->id,
        ]))->assertRedirect();

        $conv = Conversation::query()
            ->where('customer_id', $buyer->id)
            ->where('distributor_id', $distributor->id)
            ->firstOrFail();

        $this->assertSame(0, $conv->messages()->count());

        $this->actingAs($buyer)->postJson(route('messages.messages.store', $conv), [
            'body' => 'Hi there',
        ])->assertCreated();

        $conv->refresh();
        $this->assertSame(2, $conv->messages()->count());
        $this->assertSame('system', $conv->messages()->orderByDesc('id')->first()->kind);
    }
}
