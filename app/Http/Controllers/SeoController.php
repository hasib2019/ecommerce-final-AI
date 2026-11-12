<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Support\CacheVersion;
use App\Support\Img;
use Illuminate\Support\Str;

class SeoController extends Controller
{
    public function sitemapIndex()
    {
        $xml = CacheVersion::remember('sitemap.index', 3600, function () {
            $types = [
                'pages' => Page::where('is_active', true)->max('updated_at'),
                'categories' => Category::active()->max('updated_at'),
                'brands' => Brand::active()->max('updated_at'),
                'products' => Product::active()->max('updated_at'),
                'posts' => Post::published()->max('updated_at'),
            ];

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($types as $type => $lastmod) {
                $out .= '<sitemap><loc>'.e(route('sitemap.type', $type)).'</loc>'
                    .($lastmod ? '<lastmod>'.date('c', strtotime($lastmod)).'</lastmod>' : '').'</sitemap>';
            }

            return $out.'</sitemapindex>';
        });

        return $this->xml($xml);
    }

    public function sitemap(string $type)
    {
        $xml = CacheVersion::remember("sitemap.$type", 3600, function () use ($type) {
            $urls = match ($type) {
                'pages' => collect([
                    ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
                    ['loc' => route('shop'), 'priority' => '0.9', 'changefreq' => 'daily'],
                    ['loc' => route('blog.index'), 'priority' => '0.6', 'changefreq' => 'weekly'],
                    ['loc' => route('contact'), 'priority' => '0.4'],
                    ['loc' => route('brands'), 'priority' => '0.5'],
                ])->concat(Page::where('is_active', true)->get(['slug', 'updated_at'])
                    ->map(fn ($p) => ['loc' => $p->url, 'lastmod' => $p->updated_at, 'priority' => '0.4'])),
                'categories' => Category::active()->get(['slug', 'updated_at', 'image'])
                    ->map(fn ($c) => ['loc' => $c->url, 'lastmod' => $c->updated_at, 'priority' => '0.8', 'changefreq' => 'daily']),
                'brands' => Brand::active()->get(['slug', 'updated_at'])
                    ->map(fn ($b) => ['loc' => $b->url, 'lastmod' => $b->updated_at, 'priority' => '0.6']),
                'products' => Product::active()->select(['id', 'slug', 'name', 'thumbnail', 'updated_at'])->lazyById(500)
                    ->map(fn ($p) => [
                        'loc' => $p->url, 'lastmod' => $p->updated_at, 'priority' => '0.8', 'changefreq' => 'weekly',
                        'image' => $p->thumbnail ? Img::absolute($p->thumbnail, 1280) : null, 'title' => $p->name,
                    ]),
                'posts' => Post::published()->get(['slug', 'updated_at', 'image'])
                    ->map(fn ($p) => ['loc' => $p->url, 'lastmod' => $p->updated_at, 'priority' => '0.6']),
            };

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

            foreach ($urls as $u) {
                $out .= '<url><loc>'.e($u['loc']).'</loc>';
                if (! empty($u['lastmod'])) {
                    $out .= '<lastmod>'.$u['lastmod']->toAtomString().'</lastmod>';
                }
                if (! empty($u['changefreq'])) {
                    $out .= '<changefreq>'.$u['changefreq'].'</changefreq>';
                }
                $out .= '<priority>'.($u['priority'] ?? '0.5').'</priority>';
                if (! empty($u['image'])) {
                    $out .= '<image:image><image:loc>'.e($u['image']).'</image:loc></image:image>';
                }
                $out .= '</url>';
            }

            return $out.'</urlset>';
        });

        return $this->xml($xml);
    }

    public function robots()
    {
        $lines = [];
        if (setting('seo.discourage_indexing')) {
            $lines = ['User-agent: *', 'Disallow: /'];
        } else {
            $lines = [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /cart',
                'Disallow: /checkout',
                'Disallow: /account',
                'Disallow: /wishlist',
                'Disallow: /search',
                'Disallow: /*?*sort=',
                'Disallow: /*?*min=',
                'Disallow: /*?*max=',
                'Allow: /',
                '',
                'Sitemap: '.route('sitemap'),
            ];
        }

        if ($extra = setting('seo.robots_extra')) {
            $lines[] = '';
            $lines[] = trim($extra);
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Google Merchant Center / Facebook Catalog compatible RSS 2.0 feed. */
    public function productFeed()
    {
        $xml = CacheVersion::remember('feed.products', 3600, function () {
            $currency = setting('general.currency_code', 'BDT');
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>'
                .'<title>'.e(setting('general.site_name')).'</title><link>'.e(url('/')).'</link>'
                .'<description>'.e(setting('seo.meta_description')).'</description>';

            Product::active()->with(['brand:id,name', 'category:id,name'])->lazyById(300)->each(function (Product $p) use (&$out, $currency) {
                $out .= '<item>'
                    .'<g:id>'.$p->id.'</g:id>'
                    .'<g:title>'.e(Str::limit($p->name, 150, '')).'</g:title>'
                    .'<g:description>'.e(Str::limit(strip_tags($p->short_description ?: $p->description ?: $p->name), 4900, '')).'</g:description>'
                    .'<g:link>'.e($p->url).'</g:link>'
                    .'<g:image_link>'.e(Img::absolute($p->thumbnail, 1280)).'</g:image_link>'
                    .'<g:availability>'.($p->inStock() ? 'in_stock' : 'out_of_stock').'</g:availability>'
                    .'<g:price>'.number_format((float) $p->price, 2, '.', '').' '.$currency.'</g:price>'
                    .($p->isOnSale() ? '<g:sale_price>'.number_format((float) $p->sale_price, 2, '.', '').' '.$currency.'</g:sale_price>' : '')
                    .'<g:condition>new</g:condition>'
                    .($p->brand ? '<g:brand>'.e($p->brand->name).'</g:brand>' : '<g:identifier_exists>no</g:identifier_exists>')
                    .($p->category ? '<g:product_type>'.e($p->category->name).'</g:product_type>' : '')
                    .($p->sku ? '<g:mpn>'.e($p->sku).'</g:mpn>' : '')
                    .'</item>';
            });

            return $out.'</channel></rss>';
        });

        return $this->xml($xml);
    }

    private function xml(string $xml)
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
