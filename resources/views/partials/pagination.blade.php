@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" class="mt-8 flex items-center justify-center gap-1">
    @if ($paginator->onFirstPage())
        <span class="grid size-10 place-items-center rounded-btn text-slate-300">{{ icon('chevron-left', 'size-4', 2) }}</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-btn border border-slate-200 bg-white text-slate-700 hover:border-primary-500 hover:text-primary-600" aria-label="Previous page">{{ icon('chevron-left', 'size-4', 2) }}</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="px-2 text-slate-400">…</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" class="grid size-10 place-items-center rounded-btn bg-primary-500 text-sm font-bold text-white">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="hidden size-10 place-items-center rounded-btn border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:border-primary-500 hover:text-primary-600 sm:grid">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-btn border border-slate-200 bg-white text-slate-700 hover:border-primary-500 hover:text-primary-600" aria-label="Next page">{{ icon('chevron-right', 'size-4', 2) }}</a>
    @else
        <span class="grid size-10 place-items-center rounded-btn text-slate-300">{{ icon('chevron-right', 'size-4', 2) }}</span>
    @endif
</nav>
<p class="mt-3 text-center text-xs text-slate-500">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
@endif
