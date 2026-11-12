@extends('layouts.app')

@section('content')
@php($links = [['account.index', 'home', 'Dashboard'], ['account.orders', 'orders', 'My Orders'], ['wishlist', 'heart', 'Wishlist'], ['account.profile', 'user', 'Profile & Security']])
<div class="container-x pb-12 pt-6">
    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        <aside>
            <div class="card p-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <span class="grid size-11 place-items-center rounded-full bg-primary-500 text-lg font-bold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()->phone ?? auth()->user()->email }}</p></div>
                </div>
                <nav class="no-scrollbar -mx-1 mt-3 flex gap-1 overflow-x-auto lg:flex-col" aria-label="Account">
                    @foreach($links as [$route, $ic, $label])
                        <a href="{{ route($route) }}" class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($route) || ($route === 'account.orders' && request()->routeIs('account.order')) ? 'bg-primary-50 text-primary-700' : 'text-slate-700 hover:bg-slate-50' }}">{{ icon($ic, 'size-5') }} {{ $label }}</a>
                    @endforeach
                    @if(auth()->user()->is_admin)
                        <a href="{{ url('/admin') }}" class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ icon('grid', 'size-5') }} Admin panel</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">@csrf<button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">{{ icon('logout', 'size-5') }} Sign out</button></form>
                </nav>
            </div>
        </aside>
        <div class="min-w-0">@yield('account')</div>
    </div>
</div>
@endsection
