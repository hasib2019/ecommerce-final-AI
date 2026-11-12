@php
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
@endphp
<div x-data="productPage(@js($config))" class="grid gap-5 p-4 sm:grid-cols-2 sm:p-6">
    <div class="relative aspect-square overflow-hidden rounded-card bg-slate-50">
        <img :src="variantImage || @js(img($product->thumbnail, 960))" alt="{{ $product->name }}" class="size-full object-contain" width="640" height="640">
    </div>
    <div class="flex flex-col">
        <h2 class="pr-8 text-xl font-bold text-slate-900">{{ $product->name }}</h2>
        @if($product->rating_count)
            <div class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">@include('partials.stars', ['rating' => $product->rating_avg]) ({{ $product->rating_count }})</div>
        @endif
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-extrabold text-primary-600" x-text="price"></span>
            <span class="text-slate-400 line-through" x-show="compare" x-text="compare"></span>
        </div>
        @if($product->short_description)<p class="mt-3 line-clamp-4 text-sm text-slate-600">{{ $product->short_description }}</p>@endif

        <template x-for="opt in options" :key="opt.name">
            <div class="mt-4">
                <p class="mb-2 text-sm font-semibold"><span x-text="opt.name"></span>: <span class="font-normal text-slate-500" x-text="selected[opt.name]"></span></p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="val in opt.values" :key="val">
                        <button type="button" @click="pick(opt.name, val)" class="rounded-btn border-2 px-3 py-1.5 text-sm" :class="selected[opt.name] === val ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200'" x-text="val"></button>
                    </template>
                </div>
            </div>
        </template>

        <p class="mt-4 text-sm font-medium" :class="stock > 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="stock > 0 ? 'In stock' : 'Out of stock'"></p>

        <div class="mt-auto space-y-2 pt-5">
            <div class="flex gap-2">
                <div class="flex h-11 items-center rounded-btn border border-slate-300">
                    <button type="button" @click="dec()" class="grid h-full w-9 place-items-center" aria-label="Decrease">{{ icon('minus', 'size-4', 2) }}</button>
                    <span class="w-8 text-center font-semibold" x-text="qty"></span>
                    <button type="button" @click="inc()" class="grid h-full w-9 place-items-center" aria-label="Increase">{{ icon('plus', 'size-4', 2) }}</button>
                </div>
                <button type="button" @click="add(); $store.ui.quick = null" :disabled="!available" class="btn btn-outline h-11 flex-1 border-primary-500 text-primary-600">Add to Cart</button>
            </div>
            <button type="button" @click="add(true)" :disabled="!available" class="btn btn-primary h-11 w-full">Buy Now</button>
            <a href="{{ $product->url }}" class="block pt-1 text-center text-sm font-semibold text-slate-600 hover:text-primary-600">View full details →</a>
        </div>
    </div>
</div>
