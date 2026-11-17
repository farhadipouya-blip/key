<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['parent_id', 'title', 'level', 'order', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Subject::class, 'parent_id')->orderBy('order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /** عنوان کامل با مسیر والد */
    public function fullTitle(): string
    {
        $chain = [];
        $node = $this;
        while ($node) {
            array_unshift($chain, $node->title);
            $node = $node->parent;
        }
        return implode(' › ', $chain);
    }
}
