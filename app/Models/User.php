<?php

namespace App\Models;

use App\Notifications\WelcomeToMedequip;
use App\Support\NotificationFilters;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
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
        'username',
        'email',
        'password',
        'phone_number',
        'distributor_id',
        'social_provider',
        'social_id',
        'terms_accepted_at',
        'terms_version',
        'deactivated_at',
        'role',
        'pending_email',
        'login_otp',
        'login_otp_expires_at',
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
            'deactivated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'login_otp_expires_at' => 'datetime',
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
     * Get the distributor profile (for distributors).
     * Prefer the newest row if duplicates exist for the same user_id.
     */
    public function distributor(): HasOne
    {
        return $this->hasOne(Distributor::class, 'user_id')->latestOfMany('id');
    }

    /**
     * Unread notifications excluding in-app chat (handled via Messages badge).
     */
    public function unreadNonChatNotificationsCount(): int
    {
        return NotificationFilters::excludeNewChatMessages($this->unreadNotifications())->count();
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

    public function customerConversations()
    {
        return $this->hasMany(Conversation::class, 'customer_id');
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
        return ! is_null($this->banned_at);
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
     * Generate a unique username from an email local-part (for provisioning / migration).
     */
    public static function suggestUniqueUsername(string $email): string
    {
        $localPart = strstr($email, '@', true) ?: 'user';
        $base = strtolower(preg_replace('/[^a-z0-9_]/', '_', $localPart));
        $base = trim($base, '_') ?: '';
        if ($base === '' || strlen($base) < 3) {
            $base = 'user_'.substr(sha1($email), 0, 6);
        }
        $base = substr($base, 0, 20);
        $candidate = $base;
        $n = 0;
        while (static::withTrashed()->where('username', $candidate)->exists()) {
            $n++;
            $candidate = $base.'_'.$n;
            if (strlen($candidate) > 30) {
                $candidate = 'u'.substr(str_replace('-', '', (string) Str::uuid()), 0, 10);
            }
            $candidate = substr($candidate, 0, 30);
            if ($n > 500) {
                $candidate = 'u'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
                break;
            }
        }

        return substr($candidate, 0, 30);
    }

    /**
     * Get the distributor this user works for (if role is staff)
     */
    public function employer()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (filled($user->email) && blank($user->username)) {
                $user->username = static::suggestUniqueUsername($user->email);
            }
        });

        static::created(function (User $user) {
            // Check if user already has a wallet (e.g. from social login or seeder)
            if (!$user->wallet()->exists()) {
                $user->wallet()->create();
            }
            $user->notify(new WelcomeToMedequip);
        });
    }
}
