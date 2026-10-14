@extends('admin.app')
@section('page_title', 'Experiences Create')
@section('bread_camp', 'Experiences Create')
@section('action_url', url('experiences'))
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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="designation">Designation <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="designation" class="form-control designation required_validation" placeholder="Enter designation name" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fa_icon">Fa-Icon <a class="badge badge-warning ml-md-1" href="https://fontawesome.com/search" target="_blank">Search Icon</a></label>
                                                <input type="text" name="fa_icon" class="form-control fa_icon" placeholder="Example: <i class='fa-brands fa-facebook'></i>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="organization">Organization <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="organization" class="form-control organization required_validation" placeholder="Enter organization name" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="order_number">Order Number</label>
                                                <input type="text" name="order_number" class="form-control order_number required_validation" placeholder="Enter order number" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="order_number">Jobs / Responsibilities</label>
                                                <input type="text" name="responsibilities" class="form-control responsibilities" placeholder="Enter Jobs / Responsibilities" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="join_date">Join Date <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="join_date" class="form-control datepicker join_date required_validation" placeholder="Enter join date" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="custom-control custom-checkbox mt-lg-4 mb-1 pt-lg-3 text-lg-right">
                                                <input type="checkbox" name="is_continuing" id="is_continuing" class="custom-control-input is_continuing" />
                                                <label class="custom-control-label" for="is_continuing">Is Continuing</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="resign_date">Resign Date <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="resign_date" class="form-control datepicker resign_date required_validation" placeholder="Enter resign date" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="descriptions">Descriptions </label>
                                        <textarea type="text" name="descriptions" class="form-control descriptions" rows="3">Experience/Education details in shortly.</textarea>
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
