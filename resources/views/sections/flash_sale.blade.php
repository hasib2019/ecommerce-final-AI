@php($ends = $section->get('ends_at'))
<section class="container-x" aria-label="{{ $section->title ?: 'Flash sale' }}">
    <div class="overflow-hidden rounded-card bg-gradient-to-br from-primary-500 via-primary-600 to-primary-800 p-3 sm:p-5">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 sm:mb-5">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="flex items-center gap-2 text-lg font-extrabold text-white sm:text-2xl">{{ icon('bolt', 'size-6 text-yellow-300') }} {{ $section->title ?: 'Flash Sale' }}</h2>
                @if($ends)
                    <div x-data="countdown(@js(\Illuminate\Support\Carbon::parse($ends)->toIso8601String()))" class="flex items-center gap-1.5 text-white" aria-label="Time left">
                        <span class="text-xs font-medium text-white/80">Ends in</span>
                        <template x-for="(v, i) in [d, h, m, s]" :key="i">
                            <span class="flex items-center gap-1.5">
                                <span class="grid min-w-8 place-items-center rounded-md bg-white px-1 py-1 font-mono text-sm font-bold text-primary-700" x-text="v"></span>
                                <span x-show="i < 3" class="font-bold">:</span>
                            </span>
                        </template>
                    </div>
                @endif
            </div>
            @if($link = $section->get('view_all_url'))
                <a href="{{ menu_url($link) }}" class="flex items-center gap-1 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold text-white hover:bg-white/25">Shop all deals {{ icon('arrow-right', 'size-4', 2) }}</a>
            @endif
        </div>
        @if($section->subtitle)<p class="-mt-2 mb-4 text-sm text-white/85">{{ $section->subtitle }}</p>@endif
        @include('partials.product-row', ['products' => $section->items, 'cols' => (int) $section->get('columns', 5)])
    </div>
</section>
