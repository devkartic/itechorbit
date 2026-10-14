<section id="features" class="features">
    @if(sizeof($user->skills))
        <div class="container" data-aos="fade-up">
            <header class="section-header">
                <h2>Features</h2>
                <p>Professional Skills</p>
            </header>
            <div class="row">
                <div class="col-lg-12 mt-5 mt-lg-0">
                    <div class="row align-self-center gy-4 justify-content-center">
                        @foreach($user->skills as $key => $skill)
                            <div class="col-md-3 col-6" data-aos="zoom-out" data-aos-delay="{{ ++$key }}00">
                                <div class="feature-box d-flex align-items-center justify-content-center">
                                    @if(!empty($skill->fa_icon)) {!!  $skill->fa_icon  !!} @endif
                                    <h3>{{ $skill->name }}</h3>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- / row -->
        </div>
    @endif
</section>
