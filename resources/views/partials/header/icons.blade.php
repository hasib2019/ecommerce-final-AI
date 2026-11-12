@php($h = \App\Support\Settings::group('header'))
<div class="ml-auto flex items-center gap-0.5 sm:gap-1 {{ isset($center) ? '' : '' }} justify-self-end">
    @if(! empty($h['show_account']))
        <a href="{{ auth()->check() ? route('account.index') : route('login') }}" class="hidden items-center gap-2 rounded-full p-2 text-slate-700 hover:bg-slate-100 sm:flex lg:pr-3"><span class="sr-only lg:hidden">Account</span>
            {{ icon('user', 'size-6') }}
            <span class="hidden text-left text-xs leading-tight lg:block">
                <span class="block text-slate-500">{{ auth()->check() ? 'Hello, '.\Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() : 'Sign in' }}</span>
                <span class="font-semibold text-slate-900">{{ auth()->check() ? 'My Account' : 'Account' }}</span>
            </span>
        </a>
    @endif
    @if(! empty($h['show_wishlist']))
        <a href="{{ route('wishlist') }}" class="relative hidden rounded-full p-2 text-slate-700 hover:bg-slate-100 sm:block" aria-label="Wishlist">
            {{ icon('heart', 'size-6') }}
            <span x-data x-cloak x-show="$store.wishlist.ids.length" x-text="$store.wishlist.ids.length" class="absolute -right-0.5 -top-0.5 grid min-w-5 place-items-center rounded-full bg-primary-500 px-1 text-[10px] font-bold leading-5 text-white"></span>
        </a>
    @endif
    <button type="button" x-data @click="$store.cart.show()" class="relative flex items-center gap-2 rounded-full p-2 text-slate-700 hover:bg-slate-100 lg:pr-3"><span class="sr-only lg:hidden">Cart</span>
        <span class="relative">
            {{ icon('bag', 'size-6') }}
            <span x-text="$store.cart.data.count" :class="$store.cart.data.count ? 'scale-100' : 'scale-0'" class="absolute -right-2 -top-1.5 grid min-w-5 place-items-center rounded-full bg-primary-500 px-1 text-[10px] font-bold leading-5 text-white transition-transform">{{ cart()->count() }}</span>
        </span>
        <span class="hidden text-left text-xs leading-tight lg:block">
            <span class="block text-slate-500">My Cart</span>
            <span class="font-semibold text-slate-900" x-text="$store.cart.data.subtotal || '{{ money(0) }}'">&nbsp;</span>
        </span>
    </button>
</div>
