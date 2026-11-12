<section class="container-x" aria-label="{{ $section->title ?: 'Information' }}">
    <div class="{{ $section->get('width', 'wide') === 'narrow' ? 'mx-auto max-w-3xl' : '' }} {{ $section->get('boxed', true) ? 'rounded-card border border-slate-200/80 bg-white p-5 sm:p-8' : '' }}">
        @if($section->title)<h2 class="section-title mb-3">{{ $section->title }}</h2>@endif
        <div class="prose-content">{!! $section->get('content') !!}</div>
    </div>
</section>
