<?php

// ==============================================
// File: app/Models/TelegramReminder.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramReminder extends Model
{
    protected $fillable = [
        'chat_id',
        'reminder_type',
        'preferred_time',
        'is_active',
    ];

    protected $casts = [
        'preferred_time' => 'datetime:H:i:s',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this reminder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'chat_id', 'chat_id');
    }

    /**
     * Scope: Active reminders
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('reminder_type', $type);
    }

    /**
     * Scope: Due reminders
     */
    public function scopeDue($query)
    {
        $currentTime = now()->format('H:i:s');
        return $query->where('is_active', true)
            ->where('preferred_time', '<=', $currentTime);
    }
}
