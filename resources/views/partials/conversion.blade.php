@php($c = \App\Support\Settings::group('conversion'))
@php($wa = preg_replace('/\D/', '', (string) setting('general.whatsapp')))
@php($raised = request()->routeIs('checkout') || (request()->routeIs('product.show') && ! empty($c['sticky_add_to_cart'])))
@php($mobileBottom = request()->routeIs('checkout') ? 'bottom-[calc(5.5rem+env(safe-area-inset-bottom))]' : ($raised ? 'bottom-[calc(9.5rem+env(safe-area-inset-bottom))]' : 'bottom-[calc(5rem+env(safe-area-inset-bottom))]'))

{{-- Floating WhatsApp chat --}}
@if(! empty($c['whatsapp_button']) && $wa)
    <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode($c['whatsapp_message'] ?? '') }}" target="_blank" rel="noopener"
       class="fixed {{ $mobileBottom }} right-4 z-30 grid size-13 place-items-center rounded-full bg-[#25d366] text-white shadow-lg shadow-emerald-600/30 transition hover:scale-105 md:bottom-6 md:right-6"
       aria-label="Chat on WhatsApp">
        <span class="absolute inset-0 animate-ping rounded-full bg-[#25d366] opacity-30"></span>
        {{ icon('whatsapp', 'relative size-7') }}
    </a>
@endif

{{-- Real recent purchases (social proof) --}}
@if(! empty($c['sales_popup']) && ! request()->routeIs('checkout*'))
    <div x-data="salesPopup({{ (int) ($c['sales_popup_interval'] ?? 18) }})" x-cloak x-show="show"
         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:leave="transition duration-200" x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed {{ $mobileBottom }} left-3 z-30 w-[min(22rem,calc(100vw-6rem))] md:bottom-6 md:left-6">
        <template x-if="current">
            <div class="relative flex items-center gap-3 rounded-card border border-slate-200 bg-white p-2.5 pr-8 shadow-2xl">
                <a :href="current.url" class="shrink-0"><img :src="current.image" alt="" class="size-14 rounded-lg object-cover" width="56" height="56"></a>
                <a :href="current.url" class="min-w-0 text-xs leading-snug text-slate-600">
                    <span class="font-semibold text-slate-900" x-text="current.name"></span><span x-show="current.city"> from <span x-text="current.city"></span></span> purchased
                    <span class="line-clamp-1 font-semibold text-primary-600" x-text="current.product"></span>
                    <span class="flex items-center gap-1 text-[11px] text-slate-400">{{ icon('check-circle', 'size-3.5 text-emerald-500') }}<span x-text="current.ago"></span> · Verified</span>
                </a>
                <button type="button" class="absolute right-1.5 top-1.5 rounded-full p-1 text-slate-400 hover:bg-slate-100" @click="close()" aria-label="Close">{{ icon('x', 'size-3.5', 2) }}</button>
            </div>
        </template>
    </div>
@endif

{{-- Exit-intent offer --}}
@if(! empty($c['exit_popup']) && ! request()->routeIs('checkout*', 'account*'))
    <div x-data="exitPopup" x-cloak x-show="open" class="fixed inset-0 z-[80] grid place-items-center p-4" role="dialog" aria-modal="true" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>
        <div x-show="open" x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0 scale-95" class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white text-center shadow-2xl">
            <button type="button" class="absolute right-3 top-3 z-10 rounded-full bg-white/80 p-1.5 text-slate-500 hover:bg-white" @click="open = false" aria-label="Close">{{ icon('x', 'size-5') }}</button>
            @if(! empty($c['exit_popup_image']))
                <img src="{{ img($c['exit_popup_image'], 640) }}" alt="" class="aspect-[2/1] w-full object-cover" loading="lazy" width="640" height="320">
            @else
                <div class="bg-gradient-to-br from-primary-500 to-primary-700 px-6 py-8 text-white">
                    <span class="mx-auto grid size-16 place-items-center rounded-full bg-white/20">{{ icon('gift', 'size-8') }}</span>
                </div>
            @endif
            <div class="p-6">
                <h2 class="text-2xl font-extrabold text-slate-900">{{ $c['exit_popup_title'] ?? '' }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $c['exit_popup_text'] ?? '' }}</p>
                @if(! empty($c['exit_popup_coupon']))
                    <button type="button" @click="copy(@js($c['exit_popup_coupon']))" class="mx-auto mt-5 flex items-center gap-3 rounded-xl border-2 border-dashed border-primary-400 bg-primary-50 px-5 py-3 font-mono text-xl font-bold tracking-widest text-primary-700">
                        {{ $c['exit_popup_coupon'] }}
                        <span class="text-xs font-sans font-semibold tracking-normal" x-text="copied ? 'Copied!' : 'Tap to copy'"></span>
                    </button>
                @endif
                <a href="{{ route('shop') }}" class="btn btn-primary btn-lg mt-5 w-full" @click="open = false">Shop Now & Save</a>
                <button type="button" class="mt-3 text-xs text-slate-400 hover:text-slate-600" @click="open = false">No thanks, I'll pay full price</button>
            </div>
        </div>
    </div>
@endif

{{-- Quick view modal --}}
<div x-data="quickView" x-cloak x-show="$store.ui.quick" @quick-view.window="open($event.detail)" class="fixed inset-0 z-[75] grid place-items-end p-0 sm:place-items-center sm:p-4" role="dialog" aria-modal="true" @keydown.escape.window="$store.ui.quick = null">
    <div x-show="$store.ui.quick" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="$store.ui.quick = null"></div>
    <div x-show="$store.ui.quick" x-transition:enter="transition duration-250" x-transition:enter-start="opacity-0 translate-y-8" class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
        <button type="button" class="absolute right-3 top-3 z-10 rounded-full bg-white p-2 text-slate-500 shadow hover:text-slate-900" @click="$store.ui.quick = null" aria-label="Close">{{ icon('x', 'size-5') }}</button>
        <div x-show="loading" class="grid h-80 place-items-center"><span class="size-8 animate-spin rounded-full border-4 border-slate-200 border-t-primary-500"></span></div>
        <div x-html="html"></div>
    </div>
</div>
