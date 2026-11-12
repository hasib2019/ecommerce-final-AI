<?php

namespace App\Models;

use App\Models\Concerns\FlushesStorefrontCache;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use FlushesStorefrontCache;

    public const TYPES = [
        'hero_slider' => 'Hero Slider',
        'features' => 'Features / Trust Strip',
        'category_grid' => 'Category Showcase',
        'products' => 'Product Collection',
        'flash_sale' => 'Flash Sale (Countdown)',
        'banners' => 'Banner Grid',
        'promo_banner' => 'Promo Banner (Full Width)',
        'brands' => 'Brand Logos',
        'testimonials' => 'Testimonials',
        'blog_posts' => 'Latest Blog Posts',
        'newsletter' => 'Newsletter Signup',
        'rich_text' => 'Custom Text / HTML',
    ];

    protected $fillable = ['type', 'title', 'subtitle', 'data', 'visibility', 'background', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function visibilityClass(): string
    {
        return match ($this->visibility) {
            'desktop' => 'hidden md:block',
            'mobile' => 'md:hidden',
            default => '',
        };
    }
}
