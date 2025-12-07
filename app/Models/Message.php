<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'external_id',
        'channel',
        'from',
        'to',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get messages for a specific conversation
     */
    public function scopeForConversation($query, $userIdOrPhone, $channel = 'whatsapp')
    {
        return $query->where(function ($q) use ($userIdOrPhone, $channel) {
            $q->where('user_id', $userIdOrPhone)
              ->orWhere('from', $userIdOrPhone);
        })->where('channel', $channel);
    }

    /**
     * Scope to get recent messages
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
