@if(filled($b['heading'] ?? null) || filled($b['subheading'] ?? null))
    @php($light = ($b['text_color'] ?? 'light') === 'light')
    <span class="absolute inset-0 flex flex-col justify-center gap-1 p-5 sm:p-7 {{ $light ? 'text-white' : 'text-slate-900' }}">
        @if(filled($b['subheading'] ?? null))<span class="text-xs font-semibold uppercase tracking-wider {{ $light ? 'text-white/80' : 'text-primary-600' }}">{{ $b['subheading'] }}</span>@endif
        @if(filled($b['heading'] ?? null))<span class="max-w-[60%] text-lg font-extrabold leading-tight sm:text-2xl">{{ $b['heading'] }}</span>@endif
        @if(filled($b['button_text'] ?? null))<span class="mt-2 inline-flex w-fit items-center gap-1 rounded-full {{ $light ? 'bg-white text-slate-900' : 'bg-slate-900 text-white' }} px-4 py-1.5 text-xs font-bold transition group-hover:gap-2">{{ $b['button_text'] }} {{ icon('arrow-right', 'size-3.5', 2) }}</span>@endif
    </span>
@endif
