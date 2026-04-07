<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'username' => 'testuser_reg',
            'email' => 'test@example.com',
            'password' => 'RegTestP4ss!',
            'password_confirmation' => 'RegTestP4ss!',
            'role' => 'distributor',
            'terms_accepted' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('products.index', [], false));
    }
}
