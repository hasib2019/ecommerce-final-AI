@if(setting('mobile.bottom_nav') && ! request()->routeIs('checkout'))
<nav x-data class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden" aria-label="Mobile navigation">
    <div class="grid h-16 grid-cols-5 text-[11px] font-medium text-slate-600">
        <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('home') ? 'text-primary-600' : '' }}">{{ icon('home', 'size-6') }}Home</a>
        <button type="button" @click="$store.ui.menu = true" class="flex flex-col items-center justify-center gap-1">{{ icon('grid', 'size-6') }}Categories</button>
        <button type="button" @click="$store.cart.show()" class="relative flex flex-col items-center justify-center gap-1">
            <span class="relative -mt-6 grid size-12 place-items-center rounded-full bg-primary-500 text-white shadow-lg shadow-primary-500/40 ring-4 ring-white">
                {{ icon('bag', 'size-6') }}
                <span x-show="$store.cart.data.count" x-text="$store.cart.data.count" class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-slate-900 px-1 text-[10px] font-bold leading-5 text-white"></span>
            </span>
            Cart
        </button>
        <a href="{{ route('wishlist') }}" class="relative flex flex-col items-center justify-center gap-1 {{ request()->routeIs('wishlist') ? 'text-primary-600' : '' }}">
            {{ icon('heart', 'size-6') }}Wishlist
            <span x-cloak x-show="$store.wishlist.ids.length" x-text="$store.wishlist.ids.length" class="absolute right-[22%] top-1.5 grid min-w-4 place-items-center rounded-full bg-primary-500 px-1 text-[9px] font-bold leading-4 text-white"></span>
        </a>
        <a href="{{ auth()->check() ? route('account.index') : route('login') }}" class="flex flex-col items-center justify-center gap-1 {{ request()->routeIs('account.*', 'login') ? 'text-primary-600' : '' }}">{{ icon('user', 'size-6') }}Account</a>
    </div>
</nav>
@endif
