<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasteryLevel extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'subject_id', 'level', 'attempted', 'correct', 'retention', 'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public const UPDATED_AT = null;
}
