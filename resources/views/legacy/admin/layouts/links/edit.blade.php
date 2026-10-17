@extends('legacy.admin.app')
@section('page_title', 'Link Edit')
@section('bread_camp', 'Link Edit')
@section('action_url', url('links/'.$link->id))
@include('legacy.admin.includes.select2')
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
                                        <label for="name">Name <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="text" name="name" class="form-control name required_validation" value="{{ $link->name }}" placeholder="Enter link name" />
                                    </div>
                                    <div class="form-group">
                                        <label for="link_url">Link URL <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="text" name="link_url" class="form-control link_url required_validation" value="{{ $link->url }}" placeholder="Enter link url" />
                                    </div>
                                    <div class="form-group">
                                        <label>Module <span class="text-danger font-weight-bold">*</span></label>
                                        <select class="form-control select2 module_id required_validation" name="module_id">
                                            <option value="">Select One</option>
                                            @foreach($modules as $module)
                                                <option value="{{ $module->id }}" @if($module->id==$link->module_id) selected @endif >{{ $module->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="fa_icon">Fa-Icon <a class="badge badge-warning ml-md-1" href="https://fontawesome.com/search" target="_blank">Search Icon</a></label>
                                        <input type="text" name="fa_icon" class="form-control fa_icon" value="{{ $link->fa_icon }}" placeholder="Example: <i class='fa-brands fa-facebook'></i>" />
                                    </div>
                                    <div class="form-group">
                                        <label for="order_number">Order Number</label>
                                        <input type="text" name="order_number" class="form-control order_number required_validation" value="{{ $link->order_number }}" placeholder="Enter order number" />
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked" @if($link->status) checked="checked" @endif />
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
