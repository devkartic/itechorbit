@extends('admin.app')
@section('page_title', 'Project View')
@section('bread_camp', 'Project View')
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
                                    <strong><i class="fa-brands fa-r-project mr-1"></i> Project Name</strong>
                                    <p class="text-muted text-capitalize pl-4">{{ $project->name }}</p>
                                    <hr>

                                    <strong><i class="fas fa-user mr-1"></i>Client Name</strong>
                                    <p class="text-muted pl-4">{{ $project->client_name }}</p>
                                    <hr>

                                    <strong><i class="fas fa-link mr-1"></i>Project URL</strong>
                                    <p class="text-muted pl-4">{{ $project->project_url }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>About Project</strong>
                                    <p class="text-muted pl-4">{{ $project->about_project }}</p>
                                    <hr>

                                    <strong><i class="fas fa-sort mr-1"></i> Order Number</strong>
                                    <p class="text-muted pl-4">{{ $project->order_number }}</p>
                                    <hr>

                                    <strong><i class="fas fa-toggle-on mr-1"></i> Status</strong>
                                    <div class="mt-2 pl-4">
                                        @if($project->status)
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
