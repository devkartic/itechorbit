<!-- Portfolio Section -->
<section id="portfolio" class="portfolio">
    @if(sizeof($user->projects))
        <div class="container">
            <header class="section-header">
                <h2>Portfolio</h2>
                <p>Latest works</p>
            </header>

            <div class="row" data-aos="fade-up" data-aos-delay="100">
                <div class="col-lg-12 d-flex justify-content-center">
                    <ul id="portfolio-flters">
                        <li data-filter="*" class="filter-active">All</li>
                        @php $technologies = $user->projects->pluck('skill_id')->unique(); @endphp
                        @foreach($technologies as $technology)
                            <li data-filter=".filter-{{ $technology }}">{{ \App\Models\Admin\Skill::find($technology)->name }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="row gy-4 portfolio-container d-flex justify-content-center">
                @foreach($user->projects as $key => $project)
                    <div class="col-lg-4 col-md-6 portfolio-item filter-{{ $project->skill_id }}">
                        <div class="portfolio-wrap">
                            @if(file_exists($project->image_path) && !empty(file_exists($project->image_path)))
                                <img src="{{ asset($project->image_path) }}" class="img-fluid" alt="">
                            @else
                                <img src="{{ asset('blog/img/portfolio/portfolio.webp') }}" class="img-fluid" alt="">
                            @endif
                            <div class="portfolio-info">
                                <h4>{{ $project->name }}</h4>
                                <h5>{{ $project->project_category }}</h5>
                                <p>{{ $project->about_project }}</p>
                                <div class="portfolio-links">
                                    @if(!empty(file_exists($project->image_path)) && file_exists($project->image_path) )
                                        <a href="{{ asset($project->image_path) }}"
                                           data-gallery="portfolioGallery" class="portfokio-lightbox"
                                           title="{{ $project->name }}"><i
                                                class="bi bi-plus"></i></a>
                                    @else
                                        <a href="{{ asset('blog/img/portfolio/portfolio.webp') }}"
                                           data-gallery="portfolioGallery" class="portfokio-lightbox"
                                           title="{{ $project->name }}"><i
                                                class="bi bi-plus"></i></a>
                                    @endif
                                    <a href="#" title="More Details" data-bs-toggle="modal"
                                       data-bs-target=".custom-modal-open-project-{{ $project->id }}"><i
                                            class="bi bi-link"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal Section -->
                    <div class="modal fade custom-modal-open-project-{{ $project->id }}" aria-hidden="true"
                         aria-labelledby="projectModalToggleLabel" tabindex="-1">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold text-uppercase" id="projectModalToggleLabel"><span
                                            class="text-primary">{!! $project->skill->fa_icon !!}</span> {{ $project->skill->name }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- ======= Portfolio Details Section ======= -->
                                    <section id="portfolio-details" class="portfolio-details">
                                        <div class="container">
                                            <div class="row gy-4">
                                                <div class="col-lg-8">
                                                    <div class="portfolio-details-slider swiper">
                                                        <div class="swiper-wrapper align-items-center">
                                                            <div class="swiper-slide">
                                                                @if(file_exists($project->image_path) && !empty(file_exists($project->image_path)))
                                                                    <img src="{{ asset($project->image_path) }}"
                                                                         class="img-fluid" alt="">
                                                                @else
                                                                    <img
                                                                        src="{{ asset('blog/img/portfolio/portfolio.webp') }}"
                                                                        class="img-fluid" alt="">
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="swiper-pagination"></div>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="portfolio-info">
                                                        <h3>Project information</h3>
                                                        <ul>
                                                            @if(!empty($project->name))
                                                                <li>
                                                                    <strong>Name: {{ $project->name }}</strong>
                                                                </li>
                                                            @endif
                                                            @if(!empty($project->project_category))
                                                                <li>
                                                                    <strong>Category</strong>: {{ $project->project_category }}
                                                                </li>
                                                            @endif
                                                            @if(!empty($project->project_category))
                                                                <li><strong>Client</strong>: {{ $project->client_name }}
                                                                </li>
                                                            @endif
                                                            @if(!empty($project->project_category))
                                                                <li>
                                                                    <strong>Responsibility</strong>: {{ $project->jobs }}
                                                                </li>
                                                            @endif
                                                            @if(!empty($project->project_category))
                                                                <li><strong>Played the
                                                                        roles</strong>: {{ $project->played_role }}</li>
                                                            @endif
                                                            @if(!empty($project->project_category))
                                                                <li><strong>Project URL</strong>: <a
                                                                        class="text-capitalize"
                                                                        href="{{ $project->project_url }}"
                                                                        target="_blank">{{ $project->project_url }}</a>
                                                                </li>
                                                            @endif
                                                            @if(!empty($project->technology_used))
                                                                <li><strong>Technology Used</strong>:
                                                                    @php
                                                                        $project->technology_used = json_decode($project->technology_used);
                                                                    @endphp
                                                                    {{ implode(', ', $project->technology_used) }}
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                    <div class="portfolio-description">
                                                        <h2>Project details</h2>
                                                        <p>{{ $project->about_project }}</p>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </section>
                                    <!-- End Portfolio Details Section -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Modal Section -->
                @endforeach
            </div>

        </div>
    @endif

    @if(sizeof($user->experiences))
        <div class="container">
            <div class="row gy-4 mt-5">
                <header class="section-header">
                    <p>Experience & Educations</p>
                </header>
                <!-- ======= Resume Section ======= -->
                <div class="container">
                    <div class="col-12">
                        <div class="card py-3">
                            <div class="card-body">
                                <!-- Timeline start -->
                                <div class="timeline">
                                    @foreach($user->experiences as $experience)
                                        <div class="timeline-row">
                                            <div class="timeline-time">
                                                {{ \Carbon\Carbon::parse($experience->created_at)->format('h:i A') }}
                                                <small>{{ \Carbon\Carbon::parse($experience->created_at)->format('M d, Y') }}</small>
                                            </div>
                                            <div class="timeline-dot fb-bg"></div>
                                            <div class="timeline-content">
                                                @if(!empty($experience->fa_icon))
                                                    {!! $experience->fa_icon !!}
                                                @else
                                                    <i class="fa-solid fa-circle-info"></i>
                                                @endif
                                                <h4>{{ $experience->designation }}!</h4>
                                                <h6>At {{ $experience->organization }}
                                                    ({{ \Carbon\Carbon::parse($experience->join_date)->format('M d, Y') }}
                                                    to @if(!empty($experience->resign_date))
                                                        {{ \Carbon\Carbon::parse($experience->resign_date)->format('M d, Y') }}
                                                        )
                                                    @else
                                                        Continuing)
                                                    @endif</h6>
                                                <div class="">
                                                    <span
                                                        class="badge badge-light px-0">{{ $experience->responsibilities }}</span>
                                                </div>
                                                <a href="#" class="badge rounded-pill bg-info text-dark mt-2"
                                                   data-bs-toggle="modal"
                                                   data-bs-target=".custom-modal-open-experience-{{ $experience->id }}">More
                                                    info</a>
                                            </div>
                                            <!-- Modal Section -->
                                            <div class="modal fade custom-modal-open-experience-{{ $experience->id }}"
                                                 aria-hidden="true"
                                                 aria-labelledby="experienceModalToggleLabel" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content text-dark">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold text-uppercase"
                                                                id="experienceModalToggleLabel">
                                                                @if(!empty($experience->fa_icon))
                                                                    <span
                                                                        class="text-primary">{!! $experience->fa_icon !!}</span>
                                                                @endif
                                                                {{ $experience->organization }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="container">
                                                                <div class="row gy-4">
                                                                    <div class="col-12">
                                                                        <div class="card card-info">
                                                                            <div class="card-header fw-bold text-muted">
                                                                                Experience/Education details
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <ul class="list-group list-group-flush">
                                                                                    <li class="list-group-item"><b
                                                                                            class="text-muted">Designation: </b><b>{{ $experience->designation }}</b>
                                                                                    </li>
                                                                                    <li class="list-group-item"><b
                                                                                            class="text-muted">Jobs/Responsibilities: </b>{{ $experience->responsibilities }}
                                                                                    </li>
                                                                                    <li class="list-group-item"><b
                                                                                            class="text-muted">Spent
                                                                                            Time: </b>
                                                                                        {{ \Carbon\Carbon::parse($experience->join_date)->format('M d, Y') }}
                                                                                        to @if(!empty($experience->resign_date))
                                                                                            {{ \Carbon\Carbon::parse($experience->resign_date)->format('M d, Y') }}
                                                                                        @else
                                                                                            Continuing
                                                                                        @endif
                                                                                    </li>
                                                                                    <li class="list-group-item"><b
                                                                                            class="text-muted">Descriptions: </b>{{ $experience->descriptions }}
                                                                                    </li>
                                                                                </ul>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Modal Section -->
                                        </div>
                                    @endforeach
                                </div>
                                <!-- Timeline end -->
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Resume Section -->
            </div>

        </div>
    @endif
</section>
<!-- End Portfolio Section -->
