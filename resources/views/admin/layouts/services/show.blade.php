@extends('admin.app')
@section('page_title', 'Service View')
@section('bread_camp', 'Service View')
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
                                    <strong><i class="fa-brands fa-servicestack mr-1"></i> Name</strong>
                                    <p class="text-muted text-capitalize pl-4">{{ $service->name }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Title</strong>
                                    <p class="text-muted pl-4">{{ $service->title }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>About Service</strong>
                                    <p class="text-muted pl-4">{{ $service->about_service }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Icon</strong>
                                    <div class="pl-4">{!! $service->fa_icon !!}</div>
                                    <hr>

                                    <strong><i class="fas fa-link mr-1"></i> Service URL</strong>
                                    <p class="text-muted pl-4">{{ $service->service_url }}</p>
                                    <hr>

                                    <strong><i class="fas fa-sort mr-1"></i> Order Number</strong>
                                    <p class="text-muted pl-4">{{ $service->order_number }}</p>
                                    <hr>

                                    <strong><i class="fas fa-toggle-on mr-1"></i> Status</strong>
                                    <div class="mt-2 pl-4">
                                        @if($service->status)
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
