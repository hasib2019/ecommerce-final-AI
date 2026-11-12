@extends('account.layout')

@section('account')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('account.orders') }}" class="text-sm text-slate-500 hover:text-primary-600">← All orders</a>
        <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Order #{{ $order->order_number }}</h1>
        <p class="text-sm text-slate-500">Placed {{ $order->created_at->format('M d, Y \a\t h:i A') }}</p>
    </div>
    <span class="badge px-3 py-1 text-sm {{ $order->status->badgeClass() }}">{{ $order->status->getLabel() }}</span>
</div>
<div class="card p-5">@include('partials.order-timeline', ['order' => $order])</div>
<div class="mt-5 grid gap-5 md:grid-cols-[1fr_18rem]">
    <div class="card divide-y divide-slate-100">
        @foreach($order->items as $item)
            <div class="flex items-center gap-3 p-4">
                <img src="{{ img($item->image, 160) }}" alt="" class="size-16 rounded-lg bg-slate-100 object-cover" width="64" height="64" loading="lazy">
                <div class="flex-1 text-sm">
                    <p class="font-medium text-slate-800">{{ $item->product_name }}</p>
                    @if($item->variant_name)<p class="text-xs text-slate-500">{{ $item->variant_name }}</p>@endif
                    <p class="text-xs text-slate-500">{{ $item->quantity }} × {{ money($item->price) }}</p>
                </div>
                <span class="font-semibold">{{ money($item->total) }}</span>
            </div>
        @endforeach
    </div>
    <div class="space-y-4">
        <div class="card space-y-1.5 p-4 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
            @if($order->discount > 0)<div class="flex justify-between"><span class="text-slate-500">Discount</span><span class="text-emerald-600">−{{ money($order->discount) }}</span></div>@endif
            <div class="flex justify-between"><span class="text-slate-500">Delivery</span><span>{{ money($order->shipping_cost) }}</span></div>
            <div class="flex justify-between border-t border-slate-100 pt-2 font-bold"><span>Total</span><span>{{ money($order->total) }}</span></div>
        </div>
        <div class="card p-4 text-sm">
            <p class="font-semibold text-slate-900">Delivery address</p>
            <p class="mt-1 text-slate-600">{{ $order->customer_name }} · {{ $order->customer_phone }}<br>{{ $order->shipping_address }}{{ $order->city ? ', '.$order->city : '' }}</p>
            <p class="mt-3 font-semibold text-slate-900">Payment</p>
            <p class="mt-1 text-slate-600">{{ $order->paymentLabel() }} · {{ $order->payment_status->getLabel() }}</p>
        </div>
    </div>
</div>
@endsection
