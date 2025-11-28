<?php

// ==============================================
// File: app/Models/TelegramInteraction.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramInteraction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'chat_id',
        'message',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that owns this interaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'chat_id', 'chat_id');
    }

    /**
     * Scope: Recent interactions
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: By date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }
}
