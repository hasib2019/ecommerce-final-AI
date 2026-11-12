<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Post;
use App\Models\Product;
use App\Support\CacheVersion;
use Illuminate\Support\Collection;

/**
 * Loads the data each home-page section needs (products, categories, …) and caches
 * the fully-resolved list, so rendering the home page costs ~zero queries.
 */
class HomeSectionResolver
{
    /** @return Collection<int, HomeSection> */
    public static function sections(): Collection
    {
        return CacheVersion::remember('home.sections', 900, function () {
            return HomeSection::query()
                ->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')
                ->get()
                ->map(fn (HomeSection $s) => self::resolve($s))
                ->filter();
        });
    }

    public static function resolve(HomeSection $section): ?HomeSection
    {
        $items = match ($section->type) {
            'products', 'flash_sale' => self::products($section),
            'category_grid' => self::categories($section),
            'brands' => self::brands($section),
            'blog_posts' => Post::published()->latest('published_at')->limit((int) $section->get('limit', 3))
                ->get(['id', 'title', 'slug', 'excerpt', 'image', 'published_at']),
            default => collect(),
        };

        // Hide product-driven sections that would render empty.
        if (in_array($section->type, ['products', 'flash_sale', 'category_grid', 'brands', 'blog_posts'], true) && $items->isEmpty()) {
            return null;
        }

        if ($section->type === 'flash_sale' && ($end = $section->get('ends_at')) && now()->gt($end)) {
            return null; // sale finished – section disappears automatically
        }

        $section->setRelation('items', $items);

        return $section;
    }

    private static function products(HomeSection $section): Collection
    {
        $limit = max(1, min(48, (int) $section->get('limit', 10)));
        $source = $section->type === 'flash_sale' ? $section->get('source', 'on_sale') : $section->get('source', 'latest');
        $q = Product::query()->forCard()->inStock();

        switch ($source) {
            case 'manual':
                $ids = array_map('intval', (array) $section->get('product_ids', []));
                if (! $ids) {
                    return collect();
                }

                return $q->whereIn('id', $ids)->get()
                    ->sortBy(fn ($p) => array_search($p->id, $ids))->values()->take($limit);
            case 'featured':
                $q->where('is_featured', true)->latest('products.id');
                break;
            case 'best_selling':
                $q->orderByDesc('sold_count');
                break;
            case 'on_sale':
                $q->onSale()->orderByDesc('sold_count');
                break;
            case 'top_rated':
                $q->where('rating_count', '>', 0)->orderByDesc('rating_avg')->orderByDesc('rating_count');
                break;
            case 'category':
                $category = Category::find($section->get('category_id'));
                if (! $category) {
                    return collect();
                }
                $q->whereIn('category_id', $category->descendantIds())->orderByDesc('sold_count');
                break;
            default:
                $q->latest('products.id');
        }

        return $q->limit($limit)->get();
    }

    private static function categories(HomeSection $section): Collection
    {
        $ids = array_map('intval', (array) $section->get('category_ids', []));
        $q = Category::query()->active()->withCount(['products' => fn ($q) => $q->active()]);

        if ($ids) {
            return $q->whereIn('id', $ids)->get()->sortBy(fn ($c) => array_search($c->id, $ids))->values();
        }

        return $q->where(fn ($q) => $q->where('is_featured', true)->orWhereNull('parent_id'))
            ->orderByDesc('is_featured')->orderBy('sort_order')
            ->limit((int) $section->get('limit', 12))->get();
    }

    private static function brands(HomeSection $section): Collection
    {
        $ids = array_map('intval', (array) $section->get('brand_ids', []));
        $q = Brand::query()->active()->whereNotNull('logo');

        return $ids
            ? $q->whereIn('id', $ids)->get()
            : $q->orderBy('sort_order')->limit((int) $section->get('limit', 12))->get();
    }
}
