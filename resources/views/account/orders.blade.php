@extends('account.layout')

@section('account')
<h1 class="mb-5 text-2xl font-extrabold text-slate-900">My orders</h1>
<div class="card divide-y divide-slate-100">
    @forelse($orders as $order)
        @include('account.order-row', ['order' => $order])
    @empty
        <p class="p-8 text-center text-sm text-slate-500">No orders yet.</p>
    @endforelse
</div>
{{ $orders->links() }}
@endsection
