<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use App\Support\Img;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::published()->latest('published_at')
            ->paginate(9, ['id', 'title', 'slug', 'excerpt', 'image', 'published_at', 'content']);

        seo()->title('Blog'.($posts->currentPage() > 1 ? ' – Page '.$posts->currentPage() : ''))
            ->description('Tips, guides and news from '.setting('general.site_name').'.')
            ->canonical(route('blog.index').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))
            ->breadcrumbs([['Blog', route('blog.index')]]);

        return view('blog.index', compact('posts'));
    }

    public function show(Post $post)
    {
        abort_unless($post->is_published && (! $post->published_at || $post->published_at->isPast()), 404);
        $post->load('author:id,name');

        $recent = Post::published()->whereKeyNot($post->id)->latest('published_at')->limit(3)->get(['id', 'title', 'slug', 'image', 'published_at']);
        $products = Product::forCard()->inStock()->orderByDesc('sold_count')->limit(4)->get();

        seo()->title($post->meta_title ?: $post->title)
            ->description($post->seo_description)
            ->image($post->image)
            ->type('article')
            ->canonical($post->url)
            ->meta('article:published_time', optional($post->published_at ?? $post->created_at)->toIso8601String())
            ->schema(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'description' => $post->seo_description,
                'image' => $post->image ? Img::absolute($post->image, 1280) : null,
                'datePublished' => optional($post->published_at ?? $post->created_at)->toIso8601String(),
                'dateModified' => $post->updated_at->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? setting('general.site_name')],
                'publisher' => ['@type' => 'Organization', 'name' => setting('general.site_name')],
                'mainEntityOfPage' => $post->url,
            ]))
            ->breadcrumbs([['Blog', route('blog.index')], [$post->title, $post->url]]);

        return view('blog.show', compact('post', 'recent', 'products'));
    }
}
