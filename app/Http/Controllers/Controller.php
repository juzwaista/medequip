<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Get the active distributor for the current user.
     * Works for both 'distributor' owners and 'staff' employees.
     */
    protected function getDistributor(): ?\App\Models\Distributor
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->role === 'staff' ? $user->employer : $user->distributor;
    }

    /**
     * URL to open or create a shop conversation (verified users only; not own shop).
     */
    protected function shopMessagingStartUrlFor(?\App\Models\User $user, \App\Models\Distributor $distributor, ?int $productId = null): ?string
    {
        if (! $user || ! $user->hasVerifiedEmail()) {
            return null;
        }
        if ($distributor->status !== 'approved' || $distributor->is_suspended) {
            return null;
        }
        $myDistributor = $user->role === 'staff' ? $user->employer : $user->distributor;
        if ($myDistributor && (int) $myDistributor->id === (int) $distributor->id) {
            return null;
        }
        $q = ['distributor_id' => $distributor->id];
        if ($productId !== null) {
            $q['product_id'] = $productId;
        }

        return '/messages/start?'.http_build_query($q);
    }

    protected function isOwnShopProduct(?\App\Models\User $user, \App\Models\Product $product): bool
    {
        if (! $user) {
            return false;
        }
        $myDistributor = $user->role === 'staff' ? $user->employer : $user->distributor;

        return $myDistributor && (int) $myDistributor->id === (int) $product->distributor_id;
    }
}
