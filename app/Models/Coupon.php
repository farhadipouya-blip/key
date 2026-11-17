<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_amount', 'max_uses', 'used_count',
        'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_active' => 'boolean', 'value' => 'float'];
    }

    public function isValid(): bool
    {
        return $this->is_active
            && ($this->max_uses === null || $this->used_count < $this->max_uses)
            && ($this->expires_at === null || $this->expires_at->gt(now()));
    }

    /** محاسبه مبلغ تخفیف برای یک مبلغ مشخص */
    public function discountFor(float $amount): float
    {
        if (! $this->isValid()) {
            return 0;
        }
        if ($this->min_amount && $amount < $this->min_amount) {
            return 0;
        }
        $discount = $this->type === 'percent'
            ? $amount * ((float) $this->value / 100)
            : (float) $this->value;

        return (float) min($discount, $amount);
    }
}
