<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'amount', 'type', 'status',
        'gateway', 'gateway_ref', 'meta', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
