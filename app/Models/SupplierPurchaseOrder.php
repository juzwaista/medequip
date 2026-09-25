<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPurchaseOrder extends Model
{
    protected $fillable = [
        'distributor_id',
        'supplier_id',
        'po_number',
        'status',
        'expected_delivery_date',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrderItem::class);
    }
}
