@extends('admin.app')
@section('page_title', 'Post Create')
@section('bread_camp', 'Post Create')
@section('action_url', url('posts'))
@include('admin.includes.select2')
@include('admin.includes.tinymce_editor')
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
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Post Category <span class="text-danger font-weight-bold">*</span></label>
                                                <select class="form-control select2 category_id required_validation" name="category_id">
                                                    <option value="">Select One</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tags</label>
                                                <select class="form-control select2 tags required_validation" multiple="multiple" name="tags[]" data-placeholder="Select tags">
                                                    @foreach($tags as $tag)
                                                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="title">Post Title <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="text" name="title" class="form-control title required_validation" placeholder="Enter your post title" />
                                    </div>
                                    <div class="form-group">
                                        <label for="body">Post Descriptions <span class="text-danger font-weight-bold">*</span></label>
                                        <textarea class="form-control text-editor body required_validation" name="body" rows="3">Place descriptions here</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="codebase">Codebase</label>
                                        <textarea class="form-control text-editor codebase" name="codebase" rows="5">Coding place here</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="uploaded_image">Upload Image</label>
                                        <div class="input-group uploaded_image">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="uploaded_image" name="uploaded_image">
                                                <label class="custom-file-label" for="uploaded_image">Choose file</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 form-group mt-2">
                                        <div class="row">
                                            <div class="col-lg-2 col-md-2 custom-control custom-checkbox">
                                                <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked" checked="checked" />
                                                <label class="custom-control-label" for="is_checked">Is Active</label>
                                            </div>
                                            <div class="col-lg-2 col-md-2 custom-control custom-checkbox">
                                                <input type="checkbox" name="is_private" id="is_private" class="custom-control-input is_private" />
                                                <label class="custom-control-label" for="is_private">Is Private</label>
                                            </div>
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
