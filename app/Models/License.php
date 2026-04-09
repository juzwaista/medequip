<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'distributor_id',
        'type',
        'file_path',
        'status',
        'expires_at',
    ];

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }
}
