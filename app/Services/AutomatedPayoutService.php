<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class AutomatedPayoutService
{
    /**
     * Simulate an instant payout API transfer to a Distributor or Courier.
     *
     * @param float $amount
     * @param \App\Models\Distributor|\App\Models\Courier $recipient
     * @param \Illuminate\Database\Eloquent\Model $referenceModel (Payment or Delivery)
     * @return bool
     */
    public function disburse(float $amount, $recipient, $referenceModel): bool
    {
        if (empty($recipient->payout_bank) || empty($recipient->payout_account_number)) {
            Log::warning('Automated payout failed: Missing payout bank details.', [
                'amount' => $amount,
                'recipient_id' => $recipient->id,
                'recipient_type' => get_class($recipient)
            ]);
            return false;
        }

        try {
            $simulatedReferenceId = 'PAYOUT_' . strtoupper(uniqid());

            // If it's a seller payment
            if ($referenceModel instanceof \App\Models\Payment) {
                $referenceModel->update([
                    'seller_payout_cleared_at' => now(),
                ]);
            } elseif ($referenceModel instanceof \App\Models\Delivery) {
                // If it's a courier delivery
                $referenceModel->update([
                    'courier_payout_status' => 'paid',
                    'courier_paid_at' => now(),
                ]);
            }

            Log::info('Automated payout successful.', [
                'amount' => $amount,
                'reference_id' => $simulatedReferenceId,
                'recipient_id' => $recipient->id,
                'bank' => $recipient->payout_bank,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Automated payout API exception: ' . $e->getMessage());
            return false;
        }
    }
}
