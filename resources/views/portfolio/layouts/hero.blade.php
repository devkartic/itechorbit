<section id="hero" class="hero d-flex align-items-center">
    @if(!empty($user->basic))
        <div class="container">
            <div class="row">
                <div class="col-lg-6 d-flex flex-column justify-content-center">
                    <h1 data-aos="fade-up">{{ $user->basic->service_offering_title }}</h1>
                    <h2 data-aos="fade-up" data-aos-delay="400">I'm a {{ $user->basic->designation }}.</h2>
                    <!-- Social links -->
                    <div data-aos="fade-up" data-aos-delay="600">
                        <div class="text-center text-lg-start mt-4">
                            <div class="scrollto d-inline-flex align-items-center justify-content-center align-self-center">
                                @if(sizeof($user->social_links))
                                    @foreach($user->social_links as $social_link)
                                        <a type="button" href="{{ $social_link->social_url }}" class="btn btn-sm btn-outline-primary me-1" target="_blank">{!! $social_link->fa_icon !!}</a>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div data-aos="fade-up" data-aos-delay="600">
                        <div class="text-center text-lg-start">
                            <a href="#about"
                               class="btn-get-started scrollto d-inline-flex align-items-center justify-content-center align-self-center">
                                <span>Get Started</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                            @if(!empty($user->basic->cv_path) && file_exists($user->basic->cv_path) && $user->basic->cv_public_status)
                            <a href="{{ asset($user->basic->cv_path) }}" download="{{ strtolower($user->name) }}_resume"
                               class="btn-get-started scrollto d-inline-flex align-items-center justify-content-center align-self-center">
                                <span>Download Resume</span>
                                <i class="bi bi-arrow-down"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 hero-img" data-aos="zoom-out" data-aos-delay="200">
                    @if(!empty($user->basic->banner_image_path) && file_exists($user->basic->banner_image_path))
                        <img src="{{ asset($user->basic->banner_image_path) }}" class="profile-user-img img-fluid" alt="">
                    @else
                        <img src="{{ asset('portfolio/img/hero-img.png') }}" class="profile-user-img img-fluid" alt="">
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>
