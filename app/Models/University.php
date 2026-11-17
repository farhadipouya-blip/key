<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class University extends Model
{
    protected $fillable = ['name', 'city', 'type', 'website'];

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'major_university')
            ->withPivot(['year', 'capacity', 'last_rank_min', 'last_rank_max', 'tuition_type']);
    }
}
