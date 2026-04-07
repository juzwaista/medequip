<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return null;
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $this->isAssignedCourier($user, $delivery);
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $this->isAssignedCourier($user, $delivery);
    }

    public function accept(User $user, Delivery $delivery): bool
    {
        return $user->role === 'courier' && $delivery->courier_id === null;
    }

    private function isAssignedCourier(User $user, Delivery $delivery): bool
    {
        return $user->role === 'courier'
            && $user->courier?->id === $delivery->courier_id;
    }
}
