<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPurchaseOrder extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'company_name',
        'authorized_signatory',
        'contact_number',
        'billing_address',
        'tin',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
