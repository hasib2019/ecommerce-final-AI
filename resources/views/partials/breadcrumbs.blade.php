@php($crumbs = seo()->getBreadcrumbs())
@if(count($crumbs) > 1)
<nav aria-label="Breadcrumb" class="{{ $class ?? 'container-x pt-4' }}">
    <ol class="flex flex-wrap items-center gap-1 text-xs text-slate-500">
        @foreach($crumbs as $crumb)
            <li class="flex items-center gap-1">
                @if(! $loop->last)
                    <a href="{{ $crumb['url'] }}" class="hover:text-primary-600">{{ $crumb['name'] }}</a>
                    {{ icon('chevron-right', 'size-3') }}
                @else
                    <span class="line-clamp-1 text-slate-700" aria-current="page">{{ $crumb['name'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
