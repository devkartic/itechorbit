@extends('admin.app')
@section('page_title', 'Mail Inbox')
@section('bread_camp', 'Mail Inbox')
@include('admin.includes.jquery_datatable')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">DataTable with default features</h3>
        </div>
        <!-- /.card-header -->
        @if(sizeof($contacts))
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Sl no.</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Read Status</th>
                        <th>Mail Sent</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($contacts as $key => $email)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $email->name }}</td>
                            <td>{{ $email->subject }}</td>
                            <td>{{ $email->message }}</td>
                            <td>
                                @if($email->read_status)
                                    <h6 class="badge bg-success">Read</h6>
                                @else
                                    <h6 class="badge bg-warning">Unread</h6>
                                @endif
                            </td>
                            <td>
                                @if($email->sent_mail_status)
                                    <h6 class="badge bg-success">Sent</h6>
                                @else
                                    <h6 class="badge bg-warning">Pending</h6>
                                @endif
                            </td>
                            <td class="mx-auto">
                                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                                    <a href="{{ url('contacts/'.$email->id) }}" class="btn-group mr-2" role="group"
                                       aria-label="First group">
                                        <button type="button" class="btn btn-xs btn-outline-info">View</button>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger"
                                            data-action="{{ url('contacts/'.$email->id) }}"
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
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Read Status</th>
                        <th>Mail Sent</th>
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
