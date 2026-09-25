<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Admins now open a user (or shop) detail page first and take Warn / Suspend / Ban actions from
 * there, instead of acting straight from the list.
 */
class AdminUserDetailTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(string ...$permissions): User
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    private function asAdmin(User $admin): static
    {
        return $this->actingAs($admin)->withSession(['login.otp_verified' => true]);
    }

    private function shop(): Distributor
    {
        $owner = User::factory()->distributor()->create(['email_verified_at' => now()]);

        return Distributor::create([
            'user_id' => $owner->id,
            'company_name' => 'Acme Medical Supplies',
            'slug' => 'acme-'.uniqid(),
            'address' => '1 Acme St',
            'contact_number' => '09123456789',
            'email' => 'acme@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ]);
    }

    public function test_admin_can_view_a_user_detail_page_with_order_activity(): void
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => now(), 'name' => 'Juan Dela Cruz']);
        $shop = $this->shop();

        Order::create([
            'order_number' => 'ORD-DETAIL-1',
            'customer_id' => $customer->id,
            'distributor_id' => $shop->id,
            'status' => 'completed',
            'subtotal' => 500,
            'shipping_fee' => 50,
            'total_amount' => 550,
            'delivery_address' => '456 Patient Rd',
            'contact_number' => '09171234567',
            'payment_method' => 'gcash',
        ]);

        $this->asAdmin($this->adminWith('admin.users.manage'))
            ->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/UserManagement/Show')
                ->where('account.name', 'Juan Dela Cruz')
                ->where('account.banned_at', null)
                ->where('orderStats.total', 1)
                ->where('orderStats.completed', 1)
                ->where('orderStats.spent', 550)
                ->has('recentOrders', 1));
    }

    public function test_banned_user_detail_shows_ban_and_history_after_banning_from_the_page(): void
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        $admin = $this->adminWith('admin.users.manage');

        $this->asAdmin($admin)
            ->post(route('admin.users.ban', $customer), ['reason' => 'Fraudulent orders'])
            ->assertSessionHasNoErrors();

        $this->asAdmin($admin)
            ->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('account.ban_reason', 'Fraudulent orders')
                ->whereNot('account.banned_at', null)
                ->has('moderationHistory', 1)
                ->where('moderationHistory.0.action', 'user_banned')
                ->where('moderationHistory.0.reason', 'Fraudulent orders'));
    }

    public function test_admin_accounts_have_no_detail_page(): void
    {
        $otherAdmin = User::factory()->admin()->create(['email_verified_at' => now()]);

        $this->asAdmin($this->adminWith('admin.users.manage'))
            ->get(route('admin.users.show', $otherAdmin))
            ->assertNotFound();
    }

    public function test_user_detail_requires_the_users_manage_permission(): void
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);

        $this->asAdmin($this->adminWith('admin.reports.review'))
            ->get(route('admin.users.show', $customer))
            ->assertForbidden();
    }

    public function test_admin_who_can_manage_users_can_open_a_shop_detail_page_but_not_moderate_it(): void
    {
        $shop = $this->shop();

        $this->asAdmin($this->adminWith('admin.users.manage'))
            ->get(route('admin.distributors.show', $shop->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Distributors/Show')
                ->where('canModerate', false));
    }

    public function test_admin_with_moderate_permission_gets_moderation_controls_on_the_shop_page(): void
    {
        $shop = $this->shop();

        $this->asAdmin($this->adminWith('admin.applications.review', 'admin.distributors.moderate'))
            ->get(route('admin.distributors.show', $shop->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canModerate', true));
    }

    public function test_shop_detail_is_forbidden_without_either_permission(): void
    {
        $shop = $this->shop();

        $this->asAdmin($this->adminWith('admin.reports.review'))
            ->get(route('admin.distributors.show', $shop->id))
            ->assertForbidden();
    }
}
