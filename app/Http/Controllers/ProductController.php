<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\CacheVersion;
use App\Support\Img;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category.parent.parent',
            'brand:id,name,slug,logo',
            'variants' => fn ($q) => $q->where('is_active', true),
        ]);

        // Count a view once per session per product (cheap, avoids a write on every refresh).
        $viewed = (array) session('viewed_products', []);
        if (! in_array($product->id, $viewed, true)) {
            Product::whereKey($product->id)->increment('views');
            session(['viewed_products' => array_slice([...$viewed, $product->id], -50)]);
        }

        $recentIds = array_values(array_diff((array) session('recently_viewed', []), [$product->id]));
        session(['recently_viewed' => array_slice([$product->id, ...$recentIds], 0, 13)]);

        $reviews = $product->approvedReviews()->limit(20)->get();
        $ratingBreakdown = $product->rating_count > 0
            ? $product->reviews()->where('is_approved', true)->selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating')->all()
            : [];

        $related = CacheVersion::remember("product.{$product->id}.related", 1800, function () use ($product) {
            $limit = (int) setting('catalog.related_count', 10);
            $q = Product::forCard()->inStock()->whereKeyNot($product->id);
            $related = $product->category_id
                ? (clone $q)->where('category_id', $product->category_id)->orderByDesc('sold_count')->limit($limit)->get()
                : collect();
            if ($related->count() < $limit) {
                $related = $related->concat((clone $q)->whereNotIn('id', $related->pluck('id'))
                    ->orderByDesc('sold_count')->limit($limit - $related->count())->get());
            }

            return $related;
        });

        $recentlyViewed = collect();
        if (setting('conversion.recently_viewed') && $recentIds) {
            $ids = array_slice($recentIds, 0, 12);
            $recentlyViewed = Product::forCard()->whereIn('id', $ids)->get()
                ->sortBy(fn ($p) => array_search($p->id, $ids))->values();
        }

        $this->seo($product, $reviews);

        return view('product.show', [
            'product' => $product,
            'reviews' => $reviews,
            'ratingBreakdown' => $ratingBreakdown,
            'related' => $related,
            'recentlyViewed' => $recentlyViewed,
            'variantData' => $this->variantData($product),
        ]);
    }

    public function quickView(Product $product)
    {
        abort_unless($product->is_active, 404);
        $product->load(['variants' => fn ($q) => $q->where('is_active', true)]);
        \App\Support\Icon::$sprite = false; // HTML fragment – no page sprite available

        return view('product.quick-view', [
            'product' => $product,
            'variantData' => $this->variantData($product),
        ]);
    }

    public function review(Request $request, Product $product)
    {
        abort_unless(setting('catalog.reviews_enabled') && $product->is_active, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $user = $request->user();
        $verified = $user && Order::where('user_id', $user->id)
            ->where('status', OrderStatus::Delivered)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        ProductReview::create([
            ...$data,
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'is_verified' => $verified,
            'is_approved' => (bool) setting('catalog.reviews_auto_approve'),
        ]);

        $message = setting('catalog.reviews_auto_approve')
            ? 'Thanks! Your review has been published.'
            : 'Thanks! Your review will appear after moderation.';

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    /** Real recent orders for the "someone just bought" popup – no fake data. */
    public function recentSales()
    {
        $sales = CacheVersion::remember('recent-sales', 300, function () {
            return Order::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
                ->latest()->limit(12)
                ->with(['items' => fn ($q) => $q->with('product:id,name,slug,thumbnail,is_active')])
                ->get(['id', 'customer_name', 'city', 'created_at'])
                ->map(function (Order $o) {
                    $item = $o->items->first(fn ($i) => $i->product?->is_active);
                    if (! $item) {
                        return null;
                    }

                    return [
                        'name' => Str::of($o->customer_name)->explode(' ')->first(),
                        'city' => $o->city,
                        'product' => $item->product->name,
                        'url' => route('product.show', $item->product->slug),
                        'image' => Img::url($item->product->thumbnail, 160),
                        'ago' => $o->created_at->diffForHumans(),
                    ];
                })->filter()->values()->all();
        });

        return response()->json($sales)->header('Cache-Control', 'public, max-age=300');
    }

    // ------------------------------------------------------------------ helpers

    private function variantData(Product $product): array
    {
        if (! $product->has_variants || $product->variants->isEmpty()) {
            return ['options' => [], 'variants' => []];
        }

        $options = collect((array) $product->options)
            ->filter(fn ($o) => filled($o['name'] ?? null))
            ->map(fn ($o) => ['name' => $o['name'], 'values' => array_values((array) ($o['values'] ?? []))])
            ->values()->all();

        $variants = $product->variants->map(function ($v) use ($product) {
            [$price, $compare] = $product->pricingFor($v);

            return [
                'id' => $v->id,
                'name' => $v->name,
                'options' => (array) $v->options,
                'price' => money($price),
                'compare' => $compare ? money($compare) : null,
                'stock' => $product->track_stock ? (int) $v->stock : 999,
                'image' => $v->image ? Img::url($v->image, 960) : null,
            ];
        })->values()->all();

        return compact('options', 'variants');
    }

    private function seo(Product $product, $reviews): void
    {
        $trail = [];
        if ($product->category) {
            foreach ($product->category->ancestors() as $a) {
                $trail[] = [$a->name, $a->url];
            }
            $trail[] = [$product->category->name, $product->category->url];
        }
        $trail[] = [$product->name, $product->url];

        $availability = $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        $currency = setting('general.currency_code', 'BDT');

        $offers = [
            '@type' => 'Offer',
            'url' => $product->url,
            'priceCurrency' => $currency,
            'price' => number_format($product->final_price, 2, '.', ''),
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@type' => 'Organization', 'name' => setting('general.site_name')],
        ];
        if ($product->isOnSale() && $product->sale_ends_at) {
            $offers['priceValidUntil'] = $product->sale_ends_at->toDateString();
        }

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->seo_description,
            'image' => array_map(fn ($i) => Img::absolute($i, 1280), array_slice($product->images, 0, 5)),
            'sku' => $product->sku,
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'category' => $product->category?->name,
            'offers' => $offers,
        ]);

        if ($product->rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($product->rating_avg, 1),
                'reviewCount' => $product->rating_count,
                'bestRating' => 5,
            ];
            $schema['review'] = $reviews->take(5)->map(fn ($r) => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $r->name],
                'datePublished' => $r->created_at->toDateString(),
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r->rating, 'bestRating' => 5],
                'reviewBody' => Str::limit((string) $r->comment, 500),
            ])->values()->all();
        }

        seo()->title($product->meta_title ?: $product->name)
            ->description($product->seo_description)
            ->keywords($product->meta_keywords)
            ->image($product->thumbnail)
            ->type('product')
            ->canonical($product->url)
            ->meta('product:price:amount', number_format($product->final_price, 2, '.', ''))
            ->meta('product:price:currency', $currency)
            ->meta('product:availability', $product->inStock() ? 'in stock' : 'out of stock')
            ->schema($schema)
            ->breadcrumbs($trail);
    }
}
