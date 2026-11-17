<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Highlight extends Model
{
    protected $fillable = ['media_id', 'user_id', 'page', 'text', 'note', 'color'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
