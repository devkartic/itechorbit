@extends('legacy.admin.app')
@section('page_title', 'Service Edit')
@section('bread_camp', 'Service Edit')
@section('action_url', url('services/'.$service->id))
@section('content')
    <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- left column -->
                        <div class="col-md-12 px-0">
                            <!-- jquery validation -->
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">All <span class="text-danger font-weight-bold">*</span><small> Marks filed is required.</small></h3>
                                </div>
                                <!-- /.card-header -->
                                <!-- form start -->
                                <form class="form-submit" method="post" action="@yield('action_url', '')">
                                    @csrf
                                    @method('PATCH')
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">Name <span class="text-danger font-weight-bold">*</span></label>
                                                    <input type="text" name="name" class="form-control name required_validation" value="{{ $service->name }}" placeholder="Enter service name" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="fa_icon">Fa-Icon <a class="badge badge-warning ml-md-1" href="https://fontawesome.com/search" target="_blank">Search Icon</a></label>
                                                    <input type="text" name="fa_icon" class="form-control fa_icon" value="{{ $service->fa_icon }}" placeholder="Example: <i class='fa-brands fa-facebook'></i>" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="service_url">Service Demo URL</label>
                                                    <input type="text" name="service_url" class="form-control service_url" value="{{ $service->service_url }}" placeholder="Example: https://www.example.com/" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="order_number">Order Number</label>
                                                    <input type="text" name="order_number" class="form-control order_number required_validation" value="{{ $service->order_number }}" placeholder="Enter order number" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="title">Title <span class="text-danger font-weight-bold">*</span></label>
                                            <input type="text" name="title" class="form-control title required_validation" value="{{ $service->title }}" placeholder="Enter service title" />
                                        </div>
                                        <div class="form-group">
                                            <label for="about_service">About Service <span class="text-danger font-weight-bold">*</span></label>
                                            <textarea type="text" name="about_service" class="form-control about_service required_validation" rows="3">{{ $service->about_service }}</textarea>
                                        </div>
                                        <div class="form-group mb-0">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked" @if($service->status) checked="checked" @endif />
                                                <label class="custom-control-label" for="is_checked">Is Active</label>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.card-body -->
                                    <div class="card-footer pt-0">
                                        <button type="button" onclick="return formSubmit(this.form)" class="btn btn-outline-primary px-5">Update</button>
                                    </div>
                                </form>
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
