@extends('legacy.admin.app')
@section('page_title', 'Experience View')
@section('bread_camp', 'Experience View')
@section('content')
    <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 px-0">
                            <div class="card card-primary">
                                <div class="card-body">
                                    <strong><i class="fas fa-id-card mr-1"></i> Designation</strong>
                                    <p class="text-muted text-capitalize pl-4">{{ $experience->designation }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Responsibilities</strong>
                                    <p class="text-muted pl-4">{{ $experience->responsibilities }}</p>
                                    <hr>

                                    <strong><i class="fas fa-briefcase mr-1"></i>Organization</strong>
                                    <p class="text-muted pl-4">{{ $experience->organization }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Icon</strong>
                                    <div class="pl-4">{!! $experience->fa_icon !!}</div>
                                    <hr>

                                    <strong><i class="fas fa-calendar mr-1"></i> Join Date</strong>
                                    <p class="text-muted pl-4">{{ date('d M, Y', strtotime($experience->join_date)) }}</p>
                                    <hr>

                                    <strong><i class="fas fa-calendar mr-1"></i> Resign Date</strong>
                                    <div class="text-muted pl-4 mt-2">
                                        @if($experience->resign_date)
                                            {{ date('d M, Y', strtotime($experience->resign_date)) }}
                                        @else
                                            <span class="badge badge-warning p-1">Service continuing at present</span>
                                        @endif
                                    </div>
                                    <hr>

                                    <strong><i class="fas fa-sort mr-1"></i> Order Number</strong>
                                    <p class="text-muted pl-4">{{ $experience->order_number }}</p>
                                    <hr>

                                    <strong><i class="fas fa-toggle-on mr-1"></i> Status</strong>
                                    <div class="mt-2 pl-4">
                                        @if($experience->status)
                                            <h4 class="badge bg-primary">Enable</h4>
                                        @else
                                            <h4 class="badge bg-danger">Disable</h4>
                                        @endif
                                    </div>

                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!--/.col (left) -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </section>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection
