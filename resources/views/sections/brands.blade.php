<section class="container-x" aria-label="{{ $section->title ?: 'Brands' }}">
    @include('partials.section-heading', ['link' => '/brands', 'linkText' => 'All brands'])
    <div class="snap-row -mx-3 gap-3 px-3 scroll-px-3 sm:mx-0 sm:scroll-px-0 sm:grid sm:grid-cols-4 sm:overflow-visible sm:px-0 md:grid-cols-6">
        @foreach($section->items as $brand)
            <a href="{{ $brand->url }}" class="grid h-20 w-[30%] shrink-0 snap-start place-items-center rounded-card border border-slate-200/80 bg-white p-4 transition hover:border-primary-300 hover:shadow-md sm:w-auto" title="{{ $brand->name }}">
                <img src="{{ img($brand->logo, 320) }}" alt="{{ $brand->name }}" class="max-h-12 w-auto object-contain {{ $section->get('grayscale', true) ? 'opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0' : '' }}" loading="lazy" width="160" height="48">
            </a>
        @endforeach
    </div>
</section>
