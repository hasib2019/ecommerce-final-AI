<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use FlushesStorefrontCache, HasSlug;

    protected $fillable = [
        'name', 'slug', 'logo', 'description', 'is_active', 'sort_order', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function getUrlAttribute(): string
    {
        return route('brand.show', $this->slug);
    }
}
