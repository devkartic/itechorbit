@extends('legacy.admin.app')
@section('page_title', 'Basic Information Create')
@section('bread_camp', 'Basic Information Create')
@section('action_url', url('basics'))
@include('legacy.admin.includes.datepicker')
@include('legacy.admin.includes.custom_file_input')
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
                            <form class="form-submit" method="post" action="@yield('action_url', '')" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="first_name">First Name <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="first_name" class="form-control first_name required_validation" placeholder="Enter your first name" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="last_name">Last Name <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="last_name" class="form-control last_name required_validation" placeholder="Enter your last name" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="designation">Designation <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="designation" class="form-control designation required_validation" placeholder="Enter your designation" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="mobile">Mobile <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="mobile" class="form-control mobile required_validation" placeholder="Enter your mobile number" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="service_offering_title">Service Offering Title <span class="text-danger font-weight-bold">*</span></label>
                                                <textarea type="text" name="service_offering_title" class="form-control service_offering_title required_validation" rows="2">Example: I'm offering you ...</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="self_introduction">Self Introduction <span class="text-danger font-weight-bold">*</span></label>
                                                <textarea type="text" name="self_introduction" class="form-control self_introduction required_validation" rows="2">Example: Myself...</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="career_objective">Career Objective <span class="text-danger font-weight-bold">*</span></label>
                                                <textarea type="text" name="career_objective" class="form-control career_objective required_validation" rows="5">Example: To build up a career...</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="additional_information">Self Additional Information <span class="text-danger font-weight-bold">*</span></label>
                                                <textarea type="text" name="additional_information" class="form-control additional_information required_validation" rows="5">Example: My hobby is playing football...</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Address <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="text" name="address" class="form-control address required_validation" aria-placeholder="Enter your present address"/>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="gender">Gender</label>
                                                <div class="row pl-2">
                                                    <div class="custom-control custom-radio mr-2">
                                                        <input class="custom-control-input" type="radio" id="male" name="gender" value="male" checked />
                                                        <label for="male" class="custom-control-label">Male</label>
                                                    </div>
                                                    <div class="custom-control custom-radio mr-2">
                                                        <input class="custom-control-input" type="radio" id="female" name="gender" value="female" />
                                                        <label for="female" class="custom-control-label">Female</label>
                                                    </div>
                                                    <div class="custom-control custom-radio">
                                                        <input class="custom-control-input" type="radio" id="others" name="gender" value="others" />
                                                        <label for="others" class="custom-control-label">Others</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="marital_status">Marital Status</label>
                                                <div class="row pl-2">
                                                    <div class="custom-control custom-radio mr-2">
                                                        <input class="custom-control-input" type="radio" id="single" name="marital_status" value="single" checked />
                                                        <label for="single" class="custom-control-label">Single</label>
                                                    </div>
                                                    <div class="custom-control custom-radio mr-2">
                                                        <input class="custom-control-input" type="radio" id="married" name="marital_status" value="married" />
                                                        <label for="married" class="custom-control-label">Married</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="birth_date">Birth Date <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="birth_date" class="form-control date datepicker birth_date required_validation" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="experience_year">Experience <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" name="experience_year" class="form-control experience_year required_validation" placeholder="Enter your experience" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="uploaded_banner_image">Upload Banner <span class="badge bg-warning text-wrap">Recommend size 800x600 px</span></label>
                                                <div class="input-group uploaded_banner_image">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="uploaded_banner_image" name="uploaded_banner_image">
                                                        <label class="custom-file-label" for="uploaded_banner_image">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="uploaded_profile_image">Upload Image <span class="badge bg-warning text-wrap">Recommend size 800x600 px</span></label>
                                                <div class="input-group">
                                                    <div class="custom-file uploaded_profile_image">
                                                        <input type="file" class="custom-file-input" id="uploaded_profile_image" name="uploaded_profile_image">
                                                        <label class="custom-file-label" for="uploaded_profile_image">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="uploaded_cv">Upload CV</label>
                                                <div class="input-group uploaded_cv">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="uploaded_cv" name="uploaded_cv">
                                                        <label class="custom-file-label" for="uploaded_cv">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mt-3">
                                                <div class="custom-control custom-checkbox pt-4">
                                                    <input type="checkbox" name="cv_public_status"
                                                           id="cv_public_status"
                                                           class="custom-control-input cv_public_status" />
                                                    <label class="custom-control-label" for="cv_public_status">CV
                                                        Share as Public</label>
                                                </div>
                                            </div>
                                        </div>
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
