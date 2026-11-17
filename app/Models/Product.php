<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'cover_path', 'price', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'price' => 'float'];
    }
}
