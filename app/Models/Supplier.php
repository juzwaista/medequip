<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'distributor_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrder::class);
    }
}
