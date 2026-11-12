@php
    $hex = fn ($v, $d) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $v) ? $v : $d;
    $primary = $hex(setting('appearance.primary_color'), '#d43d0b');
    $accent = $hex(setting('appearance.accent_color'), '#0f172a');
    $font = config('shop.fonts.'.setting('appearance.font', 'system')) ?? config('shop.fonts.system');
    $radius = config('shop.radius.'.setting('appearance.radius', 'lg'), '0.75rem');
    $favicon = setting('general.favicon');
@endphp
@if($font['family'])
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ $font['family'] }}&display=swap" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ $font['family'] }}&display=swap"></noscript>
@endif
<style>:root{--brand:{{ $primary }};--accent:{{ $accent }};--font-body:{!! $font['css'] !!};--radius:{{ $radius }}}</style>
@if($css = setting('appearance.custom_css'))
<style>{!! strip_tags($css) !!}</style>
@endif
<meta name="theme-color" content="{{ $primary }}">
@if($favicon)
<link rel="icon" href="{{ img($favicon) }}">
<link rel="apple-touch-icon" href="{{ img($favicon, 320) }}">
@else
<link rel="icon" href="data:image/svg+xml,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="'.$primary.'"/><text x="16" y="22" font-family="Arial" font-weight="700" font-size="17" fill="#fff" text-anchor="middle">'.mb_substr((string) setting('general.site_name'), 0, 1).'</text></svg>') }}">
@endif
