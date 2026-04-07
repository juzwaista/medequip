<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class CustomerReliabilityService
{
    /**
     * Rejection rate = (prescription rejections + seller-rejected orders) / total orders × 100.
     * COD disabled when rate is strictly greater than threshold.
     */
    public function codRejectionRatePercent(User $user): ?float
    {
        if ($user->role !== 'customer') {
            return null;
        }

        $total = Order::query()->where('customer_id', $user->id)->count();

        if ($total === 0) {
            return 0.0;
        }

        $rejected = Order::query()
            ->where('customer_id', $user->id)
            ->where(function ($q) {
                $q->where('prescription_status', Order::PRESCRIPTION_REJECTED)
                    ->orWhere('status', 'rejected');
            })
            ->count();

        return round(($rejected / $total) * 100, 2);
    }

    public function isCodAllowedForCustomer(User $user): bool
    {
        if ($user->role !== 'customer') {
            return true;
        }

        $rate = $this->codRejectionRatePercent($user);
        $threshold = (float) config('medequip.cod_rejection_threshold_percent', 15);

        return $rate <= $threshold;
    }
}
