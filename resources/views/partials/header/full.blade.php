@php
    $h = \App\Support\Settings::group('header');
    $layout = $h['layout'] ?? 'classic';
    $menu = (array) ($h['menu'] ?? []);
    $categories = \App\Models\Category::menuTree();
    $sticky = ! empty($h['sticky']);
@endphp

{{-- Announcement bar --}}
@if(! empty($h['topbar_enabled']) && filled($h['topbar_text'] ?? null))
    <div class="text-xs" style="background: {{ $h['topbar_bg'] ?? '#0f172a' }}; color: {{ $h['topbar_color'] ?? '#fff' }}">
        <div class="container-x flex h-9 items-center justify-between gap-4">
            <p class="truncate text-center font-medium max-md:w-full">{{ $h['topbar_text'] }}</p>
            <div class="hidden shrink-0 items-center gap-4 md:flex">
                @foreach((array) ($h['topbar_links'] ?? []) as $link)
                    @if(filled($link['label'] ?? null))
                        <a href="{{ menu_url($link['url'] ?? '#') }}" class="opacity-90 hover:opacity-100 hover:underline">{{ $link['label'] }}</a>
                    @endif
                @endforeach
                @if(! empty($h['show_hotline']) && setting('general.phone'))
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', setting('general.phone')) }}" class="flex items-center gap-1 font-semibold">{{ icon('phone', 'size-3.5') }} {{ setting('general.phone') }}</a>
                @endif
            </div>
        </div>
    </div>
@endif

<header x-data="{ scrolled: false }" @scroll.window.passive="scrolled = window.scrollY > 40"
        class="{{ $sticky ? 'sticky top-0' : 'relative' }} z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85 transition-shadow"
        :class="scrolled && 'shadow-md shadow-slate-900/5'">

    @if($layout === 'centered')
        <div class="container-x grid h-16 grid-cols-[1fr_auto_1fr] items-center gap-3 md:h-20">
            <div class="flex items-center gap-1">
                @include('partials.header.menu-button')
                <button type="button" class="btn-ghost hidden rounded-full p-2.5 md:inline-flex" @click="$store.ui.search = ! $store.ui.search" aria-label="Search">{{ icon('search', 'size-5') }}</button>
            </div>
            @include('partials.header.logo', ['center' => true])
            @include('partials.header.icons')
        </div>
        <nav class="hidden border-t border-slate-100 md:block" aria-label="Main">
            <div class="container-x flex h-12 items-center justify-center gap-1">
                @include('partials.header.category-menu', ['categories' => $categories, 'h' => $h])
                @include('partials.header.nav-links', ['menu' => $menu])
            </div>
        </nav>
        <div x-cloak x-show="$store.ui.search" x-transition.opacity class="hidden border-t border-slate-100 py-3 md:block">
            <div class="container-x max-w-2xl">@include('partials.header.search', ['h' => $h])</div>
        </div>
    @elseif($layout === 'minimal')
        <div class="container-x flex h-16 items-center gap-3 md:h-[4.5rem]">
            @include('partials.header.menu-button')
            @include('partials.header.logo')
            <nav class="mx-auto hidden items-center gap-1 lg:flex" aria-label="Main">
                @include('partials.header.nav-links', ['menu' => $menu])
            </nav>
            <button type="button" class="btn-ghost ml-auto hidden rounded-full p-2.5 md:inline-flex lg:ml-0" @click="$store.ui.search = ! $store.ui.search" aria-label="Search">{{ icon('search', 'size-5') }}</button>
            @include('partials.header.icons')
        </div>
        <div x-cloak x-show="$store.ui.search" x-transition.opacity class="hidden border-t border-slate-100 py-3 md:block">
            <div class="container-x max-w-2xl">@include('partials.header.search', ['h' => $h])</div>
        </div>
    @else
        {{-- classic --}}
        <div class="container-x flex h-16 items-center gap-3 md:h-20 md:gap-6">
            @include('partials.header.menu-button')
            @include('partials.header.logo')
            @if(! empty($h['show_search']))
                <div class="hidden flex-1 md:block md:max-w-2xl">@include('partials.header.search', ['h' => $h])</div>
            @endif
            @include('partials.header.icons')
        </div>
        <nav class="hidden border-t border-slate-100 md:block" aria-label="Main">
            <div class="container-x flex h-12 items-center gap-1">
                @include('partials.header.category-menu', ['categories' => $categories, 'h' => $h])
                @include('partials.header.nav-links', ['menu' => $menu])
                @if(! empty($h['show_hotline']) && setting('general.phone'))
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', setting('general.phone')) }}" class="ml-auto hidden items-center gap-2 text-sm lg:flex">
                        <span class="grid size-8 place-items-center rounded-full bg-primary-50 text-primary-600">{{ icon('headset', 'size-4') }}</span>
                        <span class="leading-tight"><span class="block text-[11px] text-slate-500">Hotline</span><span class="font-semibold text-slate-900">{{ setting('general.phone') }}</span></span>
                    </a>
                @endif
            </div>
        </nav>
    @endif

    {{-- Mobile search (always visible on phones – the #1 way people shop on mobile) --}}
    @if(! empty($h['show_search']))
        <div class="container-x pb-3 md:hidden">@include('partials.header.search', ['h' => $h])</div>
    @endif
</header>

@include('partials.header.mobile-drawer', ['categories' => $categories, 'menu' => $menu])
