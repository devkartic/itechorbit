<section id="about" class="about">
    @if(!empty($user->basic))
    <div class="container" data-aos="fade-up">
        <div class="row gx-0">

            <div class="col-lg-6 d-flex flex-column justify-content-center" data-aos="fade-up" data-aos-delay="200">
                <div class="content">
                    <h3>LET’S INTRODUCE ABOUT MYSELF</h3>
                    <h2>{{ $user->basic->self_introduction }}</h2>
                    <p>{{ $user->basic->career_objective }}</p>
                    <p>{{ $user->basic->additional_information }}</p>
                    <div class="text-center text-lg-start">
                        <a href="#" class="btn-read-more d-inline-flex align-items-center justify-content-center align-self-center">
                            <span>Read More</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 d-flex align-items-center" data-aos="zoom-out" data-aos-delay="200">
                @if(!empty($user->basic->profile_image_path) && file_exists($user->basic->profile_image_path))
                    <img src="{{ asset($user->basic->profile_image_path) }}" class="profile-user-img img-fluid" alt="">
                @else
                    <img src="{{ asset('portfolio/img/about.jpg') }}" class="profile-user-img img-fluid" alt="">
                @endif
            </div>

        </div>
    </div>
    @endif
</section>
