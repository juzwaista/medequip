<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeDeactivatedAccounts extends Command
{
    protected $signature = 'accounts:purge-deactivated';
    protected $description = 'Permanently delete accounts that have been deactivated for more than 30 days.';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);

        $users = User::whereNotNull('deactivated_at')
            ->where('deactivated_at', '<=', $cutoff)
            ->get();

        foreach ($users as $user) {
            Log::warning('[PurgeDeactivatedAccounts] Permanently deleting account', [
                'user_id'         => $user->id,
                'email'           => $user->email,
                'deactivated_at'  => $user->deactivated_at,
            ]);
            $user->forceDelete();
        }

        $this->info("Purged {$users->count()} deactivated account(s).");
        return Command::SUCCESS;
    }
}
