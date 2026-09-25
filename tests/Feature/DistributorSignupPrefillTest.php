<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Distributors used to type their company name, address and map pin at registration and then
 * type all of it again on the distributor application, because registration threw it away.
 */
class DistributorSignupPrefillTest extends TestCase
{
    use RefreshDatabase;

    private function registerDistributor(array $overrides = []): void
    {
        $this->post('/register', array_merge([
            'username' => 'acme_supplies',
            'email' => 'owner@acme.test',
            'password' => 'RegTestP4ss!',
            'password_confirmation' => 'RegTestP4ss!',
            'role' => 'distributor',
            'contact_number' => '09123456789',
            'company_name' => 'Acme Medical Supplies',
            'tin_number' => '123-456-789-000',
            'address_line' => 'Blk 5 Lot 10 Sampaguita St.',
            'city' => 'Imus',
            'barangay' => 'Anabu I-A',
            'latitude' => 14.4297,
            'longitude' => 120.9367,
            'terms_accepted' => true,
        ], $overrides))->assertSessionHasNoErrors();
    }

    public function test_registration_keeps_a_distributors_company_and_address(): void
    {
        $this->registerDistributor();

        $user = User::where('email', 'owner@acme.test')->firstOrFail();
        $this->assertSame('Acme Medical Supplies', $user->company_name);
        $this->assertSame('123-456-789-000', $user->tin);

        $address = $user->addresses()->firstOrFail();
        $this->assertSame('Imus', $address->city);
        $this->assertSame('Blk 5 Lot 10 Sampaguita St.', $address->address_line);
        $this->assertTrue((bool) $address->is_default);
    }

    public function test_distributor_application_form_is_prefilled_from_registration(): void
    {
        $this->registerDistributor();
        $user = User::where('email', 'owner@acme.test')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('owner.distributors.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Owner/Distributor/Create')
                ->where('existingDistributor.company_name', 'Acme Medical Supplies')
                ->where('existingDistributor.address_line', 'Blk 5 Lot 10 Sampaguita St.')
                ->where('existingDistributor.city', 'Imus')
                ->where('existingDistributor.barangay', 'Anabu I-A')
                ->where('ownerPhone', '09123456789')
                ->where('ownerEmail', 'owner@acme.test')
                // A prefill is not a rejected application, so it must not carry rejection data.
                ->missing('existingDistributor.rejection_reason'));
    }

    public function test_distributor_can_still_register_without_an_address(): void
    {
        $this->registerDistributor([
            'address_line' => null,
            'city' => null,
            'barangay' => null,
            'latitude' => null,
            'longitude' => null,
        ]);

        $user = User::where('email', 'owner@acme.test')->firstOrFail();
        $this->assertSame(0, $user->addresses()->count());
        $this->assertSame('Acme Medical Supplies', $user->company_name);
    }
}
