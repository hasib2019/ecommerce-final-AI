<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Product extends Model
{
    use FlushesStorefrontCache, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'sale_price', 'sale_ends_at', 'cost_price', 'stock', 'track_stock', 'low_stock_threshold',
        'thumbnail', 'gallery', 'video_url', 'badge', 'weight', 'has_variants', 'options', 'specifications',
        'is_active', 'is_featured', 'views', 'sold_count', 'rating_avg', 'rating_count',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    /** Columns needed to render a product card – keeps listing queries lean. */
    public const CARD_COLUMNS = [
        'products.id', 'products.name', 'products.slug', 'products.price', 'products.sale_price', 'products.sale_ends_at',
        'products.stock', 'products.track_stock', 'products.thumbnail', 'products.gallery', 'products.badge',
        'products.has_variants', 'products.rating_avg', 'products.rating_count', 'products.sold_count',
        'products.category_id', 'products.created_at', 'products.low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'sale_ends_at' => 'datetime',
            'gallery' => 'array',
            'options' => 'array',
            'specifications' => 'array',
            'track_stock' => 'boolean',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'rating_avg' => 'float',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ---------------------------------------------------------------- scopes

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('products.is_active', true);
    }

    public function scopeForCard(Builder $q): Builder
    {
        return $q->select(self::CARD_COLUMNS)->active();
    }

    public function scopeOnSale(Builder $q): Builder
    {
        return $q->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', now()));
    }

    public function scopeInStock(Builder $q): Builder
    {
        return $q->where(fn ($q) => $q->where('track_stock', false)->orWhere('stock', '>', 0));
    }

    /** SQL expression for the price a customer actually pays right now. */
    public static function finalPriceSql(): string
    {
        $now = DB::getPdo()->quote(now()->toDateTimeString());

        return "(CASE WHEN products.sale_price IS NOT NULL AND products.sale_price < products.price
                AND (products.sale_ends_at IS NULL OR products.sale_ends_at > {$now})
                THEN products.sale_price ELSE products.price END)";
    }

    public function scopeSorted(Builder $q, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $q->orderByRaw(self::finalPriceSql().' asc'),
            'price_desc' => $q->orderByRaw(self::finalPriceSql().' desc'),
            'popular' => $q->orderByDesc('sold_count')->orderByDesc('views'),
            'rating' => $q->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'name' => $q->orderBy('name'),
            default => $q->latest('products.id'),
        };
    }

    // ---------------------------------------------------------------- pricing

    public function isOnSale(): bool
    {
        return $this->sale_price !== null
            && (float) $this->sale_price < (float) $this->price
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->isOnSale() ? $this->sale_price : $this->price);
    }

    public function getComparePriceAttribute(): ?float
    {
        return $this->isOnSale() ? (float) $this->price : null;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (! $this->isOnSale() || (float) $this->price <= 0) {
            return 0;
        }

        return (int) round((1 - (float) $this->sale_price / (float) $this->price) * 100);
    }

    /** [price, compare_price] for a given variant (or the product itself). */
    public function pricingFor(?ProductVariant $variant): array
    {
        if (! $variant || $variant->price === null) {
            return [$this->final_price, $this->compare_price];
        }

        $regular = (float) $variant->price;
        $sale = $variant->sale_price !== null ? (float) $variant->sale_price : null;
        $saleWindowOpen = $this->sale_ends_at === null || $this->sale_ends_at->isFuture();

        if ($sale !== null && $sale < $regular && $saleWindowOpen) {
            return [$sale, $regular];
        }

        return [$regular, null];
    }

    // ---------------------------------------------------------------- stock

    public function inStock(): bool
    {
        return ! $this->track_stock || $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock > 0 && $this->stock <= $this->low_stock_threshold;
    }

    // ---------------------------------------------------------------- media / urls

    /** @return array<int, string> */
    public function getImagesAttribute(): array
    {
        return array_values(array_filter(array_unique(array_merge(
            [$this->thumbnail],
            (array) ($this->gallery ?? [])
        ))));
    }

    public function getSecondImageAttribute(): ?string
    {
        return ((array) ($this->gallery ?? []))[0] ?? null;
    }

    public function getUrlAttribute(): string
    {
        return route('product.show', $this->slug);
    }

    public function isNew(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays((int) setting('catalog.new_days', 14)));
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: Str::limit(trim(strip_tags($this->short_description ?: $this->description ?: $this->name)), 158);
    }

    public function recalculateRating(): void
    {
        $stats = $this->reviews()->where('is_approved', true)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating),0) as a')->first();

        $this->updateQuietly([
            'rating_count' => (int) $stats->c,
            'rating_avg' => round((float) $stats->a, 2),
        ]);
    }

    /** For products with variants, product stock mirrors the sum of variant stock. */
    public function syncStockFromVariants(): void
    {
        if (! $this->has_variants) {
            return;
        }

        $this->updateQuietly(['stock' => (int) $this->variants()->where('is_active', true)->sum('stock')]);
    }
}
