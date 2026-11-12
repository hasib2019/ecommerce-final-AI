@extends('layouts.app')

@section('content')
@include('partials.breadcrumbs')
<article class="container-x max-w-4xl pb-12 pt-4">
    <h1 class="mb-6 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $page->title }}</h1>
    <div class="card prose-content p-5 sm:p-8">{!! $page->content !!}</div>
</article>
@endsection
