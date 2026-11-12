@extends('layouts.app')

@section('content')
@include('partials.breadcrumbs')
<div class="container-x pb-12 pt-4">
    <h1 class="mb-6 text-3xl font-extrabold tracking-tight text-slate-900">Blog</h1>
    @if($posts->isEmpty())
        <p class="text-slate-500">No articles yet.</p>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                @include('blog.card', ['post' => $post, 'heading' => 'h2', 'eager' => $loop->index < 3])
            @endforeach
        </div>
        {{ $posts->links() }}
    @endif
</div>
@endsection
