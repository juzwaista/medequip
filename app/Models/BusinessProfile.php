<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'business_type',
        'tin_number',
        'sec_dti_document_path',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
