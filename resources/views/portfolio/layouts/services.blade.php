<section id="services" class="services">
    @if(sizeof($user->services))
        <div class="container" data-aos="fade-up">
            <header class="section-header">
                <h2>Services</h2>
                <p>Available services are given bellow.</p>
            </header>
            <div class="row gy-4 justify-content-center">
                @foreach($user->services as $key => $service)
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ ++$key }}00">
                    <div class="service-box blue">
                        <i class="ri-discuss-line icon"></i>
                        <h3>{{ $service->name }}</h3>
                        <p>{{ $service->title }}</p>
                        <a href="#" class="read-more"><span>Read More</span> <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
