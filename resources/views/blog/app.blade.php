<!DOCTYPE html>
<html lang="en">

@include('blog.includes.header_library')

<body>

<!-- Header -->
@include('blog.includes.header_navbar')
<!-- End Header -->

<!-- Banner Section -->
    @include('blog.includes.banner')
<!-- End Banner Section -->

<!-- Start #main -->
<main id="main">
    <!-- Blog Section -->
    <section id="blog" class="blog">
        <div class="container">
            <div class="row">
                @yield('content')
                <!-- End blog entries list -->
                @includeWhen(!empty($post), 'blog.compose.right_sidebar')
                <!-- End blog sidebar -->
            </div>
        </div>
    </section>
    <!-- End Blog Section -->
</main>
<!-- End #main -->

<!-- News later  -->
@include('blog.includes.news_later')
<!-- News later -->

<!-- Footer  -->
@include('blog.includes.footer')
<!-- End Footer -->

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- JS Files -->
@include('blog.includes.footer_library')
</body>

</html>
