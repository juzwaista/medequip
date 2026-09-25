<?php

namespace App\Policies;

use App\Models\SavedPurchaseOrder;
use App\Models\User;

class SavedPurchaseOrderPolicy
{
    public function update(User $user, SavedPurchaseOrder $savedPurchaseOrder): bool
    {
        return $user->id === $savedPurchaseOrder->user_id;
    }

    public function delete(User $user, SavedPurchaseOrder $savedPurchaseOrder): bool
    {
        return $user->id === $savedPurchaseOrder->user_id;
    }
}
