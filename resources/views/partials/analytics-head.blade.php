@php
    $gtm = preg_replace('/[^A-Z0-9\-]/i', '', (string) setting('seo.gtm_id'));
    $ga4 = preg_replace('/[^A-Z0-9\-]/i', '', (string) setting('seo.ga4_id'));
    $pixel = preg_replace('/\D/', '', (string) setting('seo.fb_pixel_id'));
@endphp
<script>window.dataLayer = window.dataLayer || [];</script>
@if($gtm)
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm }}');</script>
@endif
@if($ga4 && ! $gtm)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
<script>function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $ga4 }}');</script>
@endif
@if($pixel)
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $pixel }}');fbq('track','PageView');</script>
@endif
