@php
    $slides = array_values(array_filter((array) $section->get('slides', []), fn ($s) => filled($s['image'] ?? null)));
    $side = array_values(array_filter((array) $section->get('side_banners', []), fn ($s) => filled($s['image'] ?? null)));
    $height = ['sm' => 'aspect-[16/9] md:aspect-[3/1]', 'md' => 'aspect-[4/3] sm:aspect-[16/9] md:aspect-[21/8]', 'lg' => 'aspect-[4/5] sm:aspect-[16/9] md:aspect-[2/1]'][$section->get('height', 'md')] ?? 'aspect-[16/9]';
    $full = $section->get('full_width', false);
@endphp
@if($slides)
<section class="{{ $full ? '' : 'container-x pt-3 sm:pt-5' }}" aria-label="Featured promotions">
    <div class="grid gap-3 {{ $side ? 'lg:grid-cols-[1fr_minmax(0,30%)]' : '' }}">
        <div x-data="slider({{ count($slides) }}, {{ $section->get('autoplay', true) ? 'true' : 'false' }}, {{ (int) $section->get('interval', 5) }})"
             @mouseenter="stop()" @mouseleave="{{ $section->get('autoplay', true) ? 'play()' : '' }}"
             class="relative overflow-hidden {{ $full ? '' : 'rounded-card' }} bg-slate-200">
            <div x-ref="track" class="snap-row">
                @foreach($slides as $i => $slide)
                    @php
                        $align = ['left' => 'items-start text-left', 'center' => 'items-center text-center', 'right' => 'items-end text-right'][$slide['align'] ?? 'left'] ?? 'items-start text-left';
                        $light = ($slide['text_color'] ?? 'light') === 'light';
                        $tag = filled($slide['button_link'] ?? null) && blank($slide['button_text'] ?? null) ? 'a' : 'div';
                    @endphp
                    <{{ $tag }} @if($tag === 'a') href="{{ menu_url($slide['button_link']) }}" @endif class="relative block w-full shrink-0 snap-start {{ $height }}">
                        <picture>
                            @if(filled($slide['mobile_image'] ?? null))
                                <source media="(max-width: 767px)" srcset="{{ img($slide['mobile_image'], 960) }}">
                            @endif
                            <img src="{{ img($slide['image'], 1280) }}" srcset="{{ \App\Support\Img::srcset($slide['image'], [640, 960, 1280, 1600]) }}" sizes="{{ $side ? '(min-width:1024px) 66vw, 100vw' : '100vw' }}"
                                 alt="{{ $slide['heading'] ?? 'Promotion' }}" class="absolute inset-0 size-full object-cover" width="1600" height="700"
                                 @if($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                        </picture>
                        @if(filled($slide['heading'] ?? null) || filled($slide['subheading'] ?? null))
                            @if(! empty($slide['overlay']))
                                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/70 via-slate-950/30 to-transparent"></div>
                            @endif
                            <div class="absolute inset-0 flex flex-col justify-center gap-2 p-6 sm:p-10 md:p-14 {{ $align }}">
                                @if(filled($slide['badge'] ?? null))
                                    <span class="badge bg-primary-500 px-3 py-1 text-xs text-white">{{ $slide['badge'] }}</span>
                                @endif
                                @if(filled($slide['heading'] ?? null))
                                    @if($i === 0 && ! empty($isFirstSection))
                                        <h1 class="max-w-xl text-2xl font-extrabold leading-tight tracking-tight sm:text-4xl md:text-5xl {{ $light ? 'text-white' : 'text-slate-900' }}">{{ $slide['heading'] }}</h1>
                                    @else
                                        <p class="max-w-xl text-2xl font-extrabold leading-tight tracking-tight sm:text-4xl md:text-5xl {{ $light ? 'text-white' : 'text-slate-900' }}">{{ $slide['heading'] }}</p>
                                    @endif
                                @endif
                                @if(filled($slide['subheading'] ?? null))
                                    <p class="max-w-md text-sm sm:text-lg {{ $light ? 'text-white/90' : 'text-slate-700' }}">{{ $slide['subheading'] }}</p>
                                @endif
                                @if(filled($slide['button_text'] ?? null))
                                    <a href="{{ menu_url($slide['button_link'] ?? '/shop') }}" class="btn btn-primary mt-2 sm:btn-lg">{{ $slide['button_text'] }} {{ icon('arrow-right', 'size-4', 2) }}</a>
                                @endif
                            </div>
                        @endif
                    </{{ $tag }}>
                @endforeach
            </div>
            @if(count($slides) > 1)
                <button type="button" @click="go(index - 1)" class="absolute left-3 top-1/2 hidden size-10 -translate-y-1/2 place-items-center rounded-full bg-white/80 text-slate-800 shadow backdrop-blur hover:bg-white md:grid" aria-label="Previous slide">{{ icon('chevron-left', 'size-5', 2) }}</button>
                <button type="button" @click="go(index + 1)" class="absolute right-3 top-1/2 hidden size-10 -translate-y-1/2 place-items-center rounded-full bg-white/80 text-slate-800 shadow backdrop-blur hover:bg-white md:grid" aria-label="Next slide">{{ icon('chevron-right', 'size-5', 2) }}</button>
                <div class="absolute inset-x-0 bottom-1.5 flex justify-center gap-0.5">
                    @foreach($slides as $i => $s)
                        <button type="button" @click="go({{ $i }})" class="grid h-6 min-w-6 place-items-center" aria-label="Go to slide {{ $i + 1 }}"><span class="block h-2 rounded-full bg-white/60 transition-all" :class="index === {{ $i }} ? 'w-6 bg-white' : 'w-2'"></span></button>
                    @endforeach
                </div>
            @endif
        </div>
        @if($side)
            <div class="hidden grid-rows-2 gap-3 lg:grid">
                @foreach(array_slice($side, 0, 2) as $b)
                    <a href="{{ menu_url($b['link'] ?? '#') }}" class="group relative block overflow-hidden rounded-card bg-slate-200">
                        <img src="{{ img($b['image'], 640) }}" alt="{{ $b['alt'] ?? $b['heading'] ?? '' }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" width="640" height="400">
                        @include('partials.banner-overlay', ['b' => $b])
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif
