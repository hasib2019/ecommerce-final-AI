<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo')
    @include('partials.theme')
    @if(!empty($lcp))
        <link rel="preload" as="image" href="{{ img($lcp, 1280) }}" imagesrcset="{{ \App\Support\Img::srcset($lcp, [640, 960, 1280, 1600]) }}" imagesizes="100vw" fetchpriority="high">
    @endif
    @stack('preload')
    @php
        $shopConfig = [
            'currency' => setting('general.currency_code', 'BDT'),
            'cartCount' => cart()->count(),
            'wishlist' => wishlist()->ids(),
            'routes' => [
                'cartData' => route('cart.data'),
                'cartAdd' => route('cart.add'),
                'cartUpdate' => route('cart.update'),
                'coupon' => route('cart.coupon'),
                'wishlistToggle' => route('wishlist.toggle'),
                'suggest' => route('search.suggest'),
                'checkoutSummary' => route('checkout.summary'),
                'checkoutCapture' => route('checkout.capture'),
                'recentSales' => route('recent-sales'),
                'newsletter' => route('newsletter'),
            ],
        ];
    @endphp
    <script>window.SHOP = @json($shopConfig);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.analytics-head')
    @stack('head')
    {!! setting('appearance.head_scripts') !!}
</head>
<body class="flex min-h-screen flex-col {{ setting('mobile.bottom_nav') && ! request()->routeIs('checkout') ? 'pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0' : '' }}">
    @include('partials.analytics-body')
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:shadow">Skip to content</a>

    @include('partials.header')

    <main id="main" class="flex-1">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.mobile-nav')
    @include('partials.cart-drawer')
    @include('partials.conversion')
    @include('partials.toasts')

    <script type="speculationrules">
    {"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/cart*","/checkout*","/logout","/admin*","/account*","/wishlist*"]}},{"not":{"selector_matches":"[data-no-prefetch]"}}]},"eagerness":"moderate"}]}
    </script>
    @stack('scripts')
    {!! setting('appearance.body_scripts') !!}
    {{ \App\Support\Icon::sprite() }}
</body>
</html>
