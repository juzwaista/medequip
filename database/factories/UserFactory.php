<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Plain text: User model uses 'password' => 'hashed' cast. Must satisfy Password::defaults() in tests.
            'password' => static::$password ??= 'CurrentP4ss!',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(function (\App\Models\User $user) use ($role) {
            $user->forceFill(['role' => $role])->save();
        });
    }

    public function customer(): static
    {
        return $this->withRole('customer');
    }

    public function distributor(): static
    {
        return $this->withRole('distributor');
    }

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    public function courier(): static
    {
        return $this->withRole('courier');
    }

    public function staff(): static
    {
        return $this->withRole('staff');
    }
}
