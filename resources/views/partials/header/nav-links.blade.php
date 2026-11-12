@foreach($menu as $item)
    @continue(blank($item['label'] ?? null))
    @php($children = array_filter((array) ($item['children'] ?? []), fn ($c) => filled($c['label'] ?? null)))
    @php($active = request()->fullUrlIs(menu_url($item['url'] ?? '#')) || (($item['url'] ?? '') === '/' && request()->is('/')))
    @if($children)
        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
            <a href="{{ menu_url($item['url'] ?? '#') }}" class="flex items-center gap-1 rounded-btn px-3 py-2 text-sm font-semibold {{ ! empty($item['highlight']) ? 'text-primary-600' : 'text-slate-700 hover:text-primary-600' }}" @focus="open = true">
                {{ $item['label'] }} {{ icon('chevron-down', 'size-3.5', 2) }}
            </a>
            <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="absolute left-0 top-full z-50 min-w-52 rounded-card border border-slate-200 bg-white p-2 shadow-xl">
                @foreach($children as $child)
                    <a href="{{ menu_url($child['url'] ?? '#') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary-600">{{ $child['label'] }}</a>
                @endforeach
            </div>
        </div>
    @else
        <a href="{{ menu_url($item['url'] ?? '#') }}" class="relative flex items-center gap-1.5 rounded-btn px-3 py-2 text-sm font-semibold {{ ! empty($item['highlight']) ? 'text-primary-600' : ($active ? 'text-primary-600' : 'text-slate-700 hover:text-primary-600') }}">
            @if(! empty($item['highlight'])){{ icon('fire', 'size-4') }}@endif
            {{ $item['label'] }}
        </a>
    @endif
@endforeach
