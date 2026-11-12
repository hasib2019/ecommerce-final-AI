@php($s = seo()->data())
<title>{{ $s['title'] }}</title>
<meta name="description" content="{{ $s['description'] }}">
@if($s['keywords'])
<meta name="keywords" content="{{ $s['keywords'] }}">
@endif
<meta name="robots" content="{{ $s['robots'] }}">
<link rel="canonical" href="{{ $s['canonical'] }}">
@foreach($s['links'] as $link)
<link rel="{{ $link['rel'] }}" href="{{ $link['href'] }}">
@endforeach
<meta property="og:site_name" content="{{ setting('general.site_name') }}">
<meta property="og:type" content="{{ $s['type'] }}">
<meta property="og:title" content="{{ $s['title'] }}">
<meta property="og:description" content="{{ $s['description'] }}">
<meta property="og:url" content="{{ $s['canonical'] }}">
<meta property="og:locale" content="en_US">
@if($s['image'])
<meta property="og:image" content="{{ $s['image'] }}">
<meta name="twitter:image" content="{{ $s['image'] }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $s['title'] }}">
<meta name="twitter:description" content="{{ $s['description'] }}">
@foreach($s['meta'] as $property => $content)
<meta property="{{ $property }}" content="{{ $content }}">
@endforeach
@if($v = setting('seo.google_verification'))
<meta name="google-site-verification" content="{{ $v }}">
@endif
@if($v = setting('seo.bing_verification'))
<meta name="msvalidate.01" content="{{ $v }}">
@endif
@if($v = setting('seo.facebook_domain_verification'))
<meta name="facebook-domain-verification" content="{{ $v }}">
@endif
@foreach($s['schemas'] as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
