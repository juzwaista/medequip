<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsernameAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_taken_username_returns_unavailable(): void
    {
        User::factory()->create([
            'username' => 'existing_user',
            'email' => 'existing@example.com',
        ]);

        $this->get('/register/username-available?username=existing_user')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'available' => false,
            ]);
    }

    public function test_unused_username_returns_available(): void
    {
        $this->get('/register/username-available?username=new_unique_name')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'available' => true,
            ]);
    }

    public function test_invalid_format_returns_not_valid(): void
    {
        $this->get('/register/username-available?username=abc')
            ->assertOk()
            ->assertJson([
                'valid' => false,
                'available' => false,
            ]);
    }

    public function test_too_long_username_returns_not_valid(): void
    {
        $this->get('/register/username-available?username='.str_repeat('a', 21))
            ->assertOk()
            ->assertJson([
                'valid' => false,
                'available' => false,
            ]);
    }
}
