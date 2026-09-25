<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Approving a distributor application used to set only status = 'approved'. is_verified (which
 * gates branches, distributor wholesale buying and the public shop list) was never set anywhere
 * in the app for a new shop, so no shop approved through the admin UI ever became verified.
 */
class DistributorVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private Distributor $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
        $this->owner = User::factory()->distributor()->create(['email_verified_at' => now()]);
        $this->shop = Distributor::create([
            'user_id' => $this->owner->id,
            'company_name' => 'Acme Medical Supplies',
            'slug' => 'acme-'.uniqid(),
            'address' => '1 Acme St',
            'contact_number' => '09123456789',
            'email' => 'acme@test.test',
            'status' => 'pending',
            'is_verified' => false,
        ]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['login.otp_verified' => true]);
    }

    public function test_approving_an_application_verifies_the_shop_and_unlocks_wholesale(): void
    {
        $this->assertFalse($this->owner->fresh()->canAccessWholesale());

        $this->asAdmin()->post(route('admin.distributors.approve', $this->shop->id))->assertSessionHasNoErrors();

        $shop = $this->shop->fresh();
        $this->assertSame('approved', $shop->status);
        $this->assertTrue($shop->is_verified);
        $this->assertTrue($this->owner->fresh()->canAccessWholesale());
    }

    public function test_rejecting_removes_verification(): void
    {
        $this->shop->update(['status' => 'approved', 'is_verified' => true]);

        $this->asAdmin()->post(route('admin.distributors.reject', $this->shop->id), [
            'reason' => 'Blurry documents',
        ])->assertSessionHasNoErrors();

        $shop = $this->shop->fresh();
        $this->assertSame('rejected', $shop->status);
        $this->assertFalse($shop->is_verified);
    }

    public function test_banning_removes_verification(): void
    {
        $this->shop->update(['status' => 'approved', 'is_verified' => true]);

        $this->asAdmin()->post(route('admin.distributors.ban', $this->shop->id), [
            'reason' => 'Counterfeit goods',
        ])->assertSessionHasNoErrors();

        $shop = $this->shop->fresh();
        $this->assertSame('banned', $shop->status);
        $this->assertFalse($shop->is_verified);
    }
}
