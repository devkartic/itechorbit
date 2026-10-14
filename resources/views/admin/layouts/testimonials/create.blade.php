@extends('admin.app')
@section('page_title', 'Testimonial Create')
@section('bread_camp', 'Testimonial Create')
@section('action_url', url('testimonials'))
@include('admin.includes.datepicker')
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
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Name <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="name" class="form-control name required_validation" placeholder="Enter name" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="designation">Designation <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="designation" class="form-control designation required_validation" placeholder="Enter designation name" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="organization">Organization <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="organization" class="form-control organization required_validation" placeholder="Enter organization name" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="email" name="email" class="form-control email required_validation" placeholder="Enter your email" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="mobile">Mobile <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="mobile" class="form-control mobile required_validation" placeholder="Enter your mobile" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ratings">Ratings <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="ratings" class="form-control ratings required_validation" placeholder="Enter your ratings" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="order_number">Order Number</label>
                                                <input type="text" name="order_number" class="form-control order_number required_validation" placeholder="Enter order number" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="comment">Comment <span class="text-danger font-weight-bold">*</span></label>
                                        <textarea type="text" name="comment" class="form-control comment required_validation" rows="3">Leave your comment in shortly.</textarea>
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked" checked="checked" />
                                            <label class="custom-control-label" for="is_checked">Is Active</label>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                                <div class="card-footer pt-0">
                                    <button type="button" onclick="return formSubmit(this.form)" class="btn btn-outline-primary px-5">Save</button>
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
