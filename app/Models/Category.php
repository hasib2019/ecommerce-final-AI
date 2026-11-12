<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use App\Models\Concerns\HasSlug;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    use FlushesStorefrontCache, HasSlug;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image', 'banner', 'is_active', 'is_featured',
        'show_in_menu', 'sort_order', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'show_in_menu' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
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
        return route('category.show', $this->slug);
    }

    /** IDs of this category and all of its descendants (cached tree walk). */
    public function descendantIds(): array
    {
        $tree = self::parentMap();
        $ids = [$this->id];
        $stack = [$this->id];

        while ($stack) {
            $id = array_pop($stack);
            foreach ($tree[$id] ?? [] as $childId) {
                $ids[] = $childId;
                $stack[] = $childId;
            }
        }

        return $ids;
    }

    /** Ancestors from the root down to (excluding) this category. */
    public function ancestors(): Collection
    {
        $chain = collect();
        $node = $this->parent;
        $guard = 0;

        while ($node && $guard++ < 10) {
            $chain->prepend($node);
            $node = $node->parent;
        }

        return $chain;
    }

    /** @return array<int, array<int>> parent_id => child ids */
    protected static function parentMap(): array
    {
        return CacheVersion::remember('category.parent_map', 3600, fn () => static::query()
            ->active()->whereNotNull('parent_id')->get(['id', 'parent_id'])
            ->groupBy('parent_id')->map(fn ($g) => $g->pluck('id')->all())->all());
    }

    /** Active root categories with their children, for menus. */
    public static function menuTree(): Collection
    {
        return CacheVersion::remember('category.menu_tree', 3600, fn () => static::query()
            ->active()->whereNull('parent_id')->where('show_in_menu', true)
            ->with(['children' => fn ($q) => $q->active()->select('id', 'parent_id', 'name', 'slug', 'image')])
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'slug', 'image']));
    }
}
