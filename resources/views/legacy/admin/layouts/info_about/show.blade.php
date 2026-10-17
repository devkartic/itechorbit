@extends('legacy.admin.app')
@section('page_title', 'About View')
@section('bread_camp', 'About View')
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
                                    <strong><i class="fas fa-info-circle mr-1"></i> Title</strong>
                                    <p class="text-muted pl-4">{{ $aboutInfo->title }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Subtitle</strong>
                                    <p class="text-muted pl-4">{{ $aboutInfo->subtitle }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i> Descriptions</strong>
                                    <div class="text-muted pl-4">{!! $aboutInfo->descriptions !!}</div>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i> Status</strong>
                                    <div class="mt-2 pl-4">
                                        @if($aboutInfo->status)
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
