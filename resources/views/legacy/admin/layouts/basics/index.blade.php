@extends('legacy.admin.app')
@section('page_title', 'Basic Information')
@section('bread_camp', 'Basic Information')
@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">Default features</div>
            <div class="card-title float-right">
                @if(empty($basic))
                    <a href="{{ url('basics/create') }}" class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-xs btn-outline-primary">Basic information create</button>
                    </a>
                @else
                    <a href="{{ url('basics/'.$basic->id.'/edit') }}" class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-xs btn-warning">Basic information edit</button>
                    </a>
                @endif
            </div>
        </div>
        <!-- /.card-header -->
        @if(!empty($basic))
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
                                            @if(!empty($basic->profile_image_path) && file_exists($basic->profile_image_path))
                                                <img class="profile-user-img img-fluid img-circle"
                                                     src="{{ asset($basic->profile_image_path) }}" alt="User profile picture"/>
                                            @else
                                                <img class="profile-user-img img-fluid img-circle"
                                                     src="{{ asset('admin/dist/img/user-avatar.webp') }}"
                                                     alt="User profile picture"/>
                                            @endif
                                        </div>

                                        <h3 class="profile-username text-center">{{ $basic->user->name }}</h3>
                                        <p class="text-muted text-center">{{ $basic->user->role->name }}</p>

                                        <div class="col-12 btn btn-default btn-block d-flex" title="Portfolio ID">
                                            <div class="text-bold">ID:</div>
                                            <div class="ml-auto text-bold">
                                                @if(!empty($basic->user->portfolio_id))
                                                    <a href="{{ url('portfolios/'.$basic->user->portfolio_id) }}" target="_blank">{{ $basic->user->portfolio_id }}</a>
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
                                        <strong><i class="fas fa-envelope-open mr-1"></i> {{ $basic->user->email }}
                                        </strong>
                                        <hr/>
                                        <strong><i class="fas fa-mobile-alt mr-1"></i> {{ $basic->mobile }}</strong>
                                        <hr/>
                                        <strong><i class="fas fa-map-marker-alt mr-1"></i> {{ $basic->address }}
                                        </strong>
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
                                                        <strong><i class="fas fa-user-alt mr-1"></i> Full Name</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->first_name }} {{ $basic->last_name }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-map-marker-alt mr-1"></i>
                                                            Location</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->address }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-mobile-alt mr-1"></i> Mobile</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->mobile }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-check-circle mr-1"></i> Gender</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->gender }}</p>
                                                        <hr>

                                                        <strong><i class="far fa-file-alt mr-1"></i> Marital
                                                            Status</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->marital_status }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-check-circle mr-1"></i> Date of
                                                            Birth</strong>
                                                        <p class="text-muted text-capitalize">{{ date('d-M-Y', strtotime($basic->birth_date)) }}</p>

                                                    </div>
                                                    <!-- /.card-body -->
                                                </div>
                                            </div>
                                            <!-- /.tab-pane -->
                                            <div class="tab-pane" id="self_introduction">
                                                <div class="card card-primary">
                                                    <div class="card-body">
                                                        <strong><i class="fas fa-info-circle mr-1"></i> About
                                                            Me</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->self_introduction }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-info-circle mr-1"></i> Additional
                                                            Information</strong>
                                                        <p class="text-muted text-capitalize">{{ $basic->additional_information }}</p>
                                                        <hr>

                                                    </div>
                                                    <!-- /.card-body -->
                                                </div>
                                            </div>
                                            <!-- /.tab-pane -->
                                            <div class="tab-pane" id="career_information">
                                                <div class="card card-primary">
                                                    <div class="card-body">
                                                        <strong><i class="fas fa-id-badge mr-1"></i>
                                                            Designation</strong>
                                                        <p class="text-muted">{{ $basic->designation }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-briefcase mr-1"></i> Service</strong>
                                                        <p class="text-muted">{{ $basic->service_offering_title }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-eye mr-1"></i> Mission &
                                                            Vision</strong>
                                                        <p class="text-muted">{{ $basic->career_objective }}</p>
                                                        <hr>

                                                        <strong><i class="fas fa-eye mr-1"></i> Experience</strong>
                                                        <p class="text-muted text-bold">{{ $basic->experience_year }}
                                                            years.</p>
                                                        <hr>

                                                        <strong><i class="fas fa-file-alt mr-1"></i> Curriculum
                                                            Vitae</strong>
                                                        <div class="text-muted mt-2">
                                                            @if($basic->cv_path)
                                                                <a type="button" href="{{ asset($basic->cv_path) }}"
                                                                   class="btn btn-sm btn-outline-primary mr-1"
                                                                   download="curriculum_vitae">Download</a>
                                                                <a type="button" href="{{ asset($basic->cv_path) }}"
                                                                   class="btn btn-sm btn-outline-info" target="_blank">Open
                                                                    with
                                                                    new window</a>
                                                            @else
                                                                <p class="text-muted">Not available!</p>
                                                            @endif
                                                        </div>

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
        @else
            <div class="card-body text-center">
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <p class="h4 card-text text-danger my-5">Data not available!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        @endif
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection

