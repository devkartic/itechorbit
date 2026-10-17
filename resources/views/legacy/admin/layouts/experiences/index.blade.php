@extends('legacy.admin.app')
@section('page_title', 'Experiences List')
@section('bread_camp', 'Experiences list')
@section('action_level', 'Experience Create')
@section('action_url', 'experiences/create')
@include('legacy.admin.includes.jquery_datatable')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">DataTable with default features</h3>
        </div>
        <!-- /.card-header -->
        @if(sizeof($experiences))
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Sl no.</th>
                        <th>Designations</th>
                        <th>Organizations</th>
                        <th>Joining Date</th>
                        <th>Resign Date</th>
                        <th>Order Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($experiences as $key => $experience)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $experience->designation }}</td>
                            <td>{{ $experience->organization }}</td>
                            <td>{{ date('d M Y', strtotime($experience->join_date)) }}</td>
                            <td>@if($experience->resign_date)
                                    {{ date('d M Y', strtotime($experience->resign_date)) }}
                                @else
                                    <span class="badge badge-warning p-1">Service continuing at present</span>
                                @endif</td>
                            <td>{{ $experience->order_number }}</td>
                            <td>
                                @if($experience->status)
                                    <h6 class="badge bg-primary">Enable</h6>
                                @else
                                    <h6 class="badge bg-danger">Disable</h6>
                                @endif
                            </td>
                            <td class="mx-auto">
                                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                                    <a href="{{ url('experiences/'.$experience->id) }}" class="btn-group mr-2"
                                       role="group" aria-label="First group">
                                        <button type="button" class="btn btn-xs btn-outline-info">View</button>
                                    </a>
                                    <a href="{{ url('experiences/'.$experience->id.'/edit') }}" class="btn-group mr-2"
                                       role="group" aria-label="Second group">
                                        <button type="button" class="btn btn-xs btn-outline-warning">Edit</button>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger"
                                            data-action="{{ url('experiences/'.$experience->id) }}"
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
                        <th>Designations</th>
                        <th>Organizations</th>
                        <th>Joining Date</th>
                        <th>Resign Date</th>
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
