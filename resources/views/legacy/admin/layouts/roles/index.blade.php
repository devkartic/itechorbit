@extends('legacy.admin.app')
@section('page_title', 'Roles List')
@section('bread_camp', 'Roles list')
@section('action_level', 'Role Create')
@section('action_url', 'roles/create')
@include('legacy.admin.includes.jquery_datatable')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">DataTable with default features</h3>
        </div>
        <!-- /.card-header -->
        @if(sizeof($roles))
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Sl no.</th>
                        <th>Name</th>
                        <th>Order Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($roles as $key => $role)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->order_number }}</td>
                            <td>
                                @if($role->status)
                                    <h6 class="badge bg-primary">Enable</h6>
                                @else
                                    <h6 class="badge bg-danger">Disable</h6>
                                @endif
                            </td>
                            <td class="mx-auto">
                                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                                    <a href="{{ url('roles/'.$role->id) }}" class="btn-group mr-2" role="group"
                                       aria-label="First group">
                                        <button type="button" class="btn btn-xs btn-outline-info">View</button>
                                    </a>
                                    <a href="{{ url('roles/'.$role->id.'/edit') }}" class="btn-group mr-2"
                                       role="group" aria-label="Second group">
                                        <button type="button" class="btn btn-xs btn-outline-warning">Edit</button>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger"
                                            data-action="{{ url('roles/'.$role->id) }}"
                                            onclick="return form_delete($(this));">Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Sl no.</th>
                        <th>Modules</th>
                        <th>Order Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="card-body text-center">
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <p class="h4 card-text text-danger my-5">Data not available!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        @endif
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection
