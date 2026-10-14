<!DOCTYPE html>
<html lang="en">

@include('portfolio.includes.header_library')

<body>

<!-- Header -->
@include('portfolio.includes.header_navbar')
<!-- End Header -->

<!-- Hero Section -->
@include('portfolio.layouts.hero')
<!-- End Hero -->

<!-- Start #main -->
<main id="main">
    <!-- About Section -->
    @include('portfolio.layouts.about')
    <!-- End About Section -->

    <!-- Counts Section -->
{{--    @include('portfolio.layouts.counts')--}}
    <!-- End Counts Section -->

    <!-- Features Section -->
    @include('portfolio.layouts.skills')
    <!-- End Features Section -->

    <!-- Services Section -->
    @include('portfolio.layouts.services')
    <!-- End Services Section -->

    <!-- Portfolio Section -->
    @include('portfolio.layouts.portfolio')
    <!-- End Portfolio Section -->

    <!-- Testimonials Section -->
    @include('portfolio.layouts.testimonials')
    <!-- End Testimonials Section -->

    <!-- Contact Section -->
    @include('portfolio.layouts.contact')
    <!-- End Contact Section -->

</main>
<!-- End #main -->

<!-- Footer  -->
@include('portfolio.includes.footer')
<!-- End Footer -->

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
        class="bi bi-arrow-up-short"></i></a>

<!-- JS Files -->
@include('portfolio.includes.footer_library')
</body>

</html>
