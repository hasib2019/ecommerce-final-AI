@php($gtm = preg_replace('/[^A-Z0-9\-]/i', '', (string) setting('seo.gtm_id')))
@if($gtm)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
