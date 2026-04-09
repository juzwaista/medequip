<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'description',
        'logo_path',
        'cover_photo_path',
        'phone',
        'website',
        'business_hours',
        'social_links',
        'address',
        'contact_number',
        'email',
        'is_verified',
        'status',           // pending | approved | rejected
        'rejection_reason',
        'valid_id_path',
        'business_license_path', // Reused for Business Permit
        'dti_sec_path',
        'bir_form_path',
        'fda_license_path',
        'prc_id_path',
        'authorization_letter_path',
        'suspended_until',
        'suspension_reason',
        'warning_reason',
        'warning_message',
        'auto_approve_orders',
        'chat_template_order_accepted',
        'chat_template_order_shipped',
        'chat_auto_reply',
        'shop_profile_onboarding_completed_at',
        'latitude',
        'longitude',
        'max_cod_amount',
        'rejection_count',
        'pickup_instructions',
        'valid_id_expires_at',
        'business_license_expires_at',
        'dti_sec_expires_at',
        'bir_form_expires_at',
        'fda_license_expires_at',
        'prc_id_expires_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'auto_approve_orders' => 'boolean',
        'business_hours' => 'array',
        'social_links' => 'array',
        'suspended_until' => 'datetime',
        'shop_profile_onboarding_completed_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'max_cod_amount' => 'decimal:2',
        'valid_id_expires_at' => 'date',
        'business_license_expires_at' => 'date',
        'dti_sec_expires_at' => 'date',
        'bir_form_expires_at' => 'date',
        'fda_license_expires_at' => 'date',
        'prc_id_expires_at' => 'date',
    ];

    protected $appends = ['is_suspended', 'unread_alerts_count', 'logo_url', 'cover_photo_url', 'rating'];

    /**
     * Get logo URL using PublicStorageUrl helper for Hostinger resilience
     */
    public function getLogoUrlAttribute()
    {
        return \App\Support\PublicStorageUrl::get($this->logo_path);
    }

    /**
     * Get cover photo URL using PublicStorageUrl helper for Hostinger resilience
     */
    public function getCoverPhotoUrlAttribute()
    {
        return \App\Support\PublicStorageUrl::get($this->cover_photo_path);
    }

    /**
     * Get shop rating based on product reviews
     */
    public function getRatingAttribute()
    {
        return (float) $this->products()->withAvg('reviews', 'stars')->get()->avg('reviews_avg_stars') ?: 0.0;
    }

    /**
     * Get the user/owner
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get staff users employed by this distributor
     */
    public function staff()
    {
        return $this->hasMany(User::class, 'distributor_id');
    }

    /**
     * Get licenses
     */
    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    /**
     * Get branches
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get products
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get orders received
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get DSS settings
     */
    public function dssSettings()
    {
        return $this->hasOne(DssDistributorSettings::class);
    }

    /**
     * Get DSS alerts
     */
    public function dssAlerts()
    {
        return $this->hasMany(DssAlert::class);
    }

    /**
     * Get sales analytics
     */
    public function salesAnalytics()
    {
        return $this->hasMany(DssSalesAnalytics::class);
    }

    /**
     * Get reorder recommendations
     */
    public function reorderRecommendations()
    {
        return $this->hasMany(DssReorderRecommendation::class);
    }

    /**
     * Get unread alerts count
     */
    public function getUnreadAlertsCountAttribute(): int
    {
        return $this->dssAlerts()->where('is_read', false)->count();
    }

    /**
     * Check if distributor is currently suspended
     */
    public function getIsSuspendedAttribute(): bool
    {
        if (! $this->suspended_until) {
            return false;
        }

        return $this->suspended_until->isFuture();
    }
}
