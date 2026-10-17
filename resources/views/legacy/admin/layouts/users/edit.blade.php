@extends('legacy.admin.app')
@section('page_title', 'Users Edit')
@section('bread_camp', 'User Edit')
@section('action_url', url('users/'.$user->id))
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
                                        <input type="text" name="name" class="form-control name required_validation" value="{{ $user->name }}" placeholder="Enter user name" />
                                    </div>
                                    <div class="form-group">
                                        <label>Role <span class="text-danger font-weight-bold">*</span></label>
                                        <select class="form-control select2 role_id required_validation" name="role_id">
                                            <option value="">Select One</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @if($role->id == $user->role_id) selected @endif>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="email" name="email" class="form-control email required_validation" value="{{ $user->email }}" placeholder="Enter your email" />
                                    </div>
                                    <div class="form-group">
                                        <label for="password">Password <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="password" name="password" class="form-control password required_validation" placeholder="Enter your password" />
                                    </div>
                                    <div class="form-group">
                                        <label for="retype_password">Confirm Password <span class="text-danger font-weight-bold">*</span></label>
                                        <input type="password" name="password_confirmation" class="form-control retype_password required_validation" placeholder="Retype your password" />
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="is_checked" id="is_checked" class="custom-control-input is_checked" @if($user->status) checked="checked" @endif />
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
