<?php

namespace App\Models;

use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'options', 'sku', 'price', 'sale_price', 'stock', 'image', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant) {
            if (blank($variant->name) && is_array($variant->options)) {
                $variant->name = implode(' / ', array_values($variant->options));
            }
        });

        $sync = function (ProductVariant $variant) {
            Product::find($variant->product_id)?->syncStockFromVariants();
            CacheVersion::bump();
        };

        static::saved($sync);
        static::deleted($sync);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
