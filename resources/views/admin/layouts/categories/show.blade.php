@extends('admin.app')
@section('page_title', 'Category View')
@section('bread_camp', 'Category View')
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
                                    <strong><i class="fas fa-info-circle mr-1"></i> Name</strong>
                                    <p class="text-muted text-capitalize pl-4">{{ $category->name }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Title</strong>
                                    <p class="text-muted pl-4">{{ $category->title }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Icon</strong>
                                    <div class="pl-4">{!! $category->fa_icon !!}</div>
                                    <hr>

                                    <strong><i class="fas fa-sort mr-1"></i> Order Number</strong>
                                    <p class="text-muted pl-4">{{ $category->order_number }}</p>
                                    <hr>

                                    <strong><i class="fas fa-toggle-on mr-1"></i> Status</strong>
                                    <div class="mt-2 pl-4">
                                        @if($category->status)
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
