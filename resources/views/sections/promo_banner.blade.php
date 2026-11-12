@php
    $ends = $section->get('ends_at');
    $align = ['left' => 'items-start text-left', 'center' => 'items-center text-center', 'right' => 'items-end text-right'][$section->get('align', 'center')] ?? 'items-center text-center';
@endphp
<section class="container-x" aria-label="{{ $section->get('heading') ?: 'Promotion' }}">
    <div class="relative overflow-hidden rounded-card bg-accent">
        @if($image = $section->get('image'))
            <img src="{{ img($image, 1600) }}" srcset="{{ \App\Support\Img::srcset($image, [640, 960, 1600]) }}" sizes="100vw" alt="" class="absolute inset-0 size-full object-cover" loading="lazy" width="1600" height="500">
            <div class="absolute inset-0 bg-slate-950/50"></div>
        @endif
        <div class="relative flex flex-col gap-3 px-6 py-12 sm:px-12 sm:py-16 {{ $align }}">
            @if($section->subtitle)<span class="badge bg-primary-500 px-3 py-1 text-xs text-white">{{ $section->subtitle }}</span>@endif
            <h2 class="max-w-2xl text-2xl font-extrabold text-white sm:text-4xl">{{ $section->get('heading') ?: $section->title }}</h2>
            @if($text = $section->get('text'))<p class="max-w-xl text-sm text-white/85 sm:text-base">{{ $text }}</p>@endif
            @if($ends)
                <div x-data="countdown(@js(\Illuminate\Support\Carbon::parse($ends)->toIso8601String()))" class="flex gap-2 text-white" x-show="!done">
                    @foreach(['d' => 'Days', 'h' => 'Hours', 'm' => 'Mins', 's' => 'Secs'] as $k => $label)
                        <div class="grid min-w-14 rounded-lg bg-white/15 px-2 py-2 text-center backdrop-blur">
                            <span class="font-mono text-xl font-bold" x-text="{{ $k }}">00</span>
                            <span class="text-[10px] uppercase tracking-wider text-white/70">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            @if($btn = $section->get('button_text'))
                <a href="{{ menu_url($section->get('button_link', '/shop')) }}" class="btn btn-primary btn-lg mt-2">{{ $btn }} {{ icon('arrow-right', 'size-4', 2) }}</a>
            @endif
        </div>
    </div>
</section>
