<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Changing the email on the profile page mails an OTP to the NEW address through an anonymous
 * notifiable. That used to 500 because the login-OTP notification read $notifiable->name and
 * routed to a database channel that anonymous notifiables don't have.
 */
class EmailChangeVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_email_sends_a_code_to_the_new_address_without_erroring(): void
    {
        $user = User::factory()->customer()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => 'new-address@example.test',
                'phone_number' => '09123456789',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('info');

        $user->refresh();
        $this->assertSame('new-address@example.test', $user->pending_email);
        $this->assertNotNull($user->login_otp);
        $this->assertNotSame('new-address@example.test', $user->email, 'primary email must not change until verified');
    }
}
