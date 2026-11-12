@extends('layouts.app')

@section('content')
<div class="container-x max-w-3xl pb-12 pt-8">
    <div class="card overflow-hidden">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-6 py-10 text-center text-white">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-white/20 animate-pop">{{ icon('check', 'size-9', 2.5) }}</span>
            <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">Thank you, {{ \Illuminate\Support\Str::of($order->customer_name)->explode(' ')->first() }}!</h1>
            <p class="mx-auto mt-2 max-w-md text-white/90">{{ setting('checkout.success_message') }}</p>
            <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 font-mono text-sm font-bold">Order #{{ $order->order_number }}</p>
        </div>

        <div class="grid gap-6 p-5 sm:grid-cols-2 sm:p-8">
            <div>
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Delivery to</h2>
                <p class="font-semibold text-slate-900">{{ $order->customer_name }}</p>
                <p class="text-sm text-slate-600">{{ $order->customer_phone }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $order->shipping_address }}{{ $order->city ? ', '.$order->city : '' }}</p>
            </div>
            <div>
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Payment</h2>
                <p class="font-semibold text-slate-900">{{ $order->paymentLabel() }}</p>
                @if($order->transaction_id)<p class="text-sm text-slate-600">TrxID: <span class="font-mono">{{ $order->transaction_id }}</span></p>@endif
                <p class="mt-1 text-sm text-slate-600">{{ $order->shipping_method }}</p>
            </div>
        </div>

        <div class="border-t border-slate-100 p-5 sm:p-8">
            <ul class="divide-y divide-slate-100">
                @foreach($order->items as $item)
                    <li class="flex items-center gap-3 py-3">
                        <img src="{{ img($item->image, 160) }}" alt="" class="size-14 rounded-lg bg-slate-100 object-cover" width="56" height="56">
                        <span class="flex-1 text-sm"><span class="font-medium text-slate-800">{{ $item->product_name }}</span>@if($item->variant_name)<span class="block text-xs text-slate-500">{{ $item->variant_name }}</span>@endif<span class="block text-xs text-slate-500">{{ $item->quantity }} × {{ money($item->price) }}</span></span>
                        <span class="text-sm font-semibold">{{ money($item->total) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-slate-600">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
                @if($order->discount > 0)<div class="flex justify-between"><dt class="text-slate-600">Discount ({{ $order->coupon_code }})</dt><dd class="text-emerald-600">−{{ money($order->discount) }}</dd></div>@endif
                <div class="flex justify-between"><dt class="text-slate-600">Delivery</dt><dd>{{ $order->shipping_cost > 0 ? money($order->shipping_cost) : 'Free' }}</dd></div>
                <div class="flex justify-between pt-2 text-lg font-extrabold"><dt>Total</dt><dd class="text-primary-600">{{ money($order->total) }}</dd></div>
            </dl>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 p-5 sm:flex-row sm:p-8">
            <a href="{{ route('track', ['order' => $order->order_number, 'phone' => $order->customer_phone]) }}" class="btn btn-dark flex-1">{{ icon('truck', 'size-5') }} Track order</a>
            <a href="{{ route('shop') }}" class="btn btn-primary flex-1">Continue shopping</a>
        </div>
    </div>
</div>

@if($firstView)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => window.shopTrack && window.shopTrack('purchase', @js([
        'transaction_id' => $order->order_number,
        'value' => (float) $order->total,
        'shipping' => (float) $order->shipping_cost,
        'currency' => setting('general.currency_code', 'BDT'),
        'coupon' => $order->coupon_code,
        'items' => $order->items->map(fn ($i) => ['item_id' => (string) $i->product_id, 'item_name' => $i->product_name, 'item_variant' => $i->variant_name, 'price' => (float) $i->price, 'quantity' => $i->quantity])->values(),
    ])));
</script>
@endpush
@endif
@endsection
