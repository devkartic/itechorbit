@extends('legacy.admin.app')
@section('page_title', 'Testimonials List')
@section('bread_camp', 'Testimonials list')
@section('action_level', 'Testimonial Create')
@section('action_url', 'testimonials/create')
@include('legacy.admin.includes.jquery_datatable')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">DataTable with default features</h3>
        </div>
        <!-- /.card-header -->
        @if(sizeof($testimonials))
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Sl no.</th>
                        <th>Name</th>
                        <th>Designations</th>
                        <th>Organizations</th>
                        <th>Comments</th>
                        <th>Ratings</th>
                        <th>Order Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($testimonials as $key => $testimonial)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $testimonial->name }}</td>
                            <td>{{ $testimonial->designation }}</td>
                            <td>{{ $testimonial->organization }}</td>
                            <td>{{ $testimonial->comment }}</td>
                            <td class="text-primary text-center">
                                @if(!empty($testimonial->ratings))
                                    @for($rating = 0; $rating < $testimonial->ratings; $rating++)
                                        <i class="fa-solid fa-star"></i>
                                    @endfor
                                @endif
                            </td>
                            <td>{{ $testimonial->order_number }}</td>
                            <td>
                                @if($testimonial->status)
                                    <h6 class="badge bg-primary">Enable</h6>
                                @else
                                    <h6 class="badge bg-danger">Disable</h6>
                                @endif
                            </td>
                            <td class="mx-auto">
                                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                                    <a href="{{ url('testimonials/'.$testimonial->id) }}" class="btn-group mr-1 mb-1"
                                       role="group" aria-label="First group">
                                        <button type="button" class="btn btn-xs btn-outline-info">View</button>
                                    </a>
                                    <a href="{{ url('testimonials/'.$testimonial->id.'/edit') }}" class="btn-group mr-1 mb-1"
                                       role="group" aria-label="Second group">
                                        <button type="button" class="btn btn-xs btn-outline-warning">Edit</button>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger mb-1"
                                            data-action="{{ url('testimonials/'.$testimonial->id) }}"
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
                        <th>Name</th>
                        <th>Designations</th>
                        <th>Organizations</th>
                        <th>Comments</th>
                        <th>Ratings</th>
                        <th>Order Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.card-body -->
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
    </div>
    <!-- /.card -->
@endsection
