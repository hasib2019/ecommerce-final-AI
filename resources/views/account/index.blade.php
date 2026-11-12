@extends('account.layout')

@section('account')
<h1 class="text-2xl font-extrabold text-slate-900">Hello, {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }} 👋</h1>
<p class="mt-1 text-sm text-slate-500">Here's what's happening with your account.</p>

<div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
    @foreach([['Total orders', $stats['orders'], 'orders'], ['In progress', $stats['pending'], 'truck'], ['Total spent', money($stats['spent']), 'cash'], ['Wishlist', $stats['wishlist'], 'heart']] as [$label, $value, $ic])
        <div class="card p-4">
            <span class="grid size-9 place-items-center rounded-full bg-primary-50 text-primary-600">{{ icon($ic, 'size-5') }}</span>
            <p class="mt-3 text-xl font-extrabold text-slate-900">{{ $value }}</p>
            <p class="text-xs text-slate-500">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="card mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 p-4">
        <h2 class="font-bold text-slate-900">Recent orders</h2>
        <a href="{{ route('account.orders') }}" class="text-sm font-semibold text-primary-600 hover:underline">View all</a>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($recentOrders as $order)
            @include('account.order-row', ['order' => $order])
        @empty
            <p class="p-6 text-center text-sm text-slate-500">You haven't placed any orders yet. <a href="{{ route('shop') }}" class="font-semibold text-primary-600">Start shopping →</a></p>
        @endforelse
    </div>
</div>
@endsection
