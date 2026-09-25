<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for consolidating RoleMiddleware + EnsureDistributorVerified into a
 * single source of truth for distributor/staff shop-status gating, and for extending that
 * gating to staff accounts (see PROJECT_CONTEXT.md §11 problems #9-#10).
 */
class DistributorAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function makeDistributor(array $overrides = []): Distributor
    {
        $owner = User::factory()->distributor()->create(['email_verified_at' => now()]);

        return Distributor::create(array_merge([
            'user_id' => $owner->id,
            'company_name' => 'Test Medical Supplies',
            'slug' => 'test-medical-'.uniqid(),
            'address' => '123 Health St',
            'contact_number' => '09123456789',
            'email' => 'shop@test.test',
            'status' => 'approved',
            'shop_profile_onboarding_completed_at' => now(),
        ], $overrides));
    }

    protected function makeStaffFor(Distributor $distributor): User
    {
        return User::factory()->staff()->create([
            'email_verified_at' => now(),
            'distributor_id' => $distributor->id,
        ]);
    }

    public function test_banned_distributor_is_logged_out_with_ban_message_not_generic_redirect(): void
    {
        // Before the middleware consolidation, RoleMiddleware's own duplicate status check ran
        // first and short-circuited the request with a generic "complete your application"
        // redirect, so EnsureDistributorVerified's banned-specific forced logout never executed.
        $distributor = $this->makeDistributor(['status' => 'banned']);

        $response = $this->actingAs($distributor->user)->get(route('owner.orders.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Your distributor account has been permanently banned.');
        $this->assertGuest();
    }

    public function test_staff_of_banned_distributor_is_also_logged_out(): void
    {
        $distributor = $this->makeDistributor(['status' => 'banned']);
        $staff = $this->makeStaffFor($distributor);

        $response = $this->actingAs($staff)->get(route('owner.orders.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_of_suspended_distributor_is_blocked_from_restricted_routes(): void
    {
        // is_suspended is a computed accessor (suspended_until->isFuture()), not a real column.
        $distributor = $this->makeDistributor(['suspended_until' => now()->addDays(7)]);
        $staff = $this->makeStaffFor($distributor);

        $response = $this->actingAs($staff)->get(route('owner.inventory.index'));

        $response->assertRedirect(route('owner.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_staff_of_suspended_distributor_can_still_reach_a_non_restricted_route(): void
    {
        // Uses orders.index rather than dashboard: the dashboard route hits an unrelated,
        // pre-existing bug (a MySQL-only FIELD() call with no SQLite guard in a DSS query)
        // when actually rendering in the test DB — irrelevant to what this test verifies,
        // which is only that the middleware itself doesn't block this route.
        $distributor = $this->makeDistributor(['suspended_until' => now()->addDays(7)]);
        $staff = $this->makeStaffFor($distributor);

        $response = $this->actingAs($staff)->get(route('owner.orders.index'));

        $response->assertOk();
    }

    public function test_staff_of_pending_distributor_is_redirected_away_from_owner_portal(): void
    {
        $distributor = $this->makeDistributor(['status' => 'pending']);
        $staff = $this->makeStaffFor($distributor);

        $response = $this->actingAs($staff)->get(route('owner.orders.index'));

        $response->assertRedirect('/products');
        $response->assertSessionHas('error');
    }

    public function test_staff_with_no_employer_link_is_logged_out(): void
    {
        $staff = User::factory()->staff()->create([
            'email_verified_at' => now(),
            'distributor_id' => null,
        ]);

        $response = $this->actingAs($staff)->get(route('owner.orders.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_of_approved_distributor_has_normal_access(): void
    {
        $distributor = $this->makeDistributor();
        $staff = $this->makeStaffFor($distributor);

        $response = $this->actingAs($staff)->get(route('owner.orders.index'));

        $response->assertOk();
    }

    public function test_pending_owner_is_still_redirected_to_pending_page(): void
    {
        // Regression: this behavior must be unchanged now that only EnsureDistributorVerified
        // (not RoleMiddleware) is responsible for it.
        $distributor = $this->makeDistributor(['status' => 'pending']);

        $response = $this->actingAs($distributor->user)->get(route('owner.orders.index'));

        $response->assertRedirect(route('owner.distributors.pending'));
    }

    public function test_owner_with_no_distributor_profile_is_sent_to_application_form(): void
    {
        $owner = User::factory()->distributor()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($owner)->get(route('owner.orders.index'));

        $response->assertRedirect(route('owner.distributors.create'));
    }
}
