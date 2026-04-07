<?php

use App\Models\User;
use App\Notifications\WelcomeToMedequip;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $type = WelcomeToMedequip::class;

        User::query()
            ->whereDoesntHave('notifications', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $user->notify(new WelcomeToMedequip);
                }
            });
    }

    public function down(): void
    {
        // Leave rows; do not mass-delete user notifications on rollback
    }
};
