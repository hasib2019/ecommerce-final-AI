@php($items = (array) $section->get('items', []))
@if($items)
<section class="container-x" aria-label="{{ $section->title ?: 'Our promise' }}">
    @include('partials.section-heading')
    <div class="grid grid-cols-2 gap-2 rounded-card border border-slate-200/80 bg-white p-3 sm:gap-4 sm:p-4 lg:grid-cols-{{ min(count($items), 4) }}">
        @foreach($items as $item)
            <div class="flex items-center gap-3 rounded-lg p-2 sm:p-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary-50 text-primary-600 sm:size-12">{{ icon($item['icon'] ?? 'check-circle', 'size-5 sm:size-6') }}</span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900">{{ $item['title'] ?? '' }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $item['text'] ?? '' }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
