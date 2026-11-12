@extends('layouts.app')

@php
    $images = $product->images ?: [null];
    $c = \App\Support\Settings::group('conversion');
    $wa = preg_replace('/\D/', '', (string) setting('general.whatsapp'));
    $phone = setting('general.phone');
    $config = [
        'id' => $product->id,
        'name' => $product->name,
        'priceRaw' => $product->final_price,
        'basePrice' => money($product->final_price),
        'baseCompare' => $product->compare_price ? money($product->compare_price) : null,
        'baseStock' => $product->track_stock ? (int) $product->stock : 999,
        'options' => $variantData['options'],
        'variants' => $variantData['variants'],
    ];
    $specs = array_filter((array) $product->specifications, fn ($s) => filled($s['label'] ?? null));
@endphp

@push('preload')
    <link rel="preload" as="image" href="{{ img($images[0], 960) }}" imagesrcset="{{ \App\Support\Img::srcset($images[0], [480, 640, 960]) }}" imagesizes="(min-width: 1024px) 600px, 100vw" fetchpriority="high">
@endpush

@section('content')
@include('partials.breadcrumbs')

<div class="container-x pb-32 pt-4 md:pb-12" x-data="productPage(@js($config))">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-10">

        {{-- ===== Gallery ===== --}}
        <div class="lg:sticky lg:top-36 lg:self-start">
            <div class="relative overflow-hidden rounded-card border border-slate-200/80 bg-white">
                <div x-ref="gallery" @scroll.passive="onScroll()" class="snap-row aspect-square">
                    @foreach($images as $i => $image)
                        <div class="relative aspect-square w-full shrink-0 snap-center overflow-hidden"
                             @mouseenter="zoomable() && (zoom = {{ $i }} + 1)" @mouseleave="zoom = false" @mousemove="zoomAt($event)">
                            <img src="{{ img($image, 960) }}" srcset="{{ \App\Support\Img::srcset($image, [480, 640, 960, 1280]) }}" sizes="(min-width: 1024px) 600px, 100vw"
                                 alt="{{ $product->name }}{{ $i ? ' – image '.($i + 1) : '' }}" width="960" height="960"
                                 class="size-full object-contain transition-transform duration-200"
                                 :style="zoom === {{ $i + 1 }} ? `transform-origin:${origin};transform:scale(1.9)` : ''"
                                 @if($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                        </div>
                    @endforeach
                </div>
                <img x-cloak x-show="variantImage" :src="variantImage" alt="{{ $product->name }}" class="absolute inset-0 size-full bg-white object-contain" width="960" height="960">

                <div class="pointer-events-none absolute left-3 top-3 flex flex-col gap-1.5">
                    @if($product->isOnSale())
                        <span class="badge bg-primary-500 px-2.5 py-1 text-xs text-white">-{{ $product->discount_percent }}% OFF</span>
                    @endif
                    @if($product->badge)<span class="badge bg-slate-900 px-2.5 py-1 text-xs text-white">{{ $product->badge }}</span>@endif
                </div>
                <button type="button" @click="$store.wishlist.toggle({{ $product->id }})" class="absolute right-3 top-3 grid size-10 place-items-center rounded-full bg-white shadow-md transition hover:scale-110"
                        :class="$store.wishlist.has({{ $product->id }}) ? 'text-rose-500' : 'text-slate-500'" aria-label="Save to wishlist">
                    <span x-show="!$store.wishlist.has({{ $product->id }})">{{ icon('heart', 'size-5', 1.8) }}</span>
                    <span x-cloak x-show="$store.wishlist.has({{ $product->id }})">{{ icon('heart-solid', 'size-5') }}</span>
                </button>
                @if(count($images) > 1)
                    <span class="absolute bottom-3 right-3 rounded-full bg-slate-900/70 px-2.5 py-1 text-xs font-medium text-white md:hidden" x-text="(image + 1) + ' / {{ count($images) }}'"></span>
                @endif
            </div>

            @if(count($images) > 1)
                <div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto">
                    @foreach($images as $i => $image)
                        <button type="button" @click="scrollToImage({{ $i }})" class="size-16 shrink-0 overflow-hidden rounded-lg border-2 bg-white transition sm:size-20"
                                :class="image === {{ $i }} && !variantImage ? 'border-primary-500' : 'border-transparent opacity-70 hover:opacity-100'" aria-label="Show image {{ $i + 1 }}">
                            <img src="{{ img($image, 160) }}" alt="" class="size-full object-cover" loading="lazy" width="80" height="80">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== Buy box ===== --}}
        <div class="min-w-0">
            @if($product->brand)
                <a href="{{ $product->brand->url }}" class="text-sm font-semibold text-primary-600 hover:underline">{{ $product->brand->name }}</a>
            @endif
            <h1 class="mt-1 text-xl font-bold leading-snug text-slate-900 sm:text-2xl lg:text-3xl">{{ $product->name }}</h1>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                @if($product->rating_count > 0)
                    <a href="#reviews" class="flex items-center gap-1.5 hover:text-primary-600">
                        @include('partials.stars', ['rating' => $product->rating_avg, 'size' => 'text-sm'])
                        <span class="font-semibold text-slate-700">{{ number_format($product->rating_avg, 1) }}</span>
                        <span>({{ $product->rating_count }} reviews)</span>
                    </a>
                @else
                    <a href="#reviews" class="hover:text-primary-600">Be the first to review</a>
                @endif
                @if(setting('catalog.show_sold_count') && $product->sold_count > 0)
                    <span class="flex items-center gap-1">{{ icon('fire', 'size-4 text-primary-500') }} {{ number_format($product->sold_count) }} sold</span>
                @endif
                @if($product->sku)<span>SKU: {{ $product->sku }}</span>@endif
            </div>

            {{-- Price --}}
            <div class="mt-4 rounded-card bg-gradient-to-r from-primary-50 to-white p-4">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-3xl font-extrabold tracking-tight text-primary-600" x-text="price">{{ money($product->final_price) }}</span>
                    <span class="text-lg text-slate-500 line-through" x-show="compare" x-text="compare">{{ $product->compare_price ? money($product->compare_price) : '' }}</span>
                    @if($product->isOnSale())
                        <span class="badge bg-emerald-100 px-2.5 py-1 text-xs text-emerald-700">You save {{ money($product->price - $product->final_price) }}</span>
                    @endif
                </div>
                @if($product->isOnSale() && $product->sale_ends_at)
                    <div x-data="countdown(@js($product->sale_ends_at->toIso8601String()))" x-show="!done" class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                        <span class="flex items-center gap-1 font-semibold text-rose-600">{{ icon('clock', 'size-4') }} Offer ends in</span>
                        <span class="flex gap-1 font-mono font-bold">
                            <span class="rounded bg-slate-900 px-1.5 py-0.5 text-white" x-text="d + 'd'"></span>
                            <span class="rounded bg-slate-900 px-1.5 py-0.5 text-white" x-text="h + 'h'"></span>
                            <span class="rounded bg-slate-900 px-1.5 py-0.5 text-white" x-text="m + 'm'"></span>
                            <span class="rounded bg-slate-900 px-1.5 py-0.5 text-white" x-text="s + 's'"></span>
                        </span>
                    </div>
                @endif
            </div>

            @if($product->short_description)
                <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $product->short_description }}</p>
            @endif

            {{-- Variant pickers --}}
            <template x-for="opt in options" :key="opt.name">
                <div class="mt-5">
                    <p class="mb-2 text-sm font-semibold text-slate-800"><span x-text="opt.name"></span>: <span class="font-normal text-slate-500" x-text="selected[opt.name] || 'Select'"></span></p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="val in opt.values" :key="val">
                            <button type="button" @click="pick(opt.name, val)"
                                    class="min-w-12 rounded-btn border-2 px-3.5 py-2 text-sm font-medium transition"
                                    :class="{
                                        'border-primary-500 bg-primary-50 text-primary-700': selected[opt.name] === val,
                                        'border-slate-200 bg-white text-slate-700 hover:border-slate-400': selected[opt.name] !== val,
                                        'opacity-40 line-through': !isOptionAvailable(opt.name, val)
                                    }" x-text="val"></button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Stock / urgency --}}
            <div class="mt-5 text-sm font-medium">
                <template x-if="stock <= 0">
                    <p class="flex items-center gap-2 text-rose-600">{{ icon('info', 'size-5') }} Out of stock</p>
                </template>
                <template x-if="stock > 0 && stock <= {{ (int) $product->low_stock_threshold }} && {{ setting('catalog.show_stock_count') ? 'true' : 'false' }}">
                    <p class="flex items-center gap-2 text-rose-600">
                        <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-rose-400 opacity-75"></span><span class="relative inline-flex size-2.5 rounded-full bg-rose-500"></span></span>
                        <span x-text="@js(setting('catalog.low_stock_text', 'Only :count left')).replace(':count', stock)"></span>
                    </p>
                </template>
                <template x-if="stock > {{ (int) $product->low_stock_threshold }} || (stock > 0 && !{{ setting('catalog.show_stock_count') ? 'true' : 'false' }})">
                    <p class="flex items-center gap-2 text-emerald-700">{{ icon('check-circle', 'size-5') }} In stock – ready to ship</p>
                </template>
            </div>

            {{-- Quantity + CTAs --}}
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="flex h-12 items-center rounded-btn border border-slate-300 bg-white">
                    <button type="button" @click="dec()" class="grid h-full w-11 place-items-center text-slate-600 hover:text-primary-600" aria-label="Decrease quantity">{{ icon('minus', 'size-4', 2) }}</button>
                    <input type="number" x-model.number="qty" min="1" :max="stock" class="h-full w-12 border-0 bg-transparent text-center font-semibold [appearance:textfield] focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none" aria-label="Quantity">
                    <button type="button" @click="inc()" class="grid h-full w-11 place-items-center text-slate-600 hover:text-primary-600" aria-label="Increase quantity">{{ icon('plus', 'size-4', 2) }}</button>
                </div>
                <button type="button" @click="add()" :disabled="!available || $store.cart.busy" class="btn btn-lg h-12 flex-1 border-2 border-primary-500 bg-white text-primary-600 hover:bg-primary-50">
                    {{ icon('bag', 'size-5', 1.8) }} Add to Cart
                </button>
            </div>
            @if(! empty($c['buy_now']))
                <button type="button" @click="add(true)" :disabled="!available || $store.cart.busy" class="btn btn-primary btn-lg mt-3 h-13 w-full text-base">
                    {{ icon('bolt', 'size-5') }} Buy Now – Cash on Delivery
                </button>
            @endif

            @if((! empty($c['whatsapp_order']) && $wa) || (! empty($c['call_to_order']) && $phone))
                <div class="mt-3 grid gap-3 {{ (! empty($c['whatsapp_order']) && $wa) && (! empty($c['call_to_order']) && $phone) ? 'grid-cols-2' : '' }}">
                    @if(! empty($c['whatsapp_order']) && $wa)
                        <button type="button" @click="whatsapp('{{ $wa }}', '{{ $product->url }}')" class="btn h-12 bg-emerald-700 text-white hover:bg-emerald-800">{{ icon('whatsapp', 'size-5') }} WhatsApp<span class="hidden sm:inline"> Order</span></button>
                    @endif
                    @if(! empty($c['call_to_order']) && $phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="btn btn-dark h-12">{{ icon('phone', 'size-5') }} Call to Order</a>
                    @endif
                </div>
            @endif

            {{-- Delivery & trust --}}
            <div class="mt-5 divide-y divide-slate-100 rounded-card border border-slate-200/80 bg-white text-sm">
                @if($d = setting('catalog.delivery_info'))
                    <div class="flex items-start gap-3 p-3.5">{{ icon('truck', 'size-5 shrink-0 text-primary-500') }}<div><p class="font-semibold text-slate-800">Delivery</p><p class="text-slate-500">{{ $d }}</p></div></div>
                @endif
                @if(setting('checkout.cod_enabled'))
                    <div class="flex items-start gap-3 p-3.5">{{ icon('cash', 'size-5 shrink-0 text-primary-500') }}<div><p class="font-semibold text-slate-800">Cash on Delivery</p><p class="text-slate-500">Pay when you receive your order.</p></div></div>
                @endif
                @if($r = setting('catalog.return_info'))
                    <div class="flex items-start gap-3 p-3.5">{{ icon('refresh', 'size-5 shrink-0 text-primary-500') }}<div><p class="font-semibold text-slate-800">Returns</p><p class="text-slate-500">{{ $r }}</p></div></div>
                @endif
            </div>

            <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                @if($product->category)
                    <dt class="text-slate-500">Category</dt><dd><a href="{{ $product->category->url }}" class="font-medium text-slate-700 hover:text-primary-600">{{ $product->category->name }}</a></dd>
                @endif
                @if($product->brand)
                    <dt class="text-slate-500">Brand</dt><dd><a href="{{ $product->brand->url }}" class="font-medium text-slate-700 hover:text-primary-600">{{ $product->brand->name }}</a></dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- ===== Details tabs ===== --}}
    <h2 class="sr-only">Product details</h2>
    <div class="mt-10 scroll-mt-40" id="reviews" x-data="{ tab: location.hash === '#reviews' ? 'reviews' : 'description' }" @hashchange.window="if (location.hash === '#reviews') tab = 'reviews'">
        <div class="no-scrollbar flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
            <button type="button" role="tab" @click="tab = 'description'" :aria-selected="tab === 'description'" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition" :class="tab === 'description' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-800'">Description</button>
            @if($specs)
                <button type="button" role="tab" @click="tab = 'specs'" :aria-selected="tab === 'specs'" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition" :class="tab === 'specs' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-800'">Specifications</button>
            @endif
            @if(setting('catalog.reviews_enabled'))
                <button type="button" role="tab" @click="tab = 'reviews'" :aria-selected="tab === 'reviews'" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition" :class="tab === 'reviews' ? 'border-primary-500 text-primary-600' : 'border-transparent text-slate-500 hover:text-slate-800'">Reviews ({{ $product->rating_count }})</button>
            @endif
        </div>

        <div class="card mt-4 p-5 sm:p-7">
            <div x-show="tab === 'description'" class="prose-content max-w-4xl">
                {!! $product->description ?: '<p>'.e($product->short_description).'</p>' !!}
                @if($product->video_url && preg_match('~(?:youtu\.be/|v=|embed/)([\w-]{11})~', $product->video_url, $yt))
                    <div class="mt-6 aspect-video overflow-hidden rounded-card bg-slate-900">
                        <iframe src="https://www.youtube-nocookie.com/embed/{{ $yt[1] }}" title="{{ $product->name }} video" class="size-full" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                @endif
            </div>

            @if($specs)
                <div x-cloak x-show="tab === 'specs'">
                    <table class="w-full max-w-3xl text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @foreach($specs as $spec)
                                <tr><th class="w-1/3 py-2.5 pr-4 text-left font-medium text-slate-500">{{ $spec['label'] }}</th><td class="py-2.5 text-slate-800">{{ $spec['value'] ?? '' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(setting('catalog.reviews_enabled'))
                <div x-cloak x-show="tab === 'reviews'">
                    @include('product.reviews')
                </div>
            @endif
        </div>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-10" aria-label="Related products">
            <h2 class="section-title mb-4">You may also like</h2>
            @include('partials.product-row', ['products' => $related, 'cols' => 5])
        </section>
    @endif

    @if($recentlyViewed->isNotEmpty())
        <section class="mt-10" aria-label="Recently viewed">
            <h2 class="section-title mb-4">Recently viewed</h2>
            @include('partials.product-row', ['products' => $recentlyViewed, 'cols' => 6])
        </section>
    @endif

    {{-- Sticky mobile purchase bar --}}
    @if(! empty($c['sticky_add_to_cart']))
        <div class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-30 border-t border-slate-200 bg-white/95 p-2.5 shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.25)] backdrop-blur md:hidden">
            <div class="flex items-center gap-2">
                <div class="min-w-0 flex-1 pl-1">
                    <p class="truncate text-xs text-slate-500">{{ $product->name }}</p>
                    <p class="text-lg font-extrabold leading-tight text-primary-600" x-text="price">{{ money($product->final_price) }}</p>
                </div>
                <button type="button" @click="add()" :disabled="!available" class="btn btn-outline h-11 border-primary-500 px-3 text-primary-600" aria-label="Add to cart">{{ icon('bag', 'size-5', 1.8) }}</button>
                <button type="button" @click="add({{ ! empty($c['buy_now']) ? 'true' : 'false' }})" :disabled="!available" class="btn btn-primary h-11 px-5">{{ ! empty($c['buy_now']) ? 'Buy Now' : 'Add to Cart' }}</button>
            </div>
        </div>
    @endif
</div>
@endsection
