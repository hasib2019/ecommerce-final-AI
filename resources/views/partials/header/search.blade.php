<form action="{{ route('search') }}" method="GET" role="search" class="relative" x-data="searchBox" @click.outside="open = false" @keydown.escape="open = false">
    <label for="q-{{ $id = uniqid() }}" class="sr-only">Search products</label>
    <input id="q-{{ $id }}" type="search" name="q" x-model="q" @input="input" @focus="q.length > 1 && (open = true)"
           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()"
           value="{{ request('q') }}" x-init="q = $el.value" autocomplete="off" enterkeyhint="search"
           placeholder="{{ $h['search_placeholder'] ?? 'Search products…' }}"
           class="h-11 w-full rounded-full border border-slate-200 bg-slate-100/80 pl-4 pr-12 text-sm text-slate-900 placeholder:text-slate-500 transition focus:border-primary-500 focus:bg-white focus:ring-4 focus:ring-primary-500/15 focus:outline-none">
    <button type="submit" class="absolute right-1 top-1 grid size-9 place-items-center rounded-full bg-primary-500 text-white hover:bg-primary-600" aria-label="Search">
        <span x-show="!loading">{{ icon('search', 'size-4.5', 2) }}</span>
        <span x-cloak x-show="loading" class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
    </button>

    <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-card border border-slate-200 bg-white shadow-2xl">
        <template x-if="results.categories?.length">
            <div class="flex flex-wrap gap-2 border-b border-slate-100 p-3">
                <template x-for="c in results.categories" :key="c.url">
                    <a :href="c.url" class="badge bg-primary-50 px-3 py-1 text-xs text-primary-700 hover:bg-primary-100" x-text="c.name"></a>
                </template>
            </div>
        </template>
        <template x-for="(p, i) in results.products" :key="p.url">
            <a :href="p.url" class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50" :class="active === i && 'bg-slate-50'">
                <img :src="p.image" alt="" class="size-12 shrink-0 rounded-lg bg-slate-100 object-cover" width="48" height="48" loading="lazy">
                <span class="min-w-0 flex-1">
                    <span class="line-clamp-1 text-sm font-medium text-slate-800" x-text="p.name"></span>
                    <span class="text-sm font-bold text-primary-600" x-text="p.price"></span>
                    <span class="ml-1 text-xs text-slate-400 line-through" x-text="p.compare"></span>
                </span>
            </a>
        </template>
        <template x-if="!results.products?.length">
            <p class="p-4 text-center text-sm text-slate-500">No products found. Try a different keyword.</p>
        </template>
        <a :href="results.all_url" x-show="results.products?.length" class="block border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-center text-sm font-semibold text-primary-600 hover:bg-slate-100">See all results →</a>
    </div>
</form>
