<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token_hash',
        'distributor_id',
        'invited_by_id',
        'permissions',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * Get the distributor that this invitation is for.
     */
    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    /**
     * Check if the invitation is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the invitation is accepted.
     */
    public function isAccepted()
    {
        return !is_null($this->accepted_at);
    }
}
