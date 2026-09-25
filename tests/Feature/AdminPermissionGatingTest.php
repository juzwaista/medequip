<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regression coverage for extending admin.permission:X gating to routes that used to rely
 * only on the coarse role:admin,super_admin + otp check (see PROJECT_CONTEXT.md §11 problem
 * #11), and for AdminPermission's defensive handling of a permission that was never seeded.
 */
class AdminPermissionGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_permission_gets_403_not_a_crash_when_permission_is_unseeded(): void
    {
        // No Permission row exists at all for 'admin.users.manage' in a fresh test DB —
        // hasPermissionTo() would throw PermissionDoesNotExist if AdminPermission didn't
        // catch it; this proves it degrades to a clean 403 instead of an unhandled 500.
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)
            ->withSession(['login.otp_verified' => true])
            ->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_with_permission_can_access_gated_route(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        Permission::firstOrCreate(['name' => 'admin.users.manage']);
        $admin->givePermissionTo('admin.users.manage');

        $response = $this->actingAs($admin)
            ->withSession(['login.otp_verified' => true])
            ->get('/admin/users');

        $response->assertOk();
    }

    public function test_super_admin_bypasses_the_new_gates_without_any_permission_grant(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['login.otp_verified' => true])
            ->get('/admin/users');

        $response->assertOk();
    }

    public function test_admin_without_permission_is_blocked_from_business_profiles(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)
            ->withSession(['login.otp_verified' => true])
            ->get('/admin/business-profiles');

        $response->assertForbidden();
    }

    public function test_admin_without_permission_is_blocked_from_audit_logs(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)
            ->withSession(['login.otp_verified' => true])
            ->get('/admin/audit-logs');

        $response->assertForbidden();
    }
}
