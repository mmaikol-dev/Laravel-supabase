<?php

// ==============================================
// File: app/Models/TelegramTrial.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramTrial extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'chat_id',
        'started_at',
        'expires_at',
        'converted_to_paid',
        'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'converted_to_paid' => 'boolean',
    ];

    /**
     * Get the user that owns this trial
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'chat_id', 'chat_id');
    }

    /**
     * Check if trial is active
     */
    public function isActive(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Check if trial is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope: Active trials
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Expired trials
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: Converted to paid
     */
    public function scopeConverted($query)
    {
        return $query->where('converted_to_paid', true);
    }
}