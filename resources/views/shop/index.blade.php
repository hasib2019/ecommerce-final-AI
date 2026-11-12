@extends('layouts.app')

@section('content')
@php
    $sorts = ['' => 'Newest first', 'popular' => 'Best selling', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'rating' => 'Top rated'];
    $selectedBrands = (array) request('brand', []);
@endphp

@include('partials.breadcrumbs')

<div class="container-x pb-12 pt-4">
    {{-- Heading --}}
    @if(! empty($banner))
        <div class="relative mb-5 overflow-hidden rounded-card bg-slate-200">
            <img src="{{ img($banner, 1600) }}" srcset="{{ \App\Support\Img::srcset($banner, [640, 960, 1600]) }}" sizes="100vw" alt="{{ $heading }}" class="aspect-[3/1] w-full object-cover sm:aspect-[5/1]" fetchpriority="high" width="1600" height="320">
        </div>
    @endif
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $heading }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} found</p>
        </div>
        @if(! empty($brandModel?->logo))
            <img src="{{ img($brandModel->logo, 320) }}" alt="{{ $brandModel->name }}" class="h-12 w-auto object-contain" width="160" height="48">
        @endif
    </div>

    @if(! empty($subcategories) && $subcategories->isNotEmpty())
        <div class="snap-row mb-5 gap-2">
            @foreach($subcategories as $sub)
                <a href="{{ $sub->url }}" class="flex shrink-0 snap-start items-center gap-2 rounded-full border border-slate-200 bg-white py-1.5 pl-1.5 pr-4 text-sm font-medium text-slate-700 hover:border-primary-400 hover:text-primary-600">
                    <span class="size-8 overflow-hidden rounded-full bg-slate-100">@if($sub->image)<img src="{{ img($sub->image, 160) }}" alt="" class="size-full object-cover" loading="lazy" width="32" height="32">@endif</span>
                    {{ $sub->name }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="flex gap-6">
        {{-- Filters --}}
        <div x-data class="contents">
            <div x-cloak x-show="$store.ui.filters" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-900/50 lg:hidden" @click="$store.ui.filters = false"></div>
            <aside :class="$store.ui.filters && 'translate-x-0!'"
                   class="fixed inset-y-0 left-0 z-[61] w-[85%] max-w-xs max-lg:-translate-x-full overflow-y-auto bg-white p-5 shadow-2xl transition-transform duration-200 lg:static lg:z-auto lg:w-64 lg:max-w-none lg:shrink-0 lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none"
                   aria-label="Filters">
                <form method="GET" action="{{ url()->current() }}" x-ref="filters" class="space-y-5 lg:sticky lg:top-36">
                    <div class="flex items-center justify-between lg:hidden">
                        <h2 class="text-lg font-bold">Filters</h2>
                        <button type="button" class="rounded-full p-2 hover:bg-slate-100" @click="$store.ui.filters = false" aria-label="Close filters">{{ icon('x', 'size-5') }}</button>
                    </div>
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif

                    @if(empty($category) && $categories->isNotEmpty())
                        <div class="card p-4">
                            <h3 class="mb-3 text-sm font-bold text-slate-900">Categories</h3>
                            <ul class="space-y-1 text-sm">
                                @foreach($categories as $cat)
                                    <li><a href="{{ $cat->url }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-primary-600">{{ $cat->name }} {{ icon('chevron-right', 'size-3.5 text-slate-400') }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @elseif(! empty($category))
                        <div class="card p-4">
                            <h3 class="mb-3 text-sm font-bold text-slate-900">Browse</h3>
                            <ul class="space-y-1 text-sm">
                                @if($category->parent)
                                    <li><a href="{{ $category->parent->url }}" class="flex items-center gap-1 px-2 py-1.5 text-slate-500 hover:text-primary-600">{{ icon('chevron-left', 'size-3.5') }} {{ $category->parent->name }}</a></li>
                                @endif
                                <li class="px-2 py-1.5 font-semibold text-primary-600">{{ $category->name }}</li>
                                @foreach($subcategories as $sub)
                                    <li><a href="{{ $sub->url }}" class="block rounded-lg py-1.5 pl-5 pr-2 text-slate-700 hover:bg-slate-50 hover:text-primary-600">{{ $sub->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card p-4">
                        <h3 class="mb-3 text-sm font-bold text-slate-900">Price range</h3>
                        <div class="flex items-center gap-2">
                            <label class="sr-only" for="f-min">Minimum price</label>
                            <input id="f-min" type="number" name="min" min="0" value="{{ request('min') }}" placeholder="{{ $priceRange[0] }}" class="input px-2.5 py-2" inputmode="numeric">
                            <span class="text-slate-400">–</span>
                            <label class="sr-only" for="f-max">Maximum price</label>
                            <input id="f-max" type="number" name="max" min="0" value="{{ request('max') }}" placeholder="{{ $priceRange[1] }}" class="input px-2.5 py-2" inputmode="numeric">
                        </div>
                    </div>

                    @if($brands->isNotEmpty() && empty($brandModel))
                        <div class="card p-4" x-data="{ more: false }">
                            <h3 class="mb-3 text-sm font-bold text-slate-900">Brands</h3>
                            <div class="space-y-2">
                                @foreach($brands as $brand)
                                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700" @if($loop->index >= 6) x-show="more" @endif>
                                        <input type="checkbox" name="brand[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $selectedBrands)) @change="$refs.filters.submit()" class="size-4 rounded border-slate-300 text-primary-500 accent-[var(--brand)]">
                                        {{ $brand->name }}
                                    </label>
                                @endforeach
                            </div>
                            @if($brands->count() > 6)
                                <button type="button" class="mt-2 text-xs font-semibold text-primary-600" @click="more = ! more" x-text="more ? 'Show less' : 'Show all ({{ $brands->count() }})'"></button>
                            @endif
                        </div>
                    @endif

                    <div class="card space-y-2.5 p-4">
                        <h3 class="mb-1 text-sm font-bold text-slate-900">Availability & offers</h3>
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock')) @change="$refs.filters.submit()" class="size-4 accent-[var(--brand)]"> In stock only
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="on_sale" value="1" @checked(request()->boolean('on_sale')) @change="$refs.filters.submit()" class="size-4 accent-[var(--brand)]"> On sale {{ icon('fire', 'size-4 text-primary-500') }}
                        </label>
                    </div>

                    <div class="card p-4">
                        <h3 class="mb-3 text-sm font-bold text-slate-900">Customer rating</h3>
                        <div class="space-y-2">
                            @foreach([4, 3, 2] as $r)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700">
                                    <input type="radio" name="rating" value="{{ $r }}" @checked((int) request('rating') === $r) @change="$refs.filters.submit()" class="size-4 accent-[var(--brand)]">
                                    @include('partials.stars', ['rating' => $r, 'size' => 'text-sm']) & up
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button class="btn btn-primary flex-1">Apply filters</button>
                        @if($activeFilters)
                            <a href="{{ url()->current() }}{{ request('q') ? '?q='.urlencode(request('q')) : '' }}" class="btn btn-outline">Reset</a>
                        @endif
                    </div>
                </form>
            </aside>
        </div>

        {{-- Results --}}
        <div class="min-w-0 flex-1">
            <div class="mb-4 flex items-center justify-between gap-3 rounded-card border border-slate-200/80 bg-white p-2 pl-3">
                <button type="button" x-data @click="$store.ui.filters = true" class="btn btn-outline btn-sm gap-1.5 py-2 lg:hidden">
                    {{ icon('filter', 'size-4') }} Filters
                    @if($activeFilters)<span class="grid size-5 place-items-center rounded-full bg-primary-500 text-[10px] text-white">{{ $activeFilters }}</span>@endif
                </button>
                <p class="hidden text-sm text-slate-500 lg:block">Showing <strong class="text-slate-800">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</strong> of {{ $products->total() }}</p>
                <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
                    @foreach(request()->except(['sort', 'page']) as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="sort" class="sr-only text-sm text-slate-500 sm:not-sr-only">Sort by</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="input w-auto py-2 pr-8 text-sm font-medium">
                        @foreach($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($products->isEmpty())
                <div class="card flex flex-col items-center px-6 py-16 text-center">
                    <span class="grid size-16 place-items-center rounded-full bg-slate-100 text-slate-400">{{ icon('search', 'size-8') }}</span>
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No products match your selection</h2>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">Try removing some filters or searching with a different keyword.</p>
                    <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Browse all products</a>
                </div>
            @else
                @include('partials.product-grid', ['products' => $products, 'cols' => 4, 'eagerCount' => 4])
                {{ $products->onEachSide(1)->links() }}
            @endif

            @if(! empty($description) && $products->currentPage() === 1)
                <div class="prose-content mt-10 rounded-card border border-slate-200/80 bg-white p-5 text-sm sm:p-6">{!! nl2br(e($description)) !!}</div>
            @endif
        </div>
    </div>
</div>
@endsection
