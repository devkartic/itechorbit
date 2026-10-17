@extends('legacy.admin.app')
@section('page_title', 'Permissions Control')
@section('bread_camp', 'Permissions Control')
@include('legacy.admin.includes.select2')
@include('legacy.admin.includes.jquery_datatable')
@section('content')
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Roles</label>
                    <select class="form-control select2 role-selected">
                        <option>Select One</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- /.form-group -->
            </div>
        </div>
        <!-- /.row -->
        <div class="row permission-list">
            {{-- Permission data will be append here--}}
        </div>
        <!-- /.row -->
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->
@endsection
