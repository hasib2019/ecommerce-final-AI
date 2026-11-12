@if(request()->routeIs('checkout'))
    {{-- Distraction-free checkout: no menus or banners competing with "Confirm order" --}}
    <header class="border-b border-slate-200 bg-white">
        <div class="container-x flex h-16 items-center justify-between gap-3">
            @include('partials.header.logo')
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden items-center gap-1.5 font-semibold text-emerald-600 sm:flex">{{ icon('lock', 'size-4') }} Secure checkout</span>
                @if($phone = setting('general.phone'))
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="flex items-center gap-1.5 font-semibold text-slate-700">{{ icon('phone', 'size-4') }} <span class="hidden sm:inline">Need help?</span> {{ $phone }}</a>
                @endif
            </div>
        </div>
    </header>
@else
    @include('partials.header.full')
@endif
