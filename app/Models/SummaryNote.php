<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SummaryNote extends Model
{
    protected $fillable = ['summary_id', 'user_id', 'body'];

    public function summary(): BelongsTo
    {
        return $this->belongsTo(Summary::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
