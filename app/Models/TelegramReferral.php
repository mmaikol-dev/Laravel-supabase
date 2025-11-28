<?php

// ==============================================
// File: app/Models/TelegramReferral.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramReferral extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'credited',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'credited' => 'boolean',
    ];

    /**
     * Get the user who made the referral
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'referrer_id', 'chat_id');
    }

    /**
     * Get the user who was referred
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'referred_id', 'chat_id');
    }

    /**
     * Scope: Credited referrals
     */
    public function scopeCredited($query)
    {
        return $query->where('credited', true);
    }

    /**
     * Scope: Uncredited referrals
     */
    public function scopeUncredited($query)
    {
        return $query->where('credited', false);
    }

    /**
     * Scope: By referrer
     */
    public function scopeByReferrer($query, $chatId)
    {
        return $query->where('referrer_id', $chatId);
    }
}