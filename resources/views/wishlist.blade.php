@extends('layouts.app')

@section('content')
<div class="container-x pb-12 pt-6">
    <h1 class="mb-5 text-2xl font-extrabold text-slate-900 sm:text-3xl">My Wishlist</h1>
    @if($products->isEmpty())
        <div class="card flex flex-col items-center px-6 py-16 text-center">
            <span class="grid size-20 place-items-center rounded-full bg-rose-50 text-rose-400">{{ icon('heart', 'size-10') }}</span>
            <h2 class="mt-4 text-lg font-bold">Your wishlist is empty</h2>
            <p class="mt-1 text-sm text-slate-500">Tap the ♥ on any product to save it for later.</p>
            <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Discover products</a>
        </div>
    @else
        @guest
            <p class="mb-4 rounded-card bg-primary-50 p-3 text-sm text-primary-800"><a href="{{ route('login') }}" class="font-semibold underline">Sign in</a> to keep your wishlist across devices.</p>
        @endguest
        @include('partials.product-grid', ['products' => $products, 'cols' => 5])
    @endif
</div>
@endsection
