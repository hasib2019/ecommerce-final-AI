<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Per-request SEO state. Controllers call seo()->title(...)->description(...) etc.
 * and the layout renders it once in <head>.
 */
class Seo
{
    protected ?string $title = null;

    protected bool $rawTitle = false;

    protected ?string $description = null;

    protected ?string $keywords = null;

    protected ?string $canonical = null;

    protected ?string $image = null;

    protected string $type = 'website';

    protected ?string $robots = null;

    /** @var array<int, array> */
    protected array $schemas = [];

    /** @var array<int, array{name:string,url:?string}> */
    protected array $breadcrumbs = [];

    /** @var array<string, string> */
    protected array $meta = [];

    /** @var array<int, array{rel:string,href:string}> */
    protected array $links = [];

    public function title(?string $title, bool $raw = false): static
    {
        $this->title = $title ? trim($title) : null;
        $this->rawTitle = $raw;

        return $this;
    }

    public function description(?string $description): static
    {
        if ($description) {
            $this->description = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 160, '…');
        }

        return $this;
    }

    public function keywords(?string $keywords): static
    {
        $this->keywords = $keywords ?: $this->keywords;

        return $this;
    }

    public function canonical(?string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function image(?string $path): static
    {
        if ($path) {
            $this->image = Str::startsWith($path, 'http') ? $path : Img::absolute($path, 1280);
        }

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function noindex(bool $follow = true): static
    {
        $this->robots = 'noindex, '.($follow ? 'follow' : 'nofollow');

        return $this;
    }

    public function meta(string $property, string $content): static
    {
        $this->meta[$property] = $content;

        return $this;
    }

    public function link(string $rel, string $href): static
    {
        $this->links[] = compact('rel', 'href');

        return $this;
    }

    public function schema(array $schema): static
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /** @param  array<int, array{0:string,1:?string}>  $items  [name, url] pairs; Home is prepended automatically. */
    public function breadcrumbs(array $items): static
    {
        $this->breadcrumbs = array_merge(
            [['name' => 'Home', 'url' => url('/')]],
            array_map(fn ($i) => ['name' => $i[0], 'url' => $i[1] ?? null], $items)
        );

        return $this;
    }

    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    // ------------------------------------------------------------------ output

    public function fullTitle(): string
    {
        $site = (string) setting('general.site_name');
        $sep = (string) setting('seo.title_separator', ' | ');

        if (! $this->title) {
            return $site.$sep.setting('seo.meta_title');
        }

        return $this->rawTitle ? $this->title : $this->title.$sep.$site;
    }

    public function data(): array
    {
        $robots = $this->robots ?? 'index, follow, max-image-preview:large';
        if (setting('seo.discourage_indexing')) {
            $robots = 'noindex, nofollow';
        }

        return [
            'title' => $this->fullTitle(),
            'description' => $this->description ?: (string) setting('seo.meta_description'),
            'keywords' => $this->keywords ?: (string) setting('seo.meta_keywords'),
            'canonical' => $this->canonical ?: url()->current(),
            'image' => $this->image ?: (setting('seo.og_image') ? Img::absolute(setting('seo.og_image'), 1280) : null),
            'type' => $this->type,
            'robots' => $robots,
            'meta' => $this->meta,
            'links' => $this->links,
            'schemas' => $this->allSchemas(),
        ];
    }

    protected function allSchemas(): array
    {
        $schemas = $this->schemas;

        if (count($this->breadcrumbs) > 1) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($this->breadcrumbs)->values()->map(fn ($b, $i) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $b['name'],
                    'item' => $b['url'],
                ]))->all(),
            ];
        }

        return $schemas;
    }

    /** Organization + WebSite (with sitelinks search box) – emitted on the home page. */
    public static function siteSchemas(): array
    {
        $social = array_values(array_filter((array) setting('footer.social', [])));
        $logo = setting('general.logo') ? Img::absolute(setting('general.logo'), 640) : null;

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => setting('seo.organization_type', 'OnlineStore'),
                'name' => setting('general.site_name'),
                'url' => url('/'),
                'logo' => $logo,
                'email' => setting('general.email'),
                'telephone' => setting('general.phone'),
                'address' => setting('general.address') ? [
                    '@type' => 'PostalAddress',
                    'streetAddress' => setting('general.address'),
                    'addressCountry' => 'BD',
                ] : null,
                'sameAs' => $social ?: null,
            ]),
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => setting('general.site_name'),
                'url' => url('/'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }
}
