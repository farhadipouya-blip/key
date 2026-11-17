<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    protected $fillable = [
        'user_id', 'plan_item_id', 'subject_id', 'date', 'minutes',
        'started_at', 'ended_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(PlanItem::class, 'plan_item_id');
    }
}
