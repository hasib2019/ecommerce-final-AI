@if($section->title || ($link ?? null))
<div class="mb-4 flex items-end justify-between gap-4 sm:mb-6">
    <div>
        @if($section->title)<h2 class="section-title">{{ $section->title }}</h2>@endif
        @if($section->subtitle)<p class="mt-1 text-sm text-slate-500">{{ $section->subtitle }}</p>@endif
    </div>
    <div class="flex shrink-0 items-center gap-2">
        {{ $slot ?? '' }}
        @if($link ?? null)
            <a href="{{ menu_url($link) }}" class="flex items-center gap-1 text-sm font-semibold text-primary-600 hover:underline">{{ $linkText ?? 'View all' }} {{ icon('arrow-right', 'size-4', 2) }}</a>
        @endif
    </div>
</div>
@endif
