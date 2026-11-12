@extends('layouts.app')

@php
    $selectedShipping = old('shipping_method_id', $totals['shipping_method']?->id ?? $shippingMethods->first()?->id);
    $selectedPayment = old('payment_method', array_key_first($paymentMethods));
    $initial = [
        'payment' => $selectedPayment,
        'shipping' => (int) $selectedShipping,
        'totalRaw' => $totals['total'],
        'totals' => [
            'subtotal' => money($totals['subtotal']),
            'discount' => $totals['discount'] > 0 ? money($totals['discount']) : null,
            'shipping' => $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free',
            'total' => money($totals['total']),
            'coupon' => $totals['coupon'],
        ],
    ];
    $field = fn ($name) => $errors->has($name) ? 'input input-error' : 'input';
@endphp

@section('content')
<div class="container-x pb-28 pt-6 md:pb-12" x-data="checkout(@js($initial))">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">Checkout</h1>
        <span class="flex items-center gap-1.5 text-xs font-medium text-emerald-600">{{ icon('lock', 'size-4') }} Secure checkout</span>
    </div>

    @if($errors->has('cart'))
        <div class="mb-4 rounded-card border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first('cart') }} <a href="{{ route('cart') }}" class="font-semibold underline">Update cart</a></div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" x-ref="form" @submit="submit($event)" class="grid gap-6 lg:grid-cols-[1fr_24rem]" novalidate>
        @csrf
        <div class="space-y-5">
            {{-- Delivery details --}}
            <section class="card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><span class="grid size-7 place-items-center rounded-full bg-primary-500 text-sm text-white">1</span> Delivery details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Full name <span class="text-rose-500">*</span></label>
                        <input id="name" name="name" value="{{ $prefill['name'] }}" required autocomplete="name" class="{{ $field('name') }}" placeholder="Your full name" @blur="capture()">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="label">Mobile number <span class="text-rose-500">*</span></label>
                        <input id="phone" name="phone" type="tel" value="{{ $prefill['phone'] }}" required autocomplete="tel" inputmode="tel" class="{{ $field('phone') }}" placeholder="01XXXXXXXXX" @blur="capture()" @input.debounce.800ms="capture()">
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="label">Full address <span class="text-rose-500">*</span></label>
                        <textarea id="address" name="address" rows="2" required autocomplete="street-address" class="{{ $field('address') }}" placeholder="House, road, area, thana" @blur="capture()">{{ $prefill['address'] }}</textarea>
                        @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="city" class="label">District / City</label>
                        <select id="city" name="city" class="{{ $field('city') }}">
                            @foreach(config('shop.districts') as $d)
                                <option value="{{ $d }}" @selected($prefill['city'] === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="email" class="label">Email {{ setting('checkout.require_email') ? '' : '(optional)' }}</label>
                        <input id="email" name="email" type="email" value="{{ $prefill['email'] }}" autocomplete="email" class="{{ $field('email') }}" placeholder="For order updates" @blur="capture()">
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @if(setting('checkout.show_order_note'))
                        <div class="sm:col-span-2" x-data="{ open: {{ old('note') ? 'true' : 'false' }} }">
                            <button type="button" x-show="!open" @click="open = true" class="text-sm font-semibold text-primary-600 hover:underline">+ Add a note to your order</button>
                            <div x-cloak x-show="open">
                                <label for="note" class="label">Order note</label>
                                <textarea id="note" name="note" rows="2" class="input" placeholder="Delivery instructions, preferred time…">{{ old('note') }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Shipping --}}
            <section class="card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><span class="grid size-7 place-items-center rounded-full bg-primary-500 text-sm text-white">2</span> Delivery area</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($shippingMethods as $method)
                        <label class="flex cursor-pointer items-start gap-3 rounded-card border-2 p-4 transition" :class="shipping === {{ $method->id }} ? 'border-primary-500 bg-primary-50/50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}" x-model.number="shipping" @change="refresh()" class="mt-0.5 size-4 accent-[var(--brand)]">
                            <span class="flex-1">
                                <span class="flex items-center justify-between gap-2 text-sm font-semibold text-slate-900">{{ $method->name }} <span>{{ (float) $method->cost > 0 ? money($method->cost) : 'Free' }}</span></span>
                                @if($method->description)<span class="mt-0.5 block text-xs text-slate-500">{{ $method->description }}</span>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @if($totals['free_shipping_left'] !== null)
                    @if($totals['free_shipping_left'] > 0)
                        <p class="mt-3 text-xs text-slate-600">{{ icon('truck', 'mr-1 inline size-4 text-primary-500') }} Add {{ money($totals['free_shipping_left']) }} more to your cart for free delivery.</p>
                    @else
                        <p class="mt-3 text-xs font-semibold text-emerald-600">🎉 Your order qualifies for free delivery!</p>
                    @endif
                @endif
            </section>

            {{-- Payment --}}
            <section class="card p-5 sm:p-6">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><span class="grid size-7 place-items-center rounded-full bg-primary-500 text-sm text-white">3</span> Payment method</h2>
                <div class="space-y-3">
                    @foreach($paymentMethods as $key => $pm)
                        <div class="rounded-card border-2 transition" :class="payment === '{{ $key }}' ? 'border-primary-500 bg-primary-50/40' : 'border-slate-200'">
                            <label class="flex cursor-pointer items-center gap-3 p-4">
                                <input type="radio" name="payment_method" value="{{ $key }}" x-model="payment" class="size-4 accent-[var(--brand)]">
                                <span class="flex-1 text-sm font-semibold text-slate-900">{{ $pm['label'] }}</span>
                                @if($key === 'cod')<span class="badge bg-emerald-100 text-emerald-700">Most popular</span>@endif
                                @if($key === 'bkash')<span class="rounded bg-[#e2136e] px-2 py-0.5 text-[10px] font-bold text-white">bKash</span>@endif
                                @if($key === 'nagad')<span class="rounded bg-[#f6921e] px-2 py-0.5 text-[10px] font-bold text-slate-900">Nagad</span>@endif
                            </label>
                            <div x-cloak x-show="payment === '{{ $key }}'" x-collapse>
                                <div class="space-y-3 px-4 pb-4 text-sm text-slate-600">
                                    @if($pm['note'])<p>{!! nl2br(e($pm['note'])) !!}</p>@endif
                                    @if($pm['number'])
                                        <p class="flex flex-wrap items-center gap-2">
                                            <span>{{ $pm['label'] }} {{ $pm['type'] ?? '' }} number:</span>
                                            <span x-data="{ c: false }" @click="navigator.clipboard?.writeText(@js($pm['number'])); c = true" class="cursor-pointer rounded-btn bg-white px-2.5 py-1 font-mono font-bold text-slate-900 ring-1 ring-slate-200">{{ $pm['number'] }} <span class="text-xs font-sans text-primary-600" x-text="c ? '✓ copied' : 'copy'"></span></span>
                                        </p>
                                        <p class="font-semibold text-slate-800">Amount to send: <span x-text="totals.total"></span></p>
                                    @endif
                                    @if($pm['manual'])
                                        <div>
                                            <label for="trx-{{ $key }}" class="label">Transaction ID {{ $key !== 'bank' ? '*' : '(optional)' }}</label>
                                            <input id="trx-{{ $key }}" name="transaction_id" :disabled="payment !== '{{ $key }}'" value="{{ old('payment_method') === $key ? old('transaction_id') : '' }}" class="{{ $field('transaction_id') }} font-mono uppercase" placeholder="e.g. 8N7A6B5C4D">
                                            @error('transaction_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Summary --}}
        <aside class="lg:sticky lg:top-36 lg:self-start">
            <div class="card p-5">
                <h2 class="mb-4 text-lg font-bold text-slate-900">Your order <span class="text-sm font-medium text-slate-500">({{ $lines->sum('quantity') }} items)</span></h2>
                <ul class="max-h-72 space-y-3 overflow-y-auto pr-1">
                    @foreach($lines as $line)
                        <li class="flex gap-3">
                            <span class="relative shrink-0">
                                <img src="{{ img($line->image(), 160) }}" alt="" class="size-14 rounded-lg bg-slate-100 object-cover" width="56" height="56" loading="lazy">
                                <span class="absolute -right-1.5 -top-1.5 grid size-5 place-items-center rounded-full bg-slate-700 text-[10px] font-bold text-white">{{ $line->quantity }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="line-clamp-2 text-sm font-medium text-slate-800">{{ $line->product->name }}</span>
                                @if($line->variant)<span class="text-xs text-slate-500">{{ $line->variant->name }}</span>@endif
                            </span>
                            <span class="text-sm font-semibold text-slate-900">{{ money($line->total()) }}</span>
                        </li>
                    @endforeach
                </ul>

                @if(setting('checkout.show_coupon'))
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <template x-if="!totals.coupon">
                            <div>
                                <button type="button" x-show="!couponOpen" @click="couponOpen = true" class="flex items-center gap-1.5 text-sm font-semibold text-primary-600">{{ icon('tag', 'size-4') }} Have a coupon code?</button>
                                <div x-show="couponOpen" class="flex gap-2">
                                    <label for="coupon-code" class="sr-only">Coupon code</label>
                                    <input id="coupon-code" x-model="couponCode" @keydown.enter.prevent="applyCoupon()" placeholder="Enter code" class="input uppercase" autocomplete="off">
                                    <button type="button" @click="applyCoupon()" class="btn btn-dark shrink-0">Apply</button>
                                </div>
                                <p x-show="couponMsg" x-text="couponMsg" class="mt-1.5 text-xs text-rose-600"></p>
                            </div>
                        </template>
                        <template x-if="totals.coupon">
                            <div class="flex items-center justify-between rounded-btn border border-dashed border-emerald-400 bg-emerald-50 px-3 py-2 text-sm">
                                <span class="font-semibold text-emerald-700">{{ icon('tag', 'mr-1 inline size-4') }}<span x-text="totals.coupon"></span></span>
                                <button type="button" @click="removeCoupon()" class="text-xs font-semibold text-rose-600">Remove</button>
                            </div>
                        </template>
                    </div>
                @endif

                <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-600">Subtotal</dt><dd class="font-semibold" x-text="totals.subtotal"></dd></div>
                    <div class="flex justify-between" x-show="totals.discount"><dt class="text-slate-600">Discount</dt><dd class="font-semibold text-emerald-600" x-text="'−' + totals.discount"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Delivery charge</dt><dd class="font-semibold" x-text="totals.shipping"></dd></div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3"><dt class="text-base font-bold">Total</dt><dd class="text-2xl font-extrabold text-primary-600" x-text="totals.total"></dd></div>
                </dl>

                <button type="submit" class="btn btn-primary btn-lg mt-5 hidden w-full text-base lg:flex" :disabled="submitting">
                    <span x-show="!submitting" class="flex items-center gap-2">{{ icon('check-circle', 'size-5') }} Confirm Order</span>
                    <span x-cloak x-show="submitting" class="flex items-center gap-2"><span class="size-5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span> Placing order…</span>
                </button>
                @if($t = setting('checkout.terms_text'))<p class="mt-3 text-center text-xs text-slate-500">{{ $t }}</p>@endif
                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center text-[11px] font-medium text-slate-600">
                    <span class="flex flex-col items-center gap-1">{{ icon('shield', 'size-5 text-primary-500') }}Genuine</span>
                    <span class="flex flex-col items-center gap-1">{{ icon('truck', 'size-5 text-primary-500') }}Fast delivery</span>
                    <span class="flex flex-col items-center gap-1">{{ icon('refresh', 'size-5 text-primary-500') }}Easy return</span>
                </div>
            </div>
        </aside>

        {{-- Mobile sticky submit --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.25)] lg:hidden">
            <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="submitting">
                <span x-show="!submitting">Confirm Order · <span x-text="totals.total"></span></span>
                <span x-cloak x-show="submitting">Placing order…</span>
            </button>
        </div>
    </form>
</div>
@endsection
