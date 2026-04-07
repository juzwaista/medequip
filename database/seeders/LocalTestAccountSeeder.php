<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotent dev login — run after a DB wipe: php artisan db:seed --class=LocalTestAccountSeeder
 * Optional .env: DEV_LOGIN_EMAIL, DEV_LOGIN_PASSWORD (must meet Password::defaults())
 */
class LocalTestAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Skipped: only runs in APP_ENV=local.');

            return;
        }

        $email = env('DEV_LOGIN_EMAIL', 'dev@medequip.test');
        $password = env('DEV_LOGIN_PASSWORD', 'DevTestP4ss!');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Dev User',
                'username' => 'devuser',
                'password' => $password,
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'terms_version' => User::CURRENT_TERMS_VERSION,
            ]
        );

        $user->forceFill(['role' => 'customer'])->save();

        $this->command?->info("Dev account ready: {$email} / (your DEV_LOGIN_PASSWORD or default DevTestP4ss!)");
    }
}
