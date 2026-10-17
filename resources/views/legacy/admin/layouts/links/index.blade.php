@extends('legacy.admin.app')
@section('page_title', 'Links List')
@section('bread_camp', 'Links list')
@section('action_level', 'Link Create')
@section('action_url', 'links/create')
@include('legacy.admin.includes.jquery_datatable')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">DataTable with default features</h3>
        </div>
        <!-- /.card-header -->
        @if(sizeof($links))
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Sl no.</th>
                    <th>Modules</th>
                    <th>Links</th>
                    <th>Fa-icon</th>
                    <th>Order Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($links as $key => $link)
                    <tr>
                        <td>{{ ++$key }}</td>
                        <td>{{ $link->module->name }}</td>
                        <td>{{ $link->name }}</td>
                        <td class="text-center">{!! $link->fa_icon !!}</td>
                        <td>{{ $link->order_number }}</td>
                        <td>
                            @if($link->status)
                                <h6 class="badge bg-primary">Enable</h6>
                            @else
                                <h6 class="badge bg-danger">Disable</h6>
                            @endif
                        </td>
                        <td class="mx-auto">
                            <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                                <a href="{{ url('links/'.$link->id) }}" class="btn-group mr-2" role="group" aria-label="First group">
                                    <button type="button" class="btn btn-xs btn-outline-info">View</button>
                                </a>
                                <a href="{{ url('links/'.$link->id.'/edit') }}" class="btn-group mr-2" role="group" aria-label="Second group">
                                    <button type="button" class="btn btn-xs btn-outline-warning">Edit</button>
                                </a>
                                <button type="button" class="btn btn-xs btn-outline-danger" data-action="{{ url('links/'.$link->id) }}" onclick="return form_delete($(this));">Delete</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th>Sl no.</th>
                    <th>Modules</th>
                    <th>Links</th>
                    <th>Fa-icon</th>
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
