<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Distributor;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
        'distributor_id',
        'social_provider',
        'social_id',
        'banned_at',
        'ban_reason',
        'terms_accepted_at',
        'terms_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The current terms version — bump this string to force re-acceptance.
     */
    public const CURRENT_TERMS_VERSION = '2026-03-29';

    /**
     * Check if user has accepted the current terms version.
     */
    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted_at !== null
            && $this->terms_version === self::CURRENT_TERMS_VERSION;
    }

    /**
     * Get the distributor profile (for distributors)
     */
    public function distributor()
    {
        return $this->hasOne(Distributor::class, 'user_id');
    }

    /**
     * Get orders placed by this user (for customers)
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get saved delivery addresses (for customers)
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Check if user is distributor
     */
    public function isDistributor(): bool
    {
        return $this->role === 'distributor';
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }
    /**
     * Get the user's wallet
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the user's courier profile
     */
    public function courier()
    {
        return $this->hasOne(Courier::class);
    }

    public function isCourier(): bool
    {
        return $this->role === 'courier';
    }

    /**
     * Get the distributor this user works for (if role is staff)
     */
    public function employer()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->wallet()->create();
        });
    }
}

