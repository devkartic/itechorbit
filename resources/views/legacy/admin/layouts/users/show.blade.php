@extends('legacy.admin.app')
@section('page_title', 'Users Profile')
@section('bread_camp', 'User Profile')
@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">Default features</div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-3">
                            <!-- Profile Image -->
                            <div class="card card-primary card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        @if(!empty($user->basic->profile_image_path) && file_exists($user->basic->profile_image_path))
                                            <img class="profile-user-img img-fluid img-circle"
                                                 src="{{ asset($user->basic->profile_image_path) }}"
                                                 alt="User profile picture"/>
                                        @else
                                            <img class="profile-user-img img-fluid img-circle"
                                                 src="{{ asset('admin/dist/img/user-avatar.webp') }}"
                                                 alt="User profile picture"/>
                                        @endif
                                    </div>

                                    <h3 class="profile-username text-center">{{ $user->name }}</h3>
                                    <p class="text-muted text-center">{{ $user->role->name }}</p>

                                    <div class="col-12 btn btn-default btn-block d-flex" title="Portfolio ID">
                                        <div class="text-bold">ID:</div>
                                        <div class="ml-auto text-bold">
                                            @if(!empty($user->portfolio_id))
                                                <a href="{{ url('portfolios/'.$user->portfolio_id) }}"
                                                   target="_blank">{{ $user->portfolio_id }}</a>
                                            @else
                                                <p class="text-danger">Not Available!</p>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->

                            <!-- About Me Box -->
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Contact Information</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <strong><i class="fas fa-envelope-open mr-1"></i> {{ $user->email }}
                                    </strong>
                                    @if(!empty($user->basic))
                                        <hr/>
                                        <strong><i class="fas fa-mobile-alt mr-1"></i> {{ $user->basic->mobile }}
                                        </strong>
                                        <hr/>
                                        <strong><i class="fas fa-map-marker-alt mr-1"></i> {{ $user->basic->address }}
                                        </strong>
                                    @endif
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                        <div class="col-md-9">
                            <div class="card">
                                <div class="card-header p-2">
                                    <ul class="nav nav-pills">
                                        <li class="nav-item"><a class="nav-link active" href="#basic_information"
                                                                data-toggle="tab">Basic Information</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#self_introduction"
                                                                data-toggle="tab">Self Introduction</a></li>
                                        <li class="nav-item"><a class="nav-link" href="#career_information"
                                                                data-toggle="tab">Career Information</a></li>
                                    </ul>
                                </div><!-- /.card-header -->
                                <div class="card-body">
                                    <div class="tab-content">
                                        <div class="active tab-pane" id="basic_information">
                                            <div class="card card-primary">
                                                <div class="card-body">
                                                    @if(!empty($user->basic))
                                                        <strong><i class="fas fa-user-alt mr-1"></i> Full Name</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->first_name }} {{ $user->basic->last_name }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-map-marker-alt mr-1"></i>
                                                            Location</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->address }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-mobile-alt mr-1"></i> Mobile</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->mobile }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-check-circle mr-1"></i> Gender</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->gender }}</p>
                                                        <hr>

                                                        <strong><i class="far fa-file-alt mr-1"></i> Marital
                                                            Status</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->marital_status }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-check-circle mr-1"></i> Date of
                                                            Birth</strong>
                                                        <p class="text-muted text-capitalize">{{ date('d-M-Y', strtotime($user->basic->birth_date)) }}</p>
                                                    @else
                                                        <p class="h6 card-text text-danger">There is no basic
                                                            information available! <a type="button"
                                                                                      class="btn btn-xs btn-outline-primary ml-1"
                                                                                      href="{{ url('basics/create') }}">Create
                                                                Now</a></p>
                                                    @endif
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                        <!-- /.tab-pane -->
                                        <div class="tab-pane" id="self_introduction">
                                            <div class="card card-primary">
                                                <div class="card-body">
                                                    @if(!empty($user->basic))
                                                        <strong><i class="fas fa-info-circle mr-1"></i> About
                                                            Me</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->self_introduction }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-info-circle mr-1"></i> Additional
                                                            Information</strong>
                                                        <p class="text-muted text-capitalize">{{ $user->basic->additional_information }}</p>
                                                        <hr>
                                                    @else
                                                        <p class="h6 card-text text-danger">There is no basic
                                                            information available! <a type="button"
                                                                                      class="btn btn-xs btn-outline-primary ml-1"
                                                                                      href="{{ url('basics/create') }}">Create
                                                                Now</a></p>
                                                    @endif
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                        <!-- /.tab-pane -->
                                        <div class="tab-pane" id="career_information">
                                            <div class="card card-primary">
                                                <div class="card-body">
                                                    @if(!empty($user->basic))
                                                        <strong><i class="fas fa-id-badge mr-1"></i>
                                                            Designation</strong>
                                                        <p class="text-muted">{{ $user->basic->designation }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-briefcase mr-1"></i> Service</strong>
                                                        <p class="text-muted">{{ $user->basic->service_offering_title }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-eye mr-1"></i> Mission &
                                                            Vision</strong>
                                                        <p class="text-muted">{{ $user->basic->career_objective }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-eye mr-1"></i> Experience</strong>
                                                        <p class="text-muted text-bold">{{ $user->basic->experience_year }}
                                                            years.</p>
                                                        <hr>

                                                        <strong><i class="fas fa-file-alt mr-1"></i> Curriculum
                                                            Vitae</strong>
                                                        <div class="text-muted mt-2">
                                                            @if(!empty($user->basic->cv_path))
                                                                <a type="button"
                                                                   href="{{ asset($user->basic->cv_path) }}"
                                                                   class="btn btn-sm btn-outline-primary mr-1"
                                                                   download="curriculum_vitae">Download</a>
                                                                <a type="button"
                                                                   href="{{ asset($user->basic->cv_path) }}"
                                                                   class="btn btn-sm btn-outline-info mr-1" target="_blank">Open
                                                                    with
                                                                    new window</a>
                                                                <button type="button" disabled
                                                                   class="btn btn-sm btn-outline-secondary mr-1">@if($user->basic->cv_public_status)
                                                                        <span class="text-success text-bold">Shared</span>
                                                                    @else
                                                                        <span class="text-danger text-bold">Not shared</span>
                                                                    @endif in your Portfolio</button>
                                                            @else
                                                                <p class="text-muted">Not available!</p>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <p class="h6 card-text text-danger">There is no basic
                                                            information available! <a type="button"
                                                                                      class="btn btn-xs btn-outline-primary ml-1"
                                                                                      href="{{ url('basics/create') }}">Create
                                                                Now</a></p>
                                                    @endif
                                                </div>
                                                <!-- /.card-body -->
                                            </div>
                                        </div>
                                        <!-- /.tab-pane -->
                                    </div>
                                    <!-- /.tab-content -->
                                </div><!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection
