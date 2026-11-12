@php($style = $section->get('style', 'circle'))
<section class="container-x" aria-label="{{ $section->title ?: 'Categories' }}">
    @include('partials.section-heading', ['link' => $section->get('view_all_url'), 'linkText' => 'All categories'])
    @if($style === 'card')
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-{{ max(3, min(6, (int) $section->get('columns', 6))) }}">
            @foreach($section->items as $cat)
                <a href="{{ $cat->url }}" class="group relative block aspect-[4/5] overflow-hidden rounded-card bg-slate-200">
                    @if($cat->image)<img src="{{ img($cat->image, 480) }}" alt="{{ $cat->name }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" width="480" height="600">@endif
                    <span class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/10 to-transparent"></span>
                    <span class="absolute inset-x-0 bottom-0 p-4">
                        <span class="block text-base font-bold text-white">{{ $cat->name }}</span>
                        <span class="text-xs text-white/80">{{ $cat->products_count }} products</span>
                    </span>
                </a>
            @endforeach
        </div>
    @elseif($style === 'tile')
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 sm:gap-3 md:grid-cols-6 lg:grid-cols-8">
            @foreach($section->items as $cat)
                <a href="{{ $cat->url }}" class="group flex flex-col items-center gap-2 rounded-card border border-slate-200/80 bg-white p-3 text-center transition hover:border-primary-300 hover:shadow-md">
                    <span class="aspect-square w-full overflow-hidden rounded-lg bg-slate-50">
                        @if($cat->image)<img src="{{ img($cat->image, 320) }}" alt="" class="size-full object-cover transition group-hover:scale-110" loading="lazy" width="160" height="160">@endif
                    </span>
                    <span class="line-clamp-2 text-xs font-semibold text-slate-800 group-hover:text-primary-600">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    @else
        <div class="snap-row -mx-3 gap-3 px-3 scroll-px-3 sm:mx-0 sm:scroll-px-0 sm:grid sm:grid-cols-4 sm:gap-5 sm:overflow-visible sm:px-0 md:grid-cols-6 lg:grid-cols-8">
            @foreach($section->items as $cat)
                <a href="{{ $cat->url }}" class="group flex w-[22%] shrink-0 snap-start flex-col items-center gap-2 text-center sm:w-auto">
                    <span class="aspect-square w-full overflow-hidden rounded-full border-2 border-white bg-slate-100 shadow-sm ring-1 ring-slate-200 transition group-hover:ring-2 group-hover:ring-primary-500">
                        @if($cat->image)<img src="{{ img($cat->image, 320) }}" alt="" class="size-full object-cover transition duration-300 group-hover:scale-110" loading="lazy" width="160" height="160">@endif
                    </span>
                    <span class="line-clamp-2 text-xs font-semibold text-slate-800 group-hover:text-primary-600 sm:text-sm">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>
