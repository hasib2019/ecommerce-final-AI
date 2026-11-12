@php
    /** @var \App\Models\Product $product */
    $onSale = $product->isOnSale();
    $inStock = $product->inStock();
    $second = $product->second_image;
    $id = $product->id;
@endphp
<article class="group pcard">
    <a href="{{ $product->url }}" class="pcard-media" tabindex="-1">
        <img src="{{ img($product->thumbnail, 480) }}" srcset="{{ \App\Support\Img::srcset($product->thumbnail, [320, 480, 640]) }}" sizes="(min-width:1280px) 240px, (min-width:768px) 25vw, 50vw" alt="{{ $product->name }}" width="480" height="480" class="pcard-img {{ $second ? 'md:group-hover:opacity-0' : 'group-hover:scale-105' }}" @if($eager ?? false) fetchpriority="high" @else loading="lazy" @endif decoding="async">
        @if($second)<img src="{{ img($second, 480) }}" alt="" width="480" height="480" loading="lazy" decoding="async" class="pcard-img absolute inset-0 hidden opacity-0 group-hover:opacity-100 md:block">@endif
        <span class="absolute left-2 top-2 flex flex-col items-start gap-1">
            @if($onSale)<span class="badge bg-primary-500 text-white">-{{ $product->discount_percent }}%</span>@endif
            @if($product->badge)<span class="badge bg-slate-900 text-white">{{ $product->badge }}</span>@elseif($product->isNew())<span class="badge bg-emerald-700 text-white">New</span>@endif
        </span>
        @if(! $inStock)
            <span class="absolute inset-x-0 bottom-0 bg-slate-900/75 py-1.5 text-center text-xs font-semibold text-white">Out of stock</span>
        @elseif($product->isLowStock())
            <span class="absolute inset-x-0 bottom-0 bg-rose-600/90 py-1 text-center text-[11px] font-semibold text-white">Only {{ $product->stock }} left!</span>
        @endif
    </a>
    <div class="absolute right-2 top-2 flex flex-col gap-1.5" x-data>
        <button type="button" @click="$store.wishlist.toggle({{ $id }})" class="pcard-fab" :class="$store.wishlist.has({{ $id }}) ? 'text-rose-500' : 'text-slate-500'" aria-label="Add to wishlist">
            <span x-show="!$store.wishlist.has({{ $id }})">{{ icon('heart', 'size-4.5', 1.8) }}</span>
            <span x-cloak x-show="$store.wishlist.has({{ $id }})">{{ icon('heart-solid', 'size-4.5') }}</span>
        </button>
        <button type="button" @click="$dispatch('quick-view', '{{ route('product.quick', $id) }}')" class="pcard-fab hidden text-slate-500 opacity-0 group-hover:opacity-100 md:grid" aria-label="Quick view">{{ icon('eye', 'size-4.5', 1.8) }}</button>
    </div>
    <div class="flex flex-1 flex-col p-2.5 sm:p-3">
        <h3 class="pcard-title"><a href="{{ $product->url }}" class="hover:text-primary-600">{{ $product->name }}</a></h3>
        <div class="mt-1 flex h-4 items-center gap-1.5 text-[11px] text-slate-500">
            @if($product->rating_count > 0)@include('partials.stars', ['rating' => $product->rating_avg])<span>({{ $product->rating_count }})</span>@endif
            @if($product->sold_count > 0 && setting('catalog.show_sold_count'))<span>{{ $product->sold_count >= 1000 ? round($product->sold_count / 1000, 1).'k' : $product->sold_count }} sold</span>@endif
        </div>
        <div class="mt-1.5 flex flex-wrap items-baseline gap-x-1.5">
            <span class="text-base font-extrabold text-primary-600 sm:text-lg">{{ money($product->final_price) }}</span>
            @if($onSale)<span class="text-xs text-slate-500 line-through">{{ money($product->price) }}</span>@endif
        </div>
        <div class="mt-auto pt-2.5" x-data>
            @if(! $inStock)
                <a href="{{ $product->url }}" class="pcard-cta border-slate-300 text-slate-600 hover:bg-slate-100 hover:text-slate-800">View details</a>
            @elseif($product->has_variants)
                <a href="{{ $product->url }}" class="pcard-cta">Select options</a>
            @else
                <button type="button" @click="$store.cart.add({{ $id }})" :disabled="$store.cart.busy === {{ $id }}" class="pcard-cta">{{ icon('bag', 'size-4', 1.8) }} Add to cart</button>
            @endif
        </div>
    </div>
</article>
