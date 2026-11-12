<?php

use App\Services\Cart;
use App\Services\Wishlist;
use App\Support\Img;
use App\Support\Seo;
use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $decimals = (int) setting('general.decimals', 0);
        $symbol = (string) setting('general.currency_symbol', '৳');
        $formatted = number_format($amount, $decimals);

        return setting('general.currency_position', 'left') === 'right'
            ? $formatted.' '.$symbol
            : $symbol.$formatted;
    }
}

if (! function_exists('img')) {
    function img(?string $path, int $width = 0): string
    {
        return Img::url($path, $width);
    }
}

if (! function_exists('seo')) {
    function seo(): Seo
    {
        return app(Seo::class);
    }
}

if (! function_exists('cart')) {
    function cart(): Cart
    {
        return app(Cart::class);
    }
}

if (! function_exists('wishlist')) {
    function wishlist(): Wishlist
    {
        return app(Wishlist::class);
    }
}

if (! function_exists('menu_url')) {
    /** Turn an admin-entered link ("/shop", "shop", "https://…") into an absolute URL. */
    function menu_url(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || $url === '#') {
            return '#';
        }
        if (preg_match('#^(https?:|mailto:|tel:|\#)#i', $url)) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }
}

if (! function_exists('icon')) {
    function icon(string $name, string $class = 'size-5', float $stroke = 1.6): \Illuminate\Support\HtmlString
    {
        return \App\Support\Icon::render($name, $class, $stroke);
    }
}
