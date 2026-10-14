<section id="testimonials" class="testimonials">
    @if(sizeof($user->testimonials))
        <div class="container" data-aos="fade-up">
            <header class="section-header">
                <h2>Testimonials</h2>
                <p>What they are saying about us</p>
            </header>
            <div class="testimonials-slider swiper" data-aos="fade-up" data-aos-delay="200">
                <div class="swiper-wrapper">
                    @foreach($user->testimonials as $testimonial)
                        <div class="swiper-slide">
                            <div class="testimonial-item">
                                <div class="stars">
                                    @if(!empty($testimonial->ratings))
                                        @for($rating = 0; $rating < $testimonial->ratings; $rating++)
                                            <i class="bi bi-star-fill"></i>
                                        @endfor
                                    @endif
                                </div>
                                <p>{{ $testimonial->comment }}</p>
                                <div class="profile mt-auto">
                                    <img src="{{ asset('admin/dist/img/user-avatar.webp') }}" class="testimonial-img" alt="">
                                    <h3>{{ $testimonial->name }}</h3>
                                    <h4 class="text-primary">{{ $testimonial->designation }}</h4>
                                    <h4 class="fw-bold text-black mt-1 mb-2">At {{ $testimonial->organization }}</h4>
                                    <h4 class="text-secondary mt-1"><i class="fas fa-envelope-open mr-1 text-warning"></i> {{ $testimonial->email }}</h4>
                                    <h4 class="text-secondary mt-1"><i class="fas fa-mobile-alt mr-1 text-warning"></i> {{ $testimonial->mobile }}</h4>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <!-- End testimonial items -->
                </div>
                <div class="swiper-pagination"></div>
            </div>

        </div>
    @endif
</section>
