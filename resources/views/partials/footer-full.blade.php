@php
    $f = \App\Support\Settings::group('footer');
    $dark = ($f['style'] ?? 'dark') === 'dark';
    $social = array_filter((array) ($f['social'] ?? []));
    $copyright = str_replace(['{year}', '{site}'], [date('Y'), setting('general.site_name')], (string) ($f['copyright'] ?? ''));
    $payLabels = ['cod' => ['Cash on Delivery', 'bg-emerald-700 text-white'], 'bkash' => ['bKash', 'bg-[#e2136e] text-white'], 'nagad' => ['Nagad', 'bg-[#f6921e] text-slate-900'], 'rocket' => ['Rocket', 'bg-[#8c3494] text-white'], 'visa' => ['VISA', 'bg-[#1a1f71] text-white italic'], 'mastercard' => ['Mastercard', 'bg-[#eb001b] text-white'], 'amex' => ['AMEX', 'bg-[#2e77bc] text-white']];
    $trust = (array) setting('conversion.trust_badges', []);
@endphp

{{-- Trust strip above footer --}}
@if($trust)
<section class="border-t border-slate-200 bg-white" aria-label="Why shop with us">
    <div class="container-x grid grid-cols-2 gap-4 py-6 md:grid-cols-4">
        @foreach($trust as $t)
            <div class="flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary-50 text-primary-600">{{ icon($t['icon'] ?? 'check-circle', 'size-5') }}</span>
                <span class="text-sm font-semibold text-slate-800">{{ $t['text'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
</section>
@endif

<footer class="{{ $dark ? 'bg-slate-950 text-slate-400' : 'border-t border-slate-200 bg-white text-slate-600' }}">
    @if(! empty($f['show_newsletter']))
        <div class="{{ $dark ? 'border-b border-white/10' : 'border-b border-slate-200' }}">
            <div class="container-x flex flex-col items-start justify-between gap-4 py-8 md:flex-row md:items-center">
                <div>
                    <h2 class="text-lg font-bold {{ $dark ? 'text-white' : 'text-slate-900' }}">{{ $f['newsletter_title'] ?? 'Newsletter' }}</h2>
                    <p class="mt-1 text-sm">{{ $f['newsletter_text'] ?? '' }}</p>
                </div>
                <form x-data="newsletter" @submit.prevent="submit" class="flex w-full max-w-md gap-2">
                    <label for="footer-email" class="sr-only">Email address</label>
                    <input id="footer-email" type="email" x-model="email" required placeholder="Enter your email" class="input {{ $dark ? 'border-white/10 bg-white/5 text-white placeholder:text-slate-500' : '' }}" :disabled="done">
                    <button class="btn btn-primary shrink-0" :disabled="busy || done" x-text="done ? 'Subscribed ✓' : 'Subscribe'">Subscribe</button>
                </form>
            </div>
        </div>
    @endif

    <div class="container-x grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <div class="{{ $dark ? '[&_span.text-slate-900]:text-white' : '' }}">@include('partials.header.logo')</div>
            <p class="mt-4 max-w-sm text-sm leading-relaxed">{{ $f['about'] ?? '' }}</p>
            @if($social)
                <div class="mt-5 flex gap-2">
                    @foreach($social as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"
                           class="grid size-9 place-items-center rounded-full transition {{ $dark ? 'bg-white/10 text-white hover:bg-primary-500' : 'bg-slate-100 text-slate-700 hover:bg-primary-500 hover:text-white' }}">
                            {{ icon($network, 'size-4') }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @foreach((array) ($f['columns'] ?? []) as $col)
            <div class="lg:col-span-2" x-data="{ open: false }">
                <h3 class="flex items-center justify-between text-sm font-bold uppercase tracking-wider {{ $dark ? 'text-white' : 'text-slate-900' }}">
                    {{ $col['title'] ?? '' }}
                </h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach((array) ($col['links'] ?? []) as $link)
                        @continue(blank($link['label'] ?? null))
                        <li><a href="{{ menu_url($link['url'] ?? '#') }}" class="transition hover:text-primary-500">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        @if(! empty($f['show_contact']))
            <div class="lg:col-span-4 {{ count((array) ($f['columns'] ?? [])) >= 3 ? 'lg:col-span-2' : '' }}">
                <h3 class="text-sm font-bold uppercase tracking-wider {{ $dark ? 'text-white' : 'text-slate-900' }}">Contact Us</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    @if($v = setting('general.address'))
                        <li class="flex gap-2.5">{{ icon('map-pin', 'size-5 shrink-0 text-primary-500') }}<span>{{ $v }}</span></li>
                    @endif
                    @if($v = setting('general.phone'))
                        <li class="flex gap-2.5">{{ icon('phone', 'size-5 shrink-0 text-primary-500') }}<a href="tel:{{ preg_replace('/[^\d+]/', '', $v) }}" class="hover:text-primary-500">{{ $v }}</a></li>
                    @endif
                    @if($v = setting('general.email'))
                        <li class="flex gap-2.5">{{ icon('mail', 'size-5 shrink-0 text-primary-500') }}<a href="mailto:{{ $v }}" class="hover:text-primary-500">{{ $v }}</a></li>
                    @endif
                    @if($v = setting('general.opening_hours'))
                        <li class="flex gap-2.5">{{ icon('clock', 'size-5 shrink-0 text-primary-500') }}<span>{{ $v }}</span></li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    <div class="{{ $dark ? 'border-t border-white/10' : 'border-t border-slate-200' }}">
        <div class="container-x flex flex-col items-center justify-between gap-3 py-5 text-xs md:flex-row">
            <p>{{ $copyright }}</p>
            <div class="flex flex-wrap items-center justify-center gap-1.5">
                @if(! empty($f['payment_image']))
                    <img src="{{ img($f['payment_image'], 640) }}" alt="Payment methods" class="h-7 w-auto" loading="lazy" height="28" width="240">
                @else
                    <span class="mr-1">We accept:</span>
                    @foreach((array) ($f['payment_methods'] ?? []) as $pm)
                        @if(isset($payLabels[$pm]))
                            <span class="rounded px-2 py-1 text-[10px] font-bold {{ $payLabels[$pm][1] }}">{{ $payLabels[$pm][0] }}</span>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</footer>
