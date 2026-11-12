@extends('layouts.app')

@section('content')
@include('partials.breadcrumbs')
<div class="container-x grid gap-8 pb-12 pt-4 lg:grid-cols-[1fr_20rem]">
    <article class="min-w-0">
        <header>
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl">{{ $post->title }}</h1>
            <p class="mt-3 text-sm text-slate-500">
                <time datetime="{{ optional($post->published_at ?? $post->created_at)->toDateString() }}">{{ optional($post->published_at ?? $post->created_at)->format('F d, Y') }}</time>
                · {{ $post->readingMinutes() }} min read
                @if($post->author) · by {{ $post->author->name }}@endif
            </p>
        </header>
        @if($post->image)
            <img src="{{ img($post->image, 1280) }}" srcset="{{ \App\Support\Img::srcset($post->image, [640, 960, 1280]) }}" sizes="(min-width:1024px) 800px, 100vw" alt="{{ $post->title }}" class="mt-6 aspect-[16/9] w-full rounded-card object-cover" fetchpriority="high" width="1280" height="720">
        @endif
        <div class="prose-content mt-6 text-base">{!! $post->content !!}</div>
    </article>
    <aside class="space-y-6">
        @if($products->isNotEmpty())
            <div>
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Popular products</h2>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        @endif
        @if($recent->isNotEmpty())
            <div>
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">More articles</h2>
                <ul class="space-y-3">
                    @foreach($recent as $r)
                        <li><a href="{{ $r->url }}" class="flex gap-3 hover:text-primary-600">
                            <span class="size-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">@if($r->image)<img src="{{ img($r->image, 160) }}" alt="" class="size-full object-cover" loading="lazy" width="64" height="64">@endif</span>
                            <span class="line-clamp-2 text-sm font-semibold">{{ $r->title }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </aside>
</div>
@endsection
