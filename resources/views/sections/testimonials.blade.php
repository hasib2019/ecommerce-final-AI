@php($items = (array) $section->get('items', []))
@if($items)
<section class="container-x" aria-label="{{ $section->title ?: 'Testimonials' }}">
    @include('partials.section-heading')
    <div class="snap-row -mx-3 gap-3 px-3 scroll-px-3 sm:mx-0 sm:scroll-px-0 sm:grid sm:grid-cols-2 sm:gap-4 sm:overflow-visible sm:px-0 lg:grid-cols-3">
        @foreach($items as $t)
            <figure class="flex w-[85%] shrink-0 snap-start flex-col rounded-card border border-slate-200/80 bg-white p-5 sm:w-auto">
                @include('partials.stars', ['rating' => (int) ($t['rating'] ?? 5), 'size' => 'text-base'])
                <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-slate-700">“{{ $t['text'] ?? '' }}”</blockquote>
                <figcaption class="mt-4 flex items-center gap-3">
                    @if(filled($t['avatar'] ?? null))
                        <img src="{{ img($t['avatar'], 160) }}" alt="" class="size-10 rounded-full object-cover" loading="lazy" width="40" height="40">
                    @else
                        <span class="grid size-10 place-items-center rounded-full bg-primary-100 font-bold text-primary-700">{{ mb_substr($t['name'] ?? '?', 0, 1) }}</span>
                    @endif
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">{{ $t['name'] ?? '' }}</span>
                        <span class="text-xs text-slate-500">{{ $t['location'] ?? '' }}</span>
                    </span>
                </figcaption>
            </figure>
        @endforeach
    </div>
</section>
@endif
