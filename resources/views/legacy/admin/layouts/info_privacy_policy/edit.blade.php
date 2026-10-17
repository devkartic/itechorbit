@extends('legacy.admin.app')
@section('page_title', 'Privacy & Policy Edit')
@section('bread_camp', 'Privacy & Policy Edit')
@section('action_url', url('privacy-policies/'.$privacyPolicy->id))

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
                                    <div class="form-group">
                                        <label for="title">Title <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="text" name="title" class="form-control title required_validation" value="{{ $privacyPolicy->title }}" placeholder="Enter category title" />
                                    </div>
                                    <div class="form-group">
                                        <label for="subtitle">Subtitle <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="text" name="subtitle" class="form-control subtitle required_validation" value="{{ $privacyPolicy->subtitle }}" placeholder="Enter category subtitle" />
                                    </div>
                                    <div class="form-group">
                                        <label for="descriptions">Descriptions <span class="text-danger font-weight-bold">*</span></label>
                                        <textarea type="text" name="descriptions" class="form-control text-editor descriptions required_validation" rows="5">{{ $privacyPolicy->descriptions }}</textarea>
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked"  @if($privacyPolicy->status) checked="checked" @endif />
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
