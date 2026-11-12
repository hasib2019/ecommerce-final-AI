@extends('layouts.app')

@section('content')
<div class="container-x pb-12 pt-6" x-data x-init="$store.cart.data = @js(cart()->toArray()); $store.cart.loaded = true">
    <h1 class="mb-5 text-2xl font-extrabold text-slate-900 sm:text-3xl">Shopping Cart</h1>

    <template x-if="!$store.cart.data.items?.length">
        <div class="card flex flex-col items-center px-6 py-16 text-center">
            <span class="grid size-20 place-items-center rounded-full bg-slate-100 text-slate-400">{{ icon('bag', 'size-10') }}</span>
            <h2 class="mt-4 text-lg font-bold">Your cart is empty</h2>
            <p class="mt-1 text-sm text-slate-500">Discover great deals and add your favourite products.</p>
            <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Continue Shopping</a>
        </div>
    </template>

    <div x-show="$store.cart.data.items?.length" class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-4">
            <template x-if="$store.cart.data.free_shipping_progress !== null">
                <div class="card p-4">
                    <p class="text-sm text-slate-700" x-show="$store.cart.data.free_shipping_left_raw > 0">
                        {{ icon('truck', 'mr-1 inline size-5 text-primary-500') }} Add <strong class="text-primary-600" x-text="$store.cart.data.free_shipping_left"></strong> more to get <strong>FREE delivery</strong>
                    </p>
                    <p class="text-sm font-semibold text-emerald-700" x-show="$store.cart.data.free_shipping_left_raw <= 0">🎉 Congratulations! Your order qualifies for FREE delivery.</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-primary-500 transition-all duration-500" :style="`width:${$store.cart.data.free_shipping_progress}%`"></div></div>
                </div>
            </template>

            <div class="card divide-y divide-slate-100">
                <template x-for="item in $store.cart.data.items" :key="item.key">
                    <div class="flex gap-3 p-4 sm:gap-4" :class="$store.cart.busy === item.key && 'opacity-50'">
                        <a :href="item.url" class="shrink-0"><img :src="item.image" :alt="item.name" class="size-20 rounded-lg bg-slate-100 object-cover sm:size-24" width="96" height="96"></a>
                        <div class="flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                            <div class="min-w-0 flex-1">
                                <a :href="item.url" class="line-clamp-2 text-sm font-semibold text-slate-800 hover:text-primary-600" x-text="item.name"></a>
                                <p class="text-xs text-slate-500" x-show="item.variant" x-text="item.variant"></p>
                                <p class="mt-1 text-sm"><span class="font-bold text-slate-900" x-text="item.price_formatted"></span> <span class="text-xs text-slate-400 line-through" x-text="item.compare_formatted"></span></p>
                            </div>
                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div class="flex items-center rounded-full border border-slate-200">
                                    <button type="button" class="grid size-9 place-items-center" @click="$store.cart.update(item.key, item.quantity - 1)" aria-label="Decrease">{{ icon('minus', 'size-4', 2) }}</button>
                                    <span class="w-8 text-center text-sm font-semibold" x-text="item.quantity"></span>
                                    <button type="button" class="grid size-9 place-items-center disabled:opacity-30" :disabled="item.quantity >= item.max" @click="$store.cart.update(item.key, item.quantity + 1)" aria-label="Increase">{{ icon('plus', 'size-4', 2) }}</button>
                                </div>
                                <span class="w-24 text-right font-bold text-slate-900" x-text="item.total_formatted"></span>
                                <button type="button" class="rounded-full p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="$store.cart.remove(item.key)" aria-label="Remove">{{ icon('trash', 'size-5') }}</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <a href="{{ route('shop') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:underline">{{ icon('chevron-left', 'size-4', 2) }} Continue shopping</a>
        </div>

        <aside class="lg:sticky lg:top-36 lg:self-start">
            <div class="card space-y-4 p-5" x-data="{ code: '', msg: '', ok: false, async apply() { const r = await fetch('{{ route('cart.coupon') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ code: this.code }) }); const j = await r.json(); this.ok = r.ok; this.msg = j.message || (j.errors && Object.values(j.errors)[0][0]); if (j.cart) this.$store.cart.data = j.cart; }, async removeCoupon() { const r = await fetch('{{ route('cart.coupon.remove') }}', { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } }); const j = await r.json(); this.$store.cart.data = j.cart; this.msg = ''; } }">
                <h2 class="text-lg font-bold text-slate-900">Order Summary</h2>
                @if(setting('checkout.show_coupon'))
                    <div>
                        <template x-if="!$store.cart.data.coupon">
                            <form @submit.prevent="apply()" class="flex gap-2">
                                <label for="coupon" class="sr-only">Coupon code</label>
                                <input id="coupon" x-model="code" placeholder="Coupon code" class="input uppercase" autocomplete="off">
                                <button class="btn btn-dark shrink-0">Apply</button>
                            </form>
                        </template>
                        <template x-if="$store.cart.data.coupon">
                            <div class="flex items-center justify-between rounded-btn border border-dashed border-emerald-400 bg-emerald-50 px-3 py-2 text-sm">
                                <span class="font-semibold text-emerald-700">{{ icon('tag', 'mr-1 inline size-4') }}<span x-text="$store.cart.data.coupon"></span> applied</span>
                                <button type="button" class="text-xs font-semibold text-rose-600 hover:underline" @click="removeCoupon()">Remove</button>
                            </div>
                        </template>
                        <p x-show="msg" x-text="msg" class="mt-1.5 text-xs" :class="ok ? 'text-emerald-600' : 'text-rose-600'"></p>
                    </div>
                @endif
                <dl class="space-y-2 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-600">Subtotal</dt><dd class="font-semibold" x-text="$store.cart.data.subtotal"></dd></div>
                    <div class="flex justify-between" x-show="$store.cart.data.discount"><dt class="text-slate-600">Discount</dt><dd class="font-semibold text-emerald-600" x-text="'−' + $store.cart.data.discount"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Delivery</dt><dd class="text-slate-500">Calculated at checkout</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><dt class="font-bold">Total</dt><dd class="text-xl font-extrabold text-slate-900" x-text="$store.cart.data.total"></dd></div>
                </dl>
                <a href="{{ route('checkout') }}" class="btn btn-primary btn-lg w-full" data-no-prefetch>Proceed to Checkout {{ icon('arrow-right', 'size-5', 2) }}</a>
                <ul class="space-y-1.5 text-xs text-slate-500">
                    <li class="flex items-center gap-2">{{ icon('lock', 'size-4 text-emerald-500') }} Safe & secure checkout</li>
                    <li class="flex items-center gap-2">{{ icon('cash', 'size-4 text-emerald-500') }} Cash on Delivery available</li>
                    <li class="flex items-center gap-2">{{ icon('refresh', 'size-4 text-emerald-500') }} {{ setting('catalog.return_info') }}</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
