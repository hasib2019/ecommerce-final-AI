@extends('layouts.app')

@section('content')
@include('partials.breadcrumbs')
<div class="container-x pb-12 pt-4">
    <h1 class="mb-6 text-2xl font-extrabold text-slate-900 sm:text-3xl">All Brands</h1>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
        @forelse($brands as $brand)
            <a href="{{ $brand->url }}" class="card flex flex-col items-center gap-3 p-5 text-center transition hover:border-primary-300 hover:shadow-md">
                <span class="grid h-14 place-items-center">
                    @if($brand->logo)
                        <img src="{{ img($brand->logo, 320) }}" alt="{{ $brand->name }}" class="max-h-14 w-auto object-contain" loading="lazy" width="160" height="56">
                    @else
                        <span class="text-xl font-black text-slate-700">{{ $brand->name }}</span>
                    @endif
                </span>
                <span class="text-sm font-semibold text-slate-800">{{ $brand->name }}</span>
                <span class="text-xs text-slate-500">{{ $brand->products_count }} products</span>
            </a>
        @empty
            <p class="col-span-full text-slate-500">No brands yet.</p>
        @endforelse
    </div>
</div>
@endsection
