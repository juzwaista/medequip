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
}
