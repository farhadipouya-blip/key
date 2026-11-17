<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    protected $fillable = ['title', 'code', 'group', 'degree'];

    public function universities(): BelongsToMany
    {
        return $this->belongsToMany(University::class, 'major_university')
            ->withPivot(['year', 'capacity', 'last_rank_min', 'last_rank_max', 'tuition_type']);
    }

    public function cutoffs(): HasMany
    {
        return $this->hasMany(MajorUniversity::class, 'major_id');
    }
}
