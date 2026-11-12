<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_order', 'max_discount', 'usage_limit', 'used_count',
        'free_shipping', 'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'free_shipping' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Coupon $c) => $c->code = strtoupper(trim($c->code)));
    }

    public static function findByCode(?string $code): ?self
    {
        return $code ? static::where('code', strtoupper(trim($code)))->first() : null;
    }

    /** Returns null when the coupon can be applied, otherwise a human-readable reason. */
    public function rejectionReason(float $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'This coupon is not active.',
            $this->starts_at && $this->starts_at->isFuture() => 'This coupon is not active yet.',
            $this->expires_at && $this->expires_at->isPast() => 'This coupon has expired.',
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => 'This coupon has reached its usage limit.',
            $this->min_order !== null && $subtotal < (float) $this->min_order => 'Minimum order of '.money($this->min_order).' is required for this coupon.',
            default => null,
        };
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
