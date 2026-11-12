@extends('layouts.app')

@section('content')
<div class="container-x max-w-3xl pb-12 pt-8">
    <div class="text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-full bg-primary-50 text-primary-600">{{ icon('truck', 'size-7') }}</span>
        <h1 class="mt-3 text-2xl font-extrabold text-slate-900 sm:text-3xl">Track your order</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your order number and the mobile number used at checkout.</p>
    </div>

    <form method="GET" action="{{ route('track') }}" class="card mt-6 grid gap-3 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <label for="t-order" class="label">Order number</label>
            <input id="t-order" name="order" value="{{ request('order') }}" required placeholder="e.g. ORD100001" class="input uppercase">
        </div>
        <div>
            <label for="t-phone" class="label">Mobile number</label>
            <input id="t-phone" name="phone" type="tel" value="{{ request('phone') }}" required placeholder="01XXXXXXXXX" class="input" inputmode="tel">
        </div>
        <button class="btn btn-primary h-[42px]">Track</button>
    </form>

    @if($searched && ! $order)
        <div class="mt-6 rounded-card border border-amber-200 bg-amber-50 p-5 text-center text-sm text-amber-800">
            We couldn't find an order with those details. Please double-check, or <a href="{{ route('contact') }}" class="font-semibold underline">contact us</a>.
        </div>
    @endif

    @if($order)
        <div class="card mt-6 p-5 sm:p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs text-slate-500">Order</p>
                    <p class="font-mono text-lg font-bold">#{{ $order->order_number }}</p>
                </div>
                <span class="badge px-3 py-1 text-xs {{ $order->status->badgeClass() }}">{{ $order->status->getLabel() }}</span>
            </div>
            @include('partials.order-timeline', ['order' => $order])
            <div class="mt-6 grid gap-3 border-t border-slate-100 pt-5 text-sm sm:grid-cols-3">
                <div><p class="text-slate-500">Placed on</p><p class="font-semibold">{{ $order->created_at->format('M d, Y') }}</p></div>
                <div><p class="text-slate-500">Total</p><p class="font-semibold">{{ money($order->total) }}</p></div>
                <div><p class="text-slate-500">Payment</p><p class="font-semibold">{{ $order->paymentLabel() }} · {{ $order->payment_status->getLabel() }}</p></div>
            </div>
            <ul class="mt-5 divide-y divide-slate-100 border-t border-slate-100">
                @foreach($order->items as $item)
                    <li class="flex items-center gap-3 py-3 text-sm">
                        <img src="{{ img($item->image, 160) }}" alt="" class="size-12 rounded-lg bg-slate-100 object-cover" width="48" height="48" loading="lazy">
                        <span class="flex-1">{{ $item->product_name }} @if($item->variant_name)<span class="text-slate-500">({{ $item->variant_name }})</span>@endif <span class="text-slate-500">× {{ $item->quantity }}</span></span>
                        <span class="font-semibold">{{ money($item->total) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
