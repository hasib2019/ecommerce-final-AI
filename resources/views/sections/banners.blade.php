@php
    $banners = array_values(array_filter((array) $section->get('banners', []), fn ($b) => filled($b['image'] ?? null)));
    $cols = max(1, min(4, (int) $section->get('columns', count($banners) ?: 1)));
    $ratio = $section->get('ratio', $cols === 1 ? 'aspect-[4/1]' : ($cols === 2 ? 'aspect-[2/1]' : 'aspect-[4/3]'));
@endphp
@if($banners)
<section class="container-x" aria-label="{{ $section->title ?: 'Promotions' }}">
    @include('partials.section-heading')
    <div class="grid gap-3 sm:gap-4 {{ ['1' => '', '2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-3', '4' => 'grid-cols-2 lg:grid-cols-4'][$cols] }}">
        @foreach($banners as $b)
            <a href="{{ menu_url($b['link'] ?? '#') }}" class="group relative block overflow-hidden rounded-card bg-slate-200 {{ $cols === 1 ? 'aspect-[2/1] sm:aspect-[4/1]' : $ratio }}">
                <picture>
                    @if(filled($b['mobile_image'] ?? null))<source media="(max-width: 639px)" srcset="{{ img($b['mobile_image'], 960) }}">@endif
                    <img src="{{ img($b['image'], $cols === 1 ? 1600 : 960) }}" alt="{{ $b['alt'] ?? $section->title ?? '' }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy" decoding="async" width="960" height="480">
                </picture>
                @include('partials.banner-overlay', ['b' => $b])
            </a>
        @endforeach
    </div>
</section>
@endif
