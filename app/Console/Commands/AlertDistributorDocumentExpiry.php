<?php

namespace App\Console\Commands;

use App\Models\Distributor;
use App\Notifications\DocumentExpiryNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AlertDistributorDocumentExpiry extends Command
{
    protected $signature = 'medequip:alert-doc-expiry';
    protected $description = 'Check for expiring distributor documents and send alerts.';

    public function handle()
    {
        $this->info('Checking for expiring documents...');
        
        $distributors = Distributor::with('user')->get();
        $alertCount = 0;

        foreach ($distributors as $distributor) {
            if (!$distributor->user) continue;

            $expiringDocs = [];
            $docFields = [
                'valid_id_expires_at' => 'Primary Government ID',
                'business_license_expires_at' => 'Business Permit',
                'dti_sec_expires_at' => 'DTI Certificate / SEC',
                'bir_form_expires_at' => 'BIR Form 2303',
                'fda_license_expires_at' => 'FDA License to Operate',
                'prc_id_expires_at' => 'PRC ID',
            ];

            foreach ($docFields as $field => $label) {
                if ($distributor->$field) {
                    $expiry = Carbon::parse($distributor->$field);
                    // Alert if expiring within 30 days OR already expired within the last 7 days (to catch missed ones)
                    if ($expiry->isFuture() && $expiry->diffInDays(now()) <= 30) {
                        $expiringDocs[] = ['label' => $label, 'expiry' => $expiry];
                    }
                }
            }

            if (!empty($expiringDocs)) {
                $distributor->user->notify(new DocumentExpiryNotification($distributor, $expiringDocs));
                $this->line("Sent alert to {$distributor->company_name} (" . count($expiringDocs) . " docs)");
                $alertCount++;
            }
        }

        $this->info("Done. Sent {$alertCount} alerts.");
        
        Log::info('[Console] Document expiry alerts processed', ['alerts_sent' => $alertCount]);
        
        return 0;
    }
}
