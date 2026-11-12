<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ShippingMethod extends Model
{
    use FlushesStorefrontCache;

    protected $fillable = ['name', 'description', 'cost', 'free_over', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'free_over' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function available(): Collection
    {
        return CacheVersion::remember('shipping.methods', 3600, fn () => static::where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get());
    }

    public function costFor(float $subtotal): float
    {
        if ($this->free_over !== null && $subtotal >= (float) $this->free_over) {
            return 0.0;
        }

        return (float) $this->cost;
    }
}
