@extends('admin.app')
@section('page_title', 'Testimonial View')
@section('bread_camp', 'Testimonial View')
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
                                    <strong><i class="fas fa-user mr-1"></i>Name</strong>
                                    <p class="text-muted text-capitalize pl-4">{{ $testimonial->name }}</p>
                                    <hr>

                                    <strong><i class="fas fa-id-card mr-1"></i>Designation</strong>
                                    <p class="text-capitalize text-muted pl-4">{{ $testimonial->designation }}</p>
                                    <hr>

                                    <strong><i class="fas fa-briefcase mr-1"></i>Organization</strong>
                                    <p class="text-uppercase text-muted pl-4">{{ $testimonial->organization }}</p>
                                    <hr>

                                    <strong><i class="fa-solid fa-envelope mr-1"></i>Email</strong>
                                    <p class="text-muted pl-4">{{ $testimonial->email }}</p>
                                    <hr>

                                    <strong><i class="fas fa-mobile mr-1"></i>Mobile</strong>
                                    <p class="text-muted pl-4">{{ $testimonial->mobile }}</p>
                                    <hr>

                                    <strong><i class="fas fa-info-circle mr-1"></i>Rating</strong>
                                    <div class="pl-4 mt-2 text-primary">
                                        @if(!empty($testimonial->ratings))
                                            @for($rating = 0; $rating < $testimonial->ratings; $rating++)
                                                <i class="fa-solid fa-star"></i>
                                            @endfor
                                        @endif
                                    </div>
                                    <hr>

                                    <strong><i class="fas fa-message mr-1"></i>Comment</strong>
                                    <p class="text-muted pl-4">{{ $testimonial->comment }}</p>
                                    <hr>

                                    <strong><i class="fas fa-sort mr-1"></i> Order Number</strong>
                                    <p class="text-muted pl-4">{{ $testimonial->order_number }}</p>
                                    <hr>

                                    <strong><i class="fas fa-toggle-on mr-1"></i> Status</strong>
                                    <div class="mt-2 pl-4">
                                        @if($testimonial->status)
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
