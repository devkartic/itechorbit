@extends('admin.app')
@section('page_title', 'Posts List')
@section('bread_camp', 'Posts list')
@section('action_level', 'Post Create')
@section('action_url', 'posts/create')
@include('admin.includes.posts_serverside_datatable')
@section('content')
    <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
            <table class="datatable-custom display" style="width:100%">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Title</th>
                    <th>Descriptions</th>
                    <th>Created at</th>
                    <th>Updated at</th>
                    <th>Created By</th>
                    <th>Updated By</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Descriptions</th>
                    <th>Created at</th>
                    <th>Updated at</th>
                    <th>Created By</th>
                    <th>Updated By</th>
                    <th>Action</th>
                </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
@endsection
