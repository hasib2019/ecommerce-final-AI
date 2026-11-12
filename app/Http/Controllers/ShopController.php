<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CacheVersion;
use App\Support\Img;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    private const FILTER_KEYS = ['brand', 'min', 'max', 'in_stock', 'on_sale', 'rating', 'sort', 'q', 'category'];

    public function index(Request $request)
    {
        $title = $request->boolean('on_sale') ? 'Hot Deals' : 'Shop All Products';
        seo()->title($title)
            ->description('Browse all products at '.setting('general.site_name').'. '.setting('seo.meta_description'))
            ->breadcrumbs([[$title, route('shop')]]);

        return $this->listing($request, Product::query(), [
            'title' => $title,
            'heading' => $title,
            'baseUrl' => route('shop'),
        ]);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);
        $category->load(['parent.parent', 'children' => fn ($q) => $q->active()]);

        $trail = $category->ancestors()->map(fn ($c) => [$c->name, $c->url])->all();
        seo()->title($category->meta_title ?: $category->name)
            ->description($category->meta_description ?: ($category->description
                ?: "Buy {$category->name} online in Bangladesh at the best price. Genuine products, fast delivery & cash on delivery at ".setting('general.site_name').'.'))
            ->image($category->banner ?: $category->image)
            ->breadcrumbs([...$trail, [$category->name, $category->url]]);

        return $this->listing($request, Product::query()->whereIn('category_id', $category->descendantIds()), [
            'title' => $category->name,
            'heading' => $category->name,
            'description' => $category->description,
            'banner' => $category->banner,
            'subcategories' => $category->children,
            'category' => $category,
            'baseUrl' => $category->url,
        ]);
    }

    public function brand(Request $request, Brand $brand)
    {
        abort_unless($brand->is_active, 404);

        seo()->title($brand->meta_title ?: $brand->name.' Products')
            ->description($brand->meta_description ?: "Shop original {$brand->name} products online in Bangladesh at ".setting('general.site_name').'.')
            ->image($brand->logo)
            ->breadcrumbs([['Brands', route('brands')], [$brand->name, $brand->url]]);

        return $this->listing($request, Product::query()->where('brand_id', $brand->id), [
            'title' => $brand->name,
            'heading' => $brand->name,
            'description' => $brand->description,
            'brandModel' => $brand,
            'baseUrl' => $brand->url,
        ]);
    }

    public function brands()
    {
        $brands = CacheVersion::remember('brands.all', 3600, fn () => Brand::active()
            ->withCount(['products' => fn ($q) => $q->active()])->orderBy('sort_order')->orderBy('name')->get());

        seo()->title('All Brands')->breadcrumbs([['Brands', route('brands')]]);

        return view('shop.brands', compact('brands'));
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        seo()->title($term ? "Search results for “{$term}”" : 'Search')->noindex();

        $query = Product::query();
        if ($term !== '') {
            $this->applySearch($query, $term);
        }

        return $this->listing($request, $query, [
            'title' => 'Search',
            'heading' => $term ? "Results for “{$term}”" : 'Search products',
            'baseUrl' => route('search'),
            'searchTerm' => $term,
        ]);
    }

    /** Live-search dropdown (JSON). */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $products = Product::query()->select(['id', 'name', 'slug', 'price', 'sale_price', 'sale_ends_at', 'thumbnail'])->active();
        $this->applySearch($products, $term);

        $categories = Category::active()->where('name', 'like', '%'.$term.'%')->limit(3)->get(['name', 'slug']);

        return response()->json([
            'products' => $products->limit(6)->get()->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => $p->url,
                'image' => Img::url($p->thumbnail, 160),
                'price' => money($p->final_price),
                'compare' => $p->compare_price ? money($p->compare_price) : null,
            ]),
            'categories' => $categories->map(fn ($c) => ['name' => $c->name, 'url' => $c->url]),
            'all_url' => route('search', ['q' => $term]),
        ])->header('Cache-Control', 'public, max-age=120');
    }

    // ------------------------------------------------------------------ internals

    private function applySearch(Builder $query, string $term): void
    {
        $words = collect(preg_split('/\s+/', Str::limit($term, 80, '')))->filter(fn ($w) => mb_strlen($w) >= 1)->take(6);

        $query->where(function (Builder $q) use ($term, $words) {
            $q->where('products.sku', $term);
            $q->orWhere(function (Builder $q) use ($words) {
                foreach ($words as $w) {
                    $like = '%'.addcslashes($w, '%_\\').'%';
                    $q->where(fn ($q) => $q->where('products.name', 'like', $like)
                        ->orWhere('products.short_description', 'like', $like)
                        ->orWhere('products.meta_keywords', 'like', $like));
                }
            });
        });

        // Rank names that start with the term first.
        $query->orderByRaw('CASE WHEN products.name LIKE ? THEN 0 WHEN products.name LIKE ? THEN 1 ELSE 2 END', [
            addcslashes($term, '%_\\').'%', '%'.addcslashes($term, '%_\\').'%',
        ]);
    }

    private function listing(Request $request, Builder $base, array $context)
    {
        $base->active();

        // Facets are computed on the un-filtered base so the sidebar stays stable.
        $facetBase = (clone $base);
        $brands = Brand::active()
            ->whereIn('id', (clone $facetBase)->reorder()->select('brand_id')->whereNotNull('brand_id')->distinct())
            ->orderBy('name')->get(['id', 'name', 'slug']);
        $range = (clone $facetBase)->selectRaw('MIN('.Product::finalPriceSql().') as lo, MAX('.Product::finalPriceSql().') as hi')
            ->reorder()->first();

        $query = $base->select(Product::CARD_COLUMNS);

        if ($request->filled('category') && ($cat = Category::where('slug', $request->query('category'))->first())) {
            $query->whereIn('category_id', $cat->descendantIds());
        }
        if ($brandSlugs = array_filter((array) $request->query('brand', []))) {
            $query->whereIn('brand_id', Brand::whereIn('slug', $brandSlugs)->pluck('id'));
        }
        if ($request->filled('min')) {
            $query->whereRaw(Product::finalPriceSql().' >= ?', [(float) $request->query('min')]);
        }
        if ($request->filled('max')) {
            $query->whereRaw(Product::finalPriceSql().' <= ?', [(float) $request->query('max')]);
        }
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }
        if ($request->boolean('on_sale')) {
            $query->onSale();
        }
        if ($rating = (int) $request->query('rating')) {
            $query->where('rating_avg', '>=', $rating);
        }

        $sort = (string) $request->query('sort', '');
        if ($sort) {
            $query->reorder()->sorted($sort);
        } elseif (! ($context['searchTerm'] ?? null)) {
            $query->sorted(null);
        }

        $perPage = (int) setting('catalog.per_page', 24);
        $products = $query->paginate($perPage)->withQueryString();

        // SEO: filtered / sorted variants are not indexable; paginated pages canonicalise to themselves.
        $page = $products->currentPage();
        $canonical = $context['baseUrl'].($page > 1 ? '?page='.$page : '');
        seo()->canonical($canonical);
        if ($request->hasAny(array_diff(self::FILTER_KEYS, ['q']))) {
            seo()->noindex();
        }
        if ($page > 1) {
            seo()->title(($context['title'] ?? 'Shop').' – Page '.$page);
        }
        if ($products->previousPageUrl()) {
            seo()->link('prev', $products->previousPageUrl());
        }
        if ($products->nextPageUrl()) {
            seo()->link('next', $products->nextPageUrl());
        }

        $activeFilters = collect($request->only(self::FILTER_KEYS))
            ->except(['sort', 'q'])->filter(fn ($v) => filled($v))->count();

        return view('shop.index', array_merge($context, [
            'products' => $products,
            'brands' => $brands,
            'priceRange' => [(int) floor((float) ($range->lo ?? 0)), (int) ceil((float) ($range->hi ?? 0))],
            'categories' => Category::menuTree(),
            'activeFilters' => $activeFilters,
            'sort' => $sort,
        ]));
    }
}
