<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    @php
        $publicPage = str_starts_with($page['component'], 'Website/') || str_starts_with($page['component'], 'Portfolio/');
        $tagManagerId = $publicPage ? trim((string) config('site.tag_manager_id')) : '';
        $analyticsId = $publicPage ? trim((string) config('site.analytics_id')) : '';
        $siteVerification = $publicPage ? trim((string) config('site.verification')) : '';
    @endphp
    @if ($tagManagerId !== '')
        <script nonce="{{ Vite::cspNonce() }}">
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+encodeURIComponent(i)+dl;
            var n=d.querySelector('[nonce]');if(n){j.setAttribute('nonce',n.nonce||n.getAttribute('nonce'));}f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer',{{ Illuminate\Support\Js::from($tagManagerId) }});
        </script>
    @endif
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($siteVerification !== '')
        <meta name="google-site-verification" content="{{ $siteVerification }}">
    @endif
    @if ($analyticsId !== '')
        <script nonce="{{ Vite::cspNonce() }}" async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($analyticsId) }}"></script>
        <script nonce="{{ Vite::cspNonce() }}">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', {{ Illuminate\Support\Js::from($analyticsId) }});
        </script>
    @endif
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @viteReactRefresh
    <link rel="preload" href="{{ Vite::asset('node_modules/@fontsource/inter/files/inter-latin-600-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    {{-- Include the page chunk so its CSS is present before hydration, on every page. --}}
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
</head>
<body class="font-sans">
    @if ($tagManagerId !== '')
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($tagManagerId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @inertia
</body>
</html>
