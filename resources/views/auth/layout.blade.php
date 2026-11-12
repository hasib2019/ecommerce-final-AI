@extends('layouts.app')

@section('content')
<div class="container-x flex justify-center py-10 sm:py-16">
    <div class="w-full max-w-md">
        <div class="card p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <h1 class="text-2xl font-extrabold text-slate-900">@yield('title')</h1>
            <p class="mt-1 text-sm text-slate-500">@yield('subtitle')</p>
            <div class="mt-6">@yield('form')</div>
        </div>
        @yield('below')
    </div>
</div>
@endsection
