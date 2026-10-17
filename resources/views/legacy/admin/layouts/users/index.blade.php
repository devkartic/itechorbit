@extends('legacy.admin.app')
@section('page_title', 'Users List')
@section('bread_camp', 'Users list')
@section('create_level', 'User Create')
@section('create_url', 'users/create')
@include('legacy.admin.includes.users_serverside_datatable')
@section('content')
    <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
            <table class="datatable-custom display" style="width:100%">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Portfolio</th>
                    <th>Created at</th>
                    <th>Updated at</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Portfolio</th>
                    <th>Created at</th>
                    <th>Updated at</th>
                    <th>Action</th>
                </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
@endsection
