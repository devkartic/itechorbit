<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('page_title', 'Home') - {{ config('app.name', 'itechorbit') }}</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <!-- Google adsense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6479484571679607"
            crossorigin="anonymous"></script>
    <meta name="google-site-verification" content="6ZjlWVJoAFb6SKF0esemqyCpzyk7_gVrQCcYvUJGmAM" />
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-NJGX639VCS"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-NJGX639VCS');
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <!-- Favicons -->
    <link href="{{ asset('favicon.svg') }}" rel="icon" type="image/svg+xml">
    <link href="{{ asset('apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('blog/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('blog/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('blog/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('blog/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('blog/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('blog/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{ asset('blog/css/style.css') }}" rel="stylesheet">

    <!-- Font awesome library Files -->
    <link href="{{ asset('common_libraries/fontawesome-free-6.1.1-web/css/all.css') }}" rel="stylesheet">
    <link href="{{ asset('blog/css/custom.css') }}" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('common_libraries/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- jQuery library Files -->
    <script src="{{ asset('common_libraries/js/jquery-3.6.0.min.js') }}" ></script>
</head>
