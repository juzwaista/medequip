<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDiscountId extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'discount_type',
        'id_name',
        'id_number',
        'id_image_path',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the discount ID
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
