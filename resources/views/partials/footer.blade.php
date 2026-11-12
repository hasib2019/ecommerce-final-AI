@if(request()->routeIs('checkout'))
    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        <div class="container-x flex flex-col items-center justify-center gap-2 sm:flex-row sm:gap-6">
            <span>{{ str_replace(['{year}', '{site}'], [date('Y'), setting('general.site_name')], (string) setting('footer.copyright')) }}</span>
            <a href="{{ url('/return-policy') }}" class="hover:text-primary-600">Return policy</a>
            <a href="{{ url('/terms-and-conditions') }}" class="hover:text-primary-600">Terms</a>
            <a href="{{ url('/privacy-policy') }}" class="hover:text-primary-600">Privacy</a>
        </div>
    </footer>
@else
    @include('partials.footer-full')
@endif
