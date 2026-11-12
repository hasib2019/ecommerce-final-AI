<div x-data x-cloak x-show="$store.cart.open" class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-label="Shopping cart" @keydown.escape.window="$store.cart.open = false">
    <div x-show="$store.cart.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50" @click="$store.cart.open = false"></div>
    <aside x-show="$store.cart.open"
           x-transition:enter="transition duration-250 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full"
           class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-lg font-bold text-slate-900">Your Cart <span class="text-sm font-medium text-slate-500" x-text="'(' + ($store.cart.data.count || 0) + ')'"></span></h2>
            <button type="button" class="rounded-full p-2 hover:bg-slate-100" @click="$store.cart.open = false" aria-label="Close cart">{{ icon('x', 'size-6') }}</button>
        </div>

        {{-- Free shipping progress: a proven nudge to increase basket size --}}
        <template x-if="$store.cart.data.free_shipping_progress !== null && $store.cart.data.count > 0">
            <div class="border-b border-slate-100 bg-primary-50/60 px-5 py-3">
                <p class="text-sm text-slate-700" x-show="$store.cart.data.free_shipping_left_raw > 0">
                    Add <strong class="text-primary-600" x-text="$store.cart.data.free_shipping_left"></strong> more for <strong>FREE delivery</strong> 🚚
                </p>
                <p class="text-sm font-semibold text-emerald-700" x-show="$store.cart.data.free_shipping_left_raw <= 0">🎉 You've unlocked FREE delivery!</p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                    <div class="h-full rounded-full bg-primary-500 transition-all duration-500" :style="`width: ${$store.cart.data.free_shipping_progress}%`"></div>
                </div>
            </div>
        </template>

        <div class="flex-1 overflow-y-auto px-5">
            <template x-if="$store.cart.loading && !$store.cart.loaded">
                <div class="space-y-4 py-5">
                    <template x-for="i in 3"><div class="flex gap-3"><div class="skeleton size-20"></div><div class="flex-1 space-y-2"><div class="skeleton h-4 w-3/4"></div><div class="skeleton h-4 w-1/3"></div></div></div></template>
                </div>
            </template>

            <template x-if="$store.cart.loaded && !$store.cart.data.items?.length">
                <div class="flex h-full flex-col items-center justify-center py-16 text-center">
                    <span class="grid size-20 place-items-center rounded-full bg-slate-100 text-slate-400">{{ icon('bag', 'size-10') }}</span>
                    <p class="mt-4 font-semibold text-slate-900">Your cart is empty</p>
                    <p class="mt-1 text-sm text-slate-500">Looks like you haven't added anything yet.</p>
                    <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Start Shopping</a>
                </div>
            </template>

            <ul class="divide-y divide-slate-100">
                <template x-for="item in $store.cart.data.items || []" :key="item.key">
                    <li class="flex gap-3 py-4" :class="$store.cart.busy === item.key && 'opacity-50'">
                        <a :href="item.url" class="shrink-0"><img :src="item.image" :alt="item.name" class="size-20 rounded-lg bg-slate-100 object-cover" width="80" height="80" loading="lazy"></a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <a :href="item.url" class="line-clamp-2 text-sm font-medium text-slate-800 hover:text-primary-600" x-text="item.name"></a>
                            <span class="text-xs text-slate-500" x-show="item.variant" x-text="item.variant"></span>
                            <div class="mt-auto flex items-center justify-between pt-2">
                                <div class="flex items-center rounded-full border border-slate-200">
                                    <button type="button" class="grid size-8 place-items-center text-slate-600 hover:text-primary-600" @click="$store.cart.update(item.key, item.quantity - 1)" aria-label="Decrease">{{ icon('minus', 'size-3.5', 2) }}</button>
                                    <span class="w-7 text-center text-sm font-semibold" x-text="item.quantity"></span>
                                    <button type="button" class="grid size-8 place-items-center text-slate-600 hover:text-primary-600 disabled:opacity-30" :disabled="item.quantity >= item.max" @click="$store.cart.update(item.key, item.quantity + 1)" aria-label="Increase">{{ icon('plus', 'size-3.5', 2) }}</button>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-slate-900" x-text="item.total_formatted"></div>
                                    <button type="button" class="text-xs text-slate-400 hover:text-rose-600" @click="$store.cart.remove(item.key)">Remove</button>
                                </div>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        <div x-show="$store.cart.data.items?.length" class="space-y-3 border-t border-slate-100 bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
            <div class="flex items-center justify-between text-sm" x-show="$store.cart.data.discount">
                <span class="text-slate-600">Discount <span class="badge bg-emerald-100 text-emerald-700" x-text="$store.cart.data.coupon"></span></span>
                <span class="font-semibold text-emerald-600" x-text="'−' + $store.cart.data.discount"></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-700">Subtotal</span>
                <span class="text-lg font-extrabold text-slate-900" x-text="$store.cart.data.subtotal"></span>
            </div>
            <p class="text-xs text-slate-500">Shipping & discounts calculated at checkout.</p>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('cart') }}" class="btn btn-outline" data-no-prefetch>View Cart</a>
                <a href="{{ route('checkout') }}" class="btn btn-primary" data-no-prefetch>Checkout {{ icon('arrow-right', 'size-4', 2) }}</a>
            </div>
            <p class="flex items-center justify-center gap-1.5 text-xs text-slate-500">{{ icon('lock', 'size-3.5') }} Secure checkout · Cash on Delivery available</p>
        </div>
    </aside>
</div>
