<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Shop role templates (shop.manage-orders, shop.manage-inventory, ...) were never enforced, and
 * staff were locked out of suppliers / purchase orders altogether because those controllers looked
 * the staff member's shop up through a relation that doesn't exist.
 */
class ShopStaffPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Distributor $shop;

    private User $owner;

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
            'is_verified' => true,
            'shop_profile_onboarding_completed_at' => now(),
        ]);
    }

    /** A staff member of the shop whose role carries exactly the given shop permissions. */
    private function staffWith(string ...$permissions): User
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'distributor_id' => $this->shop->id,
            'email_verified_at' => now(),
        ]);

        setPermissionsTeamId($this->shop->id);
        $role = Role::create(['name' => 'Role '.uniqid(), 'guard_name' => 'web', 'distributor_id' => $this->shop->id]);
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $staff->assignRole($role);

        return $staff;
    }

    public function test_inventory_staff_can_use_inventory_suppliers_and_purchase_orders(): void
    {
        $staff = $this->staffWith('shop.manage-inventory');

        $this->actingAs($staff)->get(route('owner.inventory.index'))->assertOk();
        $this->actingAs($staff)->get(route('owner.suppliers.index'))->assertOk();
        $this->actingAs($staff)->get(route('owner.procurement.index'))->assertOk();
        $this->actingAs($staff)->get(route('owner.pos.index'))->assertOk();
    }

    public function test_inventory_staff_cannot_open_orders(): void
    {
        $staff = $this->staffWith('shop.manage-inventory');

        $this->actingAs($staff)->get(route('owner.orders.index'))->assertForbidden();
    }

    public function test_order_staff_can_open_orders_but_not_inventory_or_procurement(): void
    {
        $staff = $this->staffWith('shop.manage-orders');

        $this->actingAs($staff)->get(route('owner.orders.index'))->assertOk();
        $this->actingAs($staff)->get(route('owner.inventory.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('owner.suppliers.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('owner.procurement.index'))->assertForbidden();
    }

    public function test_staff_without_any_role_permission_is_blocked_from_both(): void
    {
        $staff = $this->staffWith();

        $this->actingAs($staff)->get(route('owner.orders.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('owner.inventory.index'))->assertForbidden();
    }

    public function test_the_shop_owner_is_never_blocked(): void
    {
        $this->actingAs($this->owner)->get(route('owner.orders.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('owner.inventory.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('owner.procurement.index'))->assertOk();
    }

    public function test_permissions_do_not_leak_across_shops(): void
    {
        $staff = $this->staffWith('shop.manage-orders');

        // Same staff member, but now employed by a different shop: the permission was granted in
        // the first shop's team, so it must not carry over.
        $otherOwner = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $otherShop = Distributor::create([
            'user_id' => $otherOwner->id,
            'company_name' => 'Other Shop',
            'slug' => 'other-'.uniqid(),
            'address' => '2 Other St',
            'contact_number' => '09123456780',
            'email' => 'other@test.test',
            'status' => 'approved',
            'is_verified' => true,
            'shop_profile_onboarding_completed_at' => now(),
        ]);
        $staff->update(['distributor_id' => $otherShop->id]);

        $this->actingAs($staff->fresh())->get(route('owner.orders.index'))->assertForbidden();
    }
}
