<article class="group overflow-hidden rounded-card border border-slate-200/80 bg-white transition hover:shadow-lg">
    <a href="{{ $post->url }}" class="block aspect-[16/9] overflow-hidden bg-slate-100">
        @if($post->image)
            <img src="{{ img($post->image, 640) }}" alt="{{ $post->title }}" class="size-full object-cover transition duration-500 group-hover:scale-105" @if($eager ?? false) fetchpriority="high" @else loading="lazy" @endif width="640" height="360">
        @endif
    </a>
    <div class="p-4 sm:p-5">
        <time datetime="{{ optional($post->published_at)->toDateString() }}" class="text-xs font-medium text-primary-600">{{ optional($post->published_at)->format('M d, Y') }}</time>
        <{{ $tag = $heading ?? 'h3' }} class="mt-1.5 line-clamp-2 text-base font-bold text-slate-900 group-hover:text-primary-600"><a href="{{ $post->url }}">{{ $post->title }}</a></{{ $tag }}>
        @if($post->excerpt)<p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $post->excerpt }}</p>@endif
    </div>
</article>
