<?php

// ==============================================
// File: app/Models/TelegramUser.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class TelegramUser extends Model
{
    protected $fillable = [
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'referral_code',
        'membership_tier',
        'trial_expires_at',
        'premium_expires_at',
        'last_active_at',
    ];

    protected $casts = [
        'trial_expires_at' => 'datetime',
        'premium_expires_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the interactions for this user
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(TelegramInteraction::class, 'chat_id', 'chat_id');
    }

    /**
     * Get referrals made by this user
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(TelegramReferral::class, 'referrer_id', 'chat_id');
    }

    /**
     * Get users referred by this user
     */
    public function referredBy(): HasMany
    {
        return $this->hasMany(TelegramReferral::class, 'referred_id', 'chat_id');
    }

    /**
     * Get the trial record for this user
     */
    public function trial()
    {
        return $this->hasOne(TelegramTrial::class, 'chat_id', 'chat_id');
    }

    /**
     * Get reminders for this user
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TelegramReminder::class, 'chat_id', 'chat_id');
    }

    /**
     * Check if user is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->membership_tier === 'trial' 
            && $this->trial_expires_at 
            && $this->trial_expires_at->isFuture();
    }

    /**
     * Check if user is premium (any paid tier)
     */
    public function isPremium(): bool
    {
        return in_array($this->membership_tier, ['premium', 'vip', 'vvip']);
    }

    /**
     * Check if user has used trial
     */
    public function hasUsedTrial(): bool
    {
        return $this->trial()->exists();
    }

    /**
     * Get referral count
     */
    public function getReferralCountAttribute(): int
    {
        return $this->referrals()->count();
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Scope: Active users
     */
    public function scopeActive($query, $days = 7)
    {
        return $query->where('last_active_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: By membership tier
     */
    public function scopeTier($query, $tier)
    {
        return $query->where('membership_tier', $tier);
    }
}
