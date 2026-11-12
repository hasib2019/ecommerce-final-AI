@extends('layouts.app')

@section('content')
@php(seo()->title('Page not found')->noindex())
<div class="container-x flex flex-col items-center py-20 text-center">
    <p class="text-7xl font-black text-primary-500">404</p>
    <h1 class="mt-4 text-2xl font-bold text-slate-900">Oops! We can't find that page.</h1>
    <p class="mt-2 max-w-md text-slate-500">The product may have sold out or moved. Try searching instead:</p>
    <form action="{{ route('search') }}" class="mt-6 flex w-full max-w-md gap-2">
        <label for="nf-q" class="sr-only">Search</label>
        <input id="nf-q" name="q" class="input" placeholder="Search products…">
        <button class="btn btn-primary">Search</button>
    </form>
    <a href="{{ route('home') }}" class="mt-6 text-sm font-semibold text-primary-600 hover:underline">← Back to home</a>
</div>
@endsection
