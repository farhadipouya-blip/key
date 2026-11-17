<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    protected $fillable = ['type', 'name', 'course_id', 'created_by', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_user', 'chat_room_id', 'user_id')
            ->withPivot(['last_read_message_id', 'joined_at']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->oldest();
    }

    /** ساخت یا بازیابی اتاق گفتگوی خصوصی بین دو کاربر */
    public static function privateBetween(User $a, User $b): self
    {
        $roomId = \DB::table('chat_rooms as r')
            ->join('chat_room_user as u1', 'u1.chat_room_id', '=', 'r.id')
            ->join('chat_room_user as u2', 'u2.chat_room_id', '=', 'r.id')
            ->where('r.type', 'private')
            ->where('u1.user_id', $a->id)
            ->where('u2.user_id', $b->id)
            ->value('r.id');

        if ($roomId) {
            return static::findOrFail($roomId);
        }

        $room = static::create(['type' => 'private', 'created_by' => $a->id]);
        $room->users()->syncWithoutDetaching([$a->id, $b->id]);

        return $room;
    }
}
