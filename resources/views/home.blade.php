@extends('layouts.app')

@section('content')
    <h1 class="sr-only">{{ setting('general.site_name') }} – {{ setting('seo.meta_title') }}</h1>

    <div class="space-y-8 pb-10 sm:space-y-12 sm:pb-14">
        @forelse($sections as $section)
            @php($bg = $section->background)
            <div class="{{ $section->visibilityClass() }} {{ $bg ? 'py-8 sm:py-12' : '' }}" @if($bg) style="background: {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $bg) ? $bg : 'transparent' }}" @endif>
                @includeIf('sections.'.$section->type, ['section' => $section])
            </div>
        @empty
            <section class="container-x py-20 text-center">
                <h2 class="text-2xl font-bold">Welcome to {{ setting('general.site_name') }}</h2>
                <p class="mt-2 text-slate-500">Add home page sections from the admin panel → Content → Home Page.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Browse products</a>
            </section>
        @endforelse
    </div>
@endsection
