@php($logo = setting('general.logo'))
<a href="{{ route('home') }}" class="flex shrink-0 items-center {{ ($center ?? false) ? 'justify-self-center' : '' }}">
    @if($logo)
        <img src="{{ img($logo, 480) }}" alt="{{ setting('general.site_name') }}" style="height: {{ (int) setting('general.logo_height', 36) }}px" class="w-auto max-w-[170px] object-contain md:max-w-[220px]" width="200" height="{{ (int) setting('general.logo_height', 36) }}" fetchpriority="high">
    @else
        <span class="flex items-center gap-2">
            <span class="grid size-9 place-items-center rounded-btn bg-primary-500 text-lg font-black text-white shadow-sm shadow-primary-500/40">{{ mb_substr(setting('general.site_name'), 0, 1) }}</span>
            <span class="text-xl font-extrabold tracking-tight text-slate-900">{{ setting('general.site_name') }}</span>
        </span>
    @endif
</a>
