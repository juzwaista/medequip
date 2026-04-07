<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Admin / super_admin can do anything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return null;
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isCustomer($user, $order) || $this->isShopMember($user, $order);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isShopMember($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->isCustomer($user, $order);
    }

    public function confirmReceived(User $user, Order $order): bool
    {
        return $this->isCustomer($user, $order);
    }

    private function isCustomer(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id;
    }

    private function isShopMember(User $user, Order $order): bool
    {
        if ($user->role === 'distributor') {
            return $user->distributor?->id === $order->distributor_id;
        }

        if ($user->role === 'staff') {
            return $user->employer?->id === $order->distributor_id;
        }

        return false;
    }
}
