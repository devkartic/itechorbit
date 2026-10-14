@extends('admin.app')
@section('page_title', 'Project Edit')
@section('bread_camp', 'Project Edit')
@section('action_url', url('projects/'.$project->id))
@include('admin.includes.select2')
@include('admin.includes.custom_file_input')
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
                                                    <label for="name">Application Name <span class="text-danger font-weight-bold">*</span></label>
                                                    <input type="text" name="name" class="form-control name required_validation" value="{{ $project->name }}" placeholder="Enter project name" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="client_name">Client Name <span class="text-danger font-weight-bold">*</span></label>
                                                    <input type="text" name="client_name" class="form-control client_name required_validation" value="{{ $project->client_name }}" placeholder="Enter client name" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Based on Technology/Skill <span class="text-danger font-weight-bold">*</span></label>
                                                    <select class="form-control select2 skill_id required_validation" name="skill_id">
                                                        <option value="">Select One</option>
                                                        @foreach($skills as $skill)
                                                            <option value="{{ $skill->id }}" @if($project->skill_id == $skill->id) selected @endif >{{ $skill->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-group">
                                                        <label for="played_role">Played Role</label>
                                                        <input type="text" name="played_role" class="form-control played_role" value="{{ $project->played_role }}" placeholder="Lead/Engineer" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-group">
                                                        <label for="project_category">Project Category</label>
                                                        <input type="text" name="project_category" class="form-control project_category" value="{{ $project->project_category }}" placeholder="Example: Ecommerce, ERP"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-group">
                                                        <label for="jobs">Job/Responsibility</label>
                                                        <input type="text" name="jobs" class="form-control jobs" value="{{ $project->jobs }}" placeholder="Example: Design and Development"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="technology_used">Technology Used</label>
                                            <select class="form-control select2 technology_used" multiple="multiple" name="technology_used[]" data-placeholder="Select used technologies">
                                                @foreach($skills as $skill)
                                                    <option value="{{ $skill->id }}" @if(!empty($project->technology_used) && in_array($skill->name, json_decode($project->technology_used))) selected @endif>{{ $skill->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="project_url">Project URL</label>
                                                    <input type="text" name="project_url" class="form-control project_url" value="{{ $project->project_url }}" placeholder="Example: https://www.example.com/" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="order_number">Order Number</label>
                                                    <input type="text" name="order_number" class="form-control order_number required_validation" value="{{ $project->order_number }}" placeholder="Enter order number" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="about_project">About Project <span class="text-danger font-weight-bold">*</span></label>
                                            <textarea type="text" name="about_project" class="form-control about_project required_validation" rows="3">{{ $project->about_project }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="uploaded_image">Upload Image</label>
                                            <div class="input-group uploaded_image">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="uploaded_image" name="uploaded_image">
                                                    <label class="custom-file-label" for="uploaded_image">Choose file</label>
                                                </div>
                                            </div>
                                            @if(file_exists($project->image_path))
                                                <div class="uploaded-file-view input-group my-2">
                                                    <img class="mt-2" src="{{ asset($project->image_path) }}" height="100px" width="100px" style="border: 1px solid #9de0f6;" alt="Image not available"/>
                                                    <label class="m-2">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-id="{{ $project->id }}" data-field="image_path" data-action="{{ url('project-file-delete') }}" onclick="return form_file_delete($(this));" title="Delete file"><i class="fa-solid fa-trash"></i></button>
                                                    </label>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group mb-0">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked" @if($project->status) checked="checked" @endif />
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
