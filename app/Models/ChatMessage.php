<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_room_id', 'user_id', 'type', 'body', 'file_path', 'file_name',
        'file_size', 'duration_seconds', 'poll_options', 'reply_to',
    ];

    protected function casts(): array
    {
        return ['poll_options' => 'array'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** رأی‌های نظرسنجی این پیام (استفاده‌شده در ChatController) */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'chat_message_id');
    }
}
