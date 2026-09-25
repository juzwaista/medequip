<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression coverage for the B2B ("Apply for Corporate Account") flow. Every submit used to
 * 500 because BusinessAccountController instantiated `new SafeUpload` without its required
 * allowed-extensions argument.
 */
class BusinessAccountApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->customer = User::factory()->customer()->create(['email_verified_at' => now()]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'St. Jude Hospital',
            'business_type' => 'Hospital',
            'tin_number' => '123-456-789-000',
        ], $overrides);
    }

    public function test_apply_page_renders(): void
    {
        $this->actingAs($this->customer)
            ->get(route('business-account.apply'))
            ->assertOk();
    }

    public function test_customer_can_submit_application_without_a_document(): void
    {
        $this->actingAs($this->customer)
            ->post(route('business-account.store'), $this->payload())
            ->assertRedirect(route('business-account.status'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('business_profiles', [
            'user_id' => $this->customer->id,
            'company_name' => 'St. Jude Hospital',
            'status' => 'pending',
            'sec_dti_document_path' => null,
        ]);
    }

    public function test_customer_can_submit_application_with_a_pdf_document(): void
    {
        $this->actingAs($this->customer)
            ->post(route('business-account.store'), $this->payload([
                'sec_dti_document' => UploadedFile::fake()->create('sec.pdf', 200, 'application/pdf'),
            ]))
            ->assertRedirect(route('business-account.status'));

        $profile = BusinessProfile::where('user_id', $this->customer->id)->firstOrFail();
        $this->assertNotNull($profile->sec_dti_document_path);
        Storage::disk('public')->assertExists($profile->sec_dti_document_path);
    }

    public function test_dangerous_document_types_are_rejected_with_a_validation_error(): void
    {
        $this->actingAs($this->customer)
            ->post(route('business-account.store'), $this->payload([
                'sec_dti_document' => UploadedFile::fake()->create('shell.php', 5, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('sec_dti_document');

        $this->assertDatabaseCount('business_profiles', 0);
    }

    public function test_status_page_renders_after_applying(): void
    {
        BusinessProfile::create([
            'user_id' => $this->customer->id,
            'company_name' => 'St. Jude Hospital',
            'business_type' => 'Hospital',
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer)
            ->get(route('business-account.status'))
            ->assertOk();
    }

    public function test_missing_document_can_be_uploaded_later(): void
    {
        BusinessProfile::create([
            'user_id' => $this->customer->id,
            'company_name' => 'St. Jude Hospital',
            'business_type' => 'Hospital',
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer)
            ->post(route('business-account.upload-document'), [
                'sec_dti_document' => UploadedFile::fake()->create('dti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $profile = BusinessProfile::where('user_id', $this->customer->id)->firstOrFail();
        $this->assertNotNull($profile->sec_dti_document_path);
        Storage::disk('public')->assertExists($profile->sec_dti_document_path);
    }
}
