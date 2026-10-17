@extends('legacy.admin.app')
@section('page_title', 'Email Details')
@section('bread_camp', 'Email Details')
@section('content')
    <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title text-bold text-info">Read Mail</h3>
                                    <div class="card-tools">
                                        <a href="#" class="btn btn-tool" title="Previous"><i
                                                class="fas fa-chevron-left"></i></a>
                                        <a href="#" class="btn btn-tool" title="Next"><i
                                                class="fas fa-chevron-right"></i></a>
                                    </div>
                                </div>

                                <div class="card-body p-0">

                                    <div class="mailbox-read-info px-3">
                                        <p class="h6">From: {{ $email->name }} [{{ $email->email }}]
                                            <span class="mailbox-read-time float-right">{{ \Carbon\Carbon::parse($email->created_at)->format('M d, Y \a\t H:i A') }}</span>
                                        </p>
                                    </div>

                                    <div class="mailbox-read-info px-3">
                                        <p class="h6 text-bold">Subject: {{ $email->subject }}</p>
                                    </div>

                                    <div class="mailbox-read-message pt-2 pb-5 px-3">{{ $email->message }}</div>
                                </div>

                                <div class="card-footer">
                                    <div class="float-right">
                                        <button type="button" class="btn btn-default"><i class="fas fa-reply"></i> Reply
                                        </button>
                                        <button type="button" class="btn btn-default"><i class="fas fa-share"></i>
                                            Forward
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-default"><i class="far fa-trash-alt"></i>
                                        Delete
                                    </button>
                                    <button type="button" class="btn btn-default"><i class="fas fa-print"></i> Print
                                    </button>
                                </div>

                            </div>

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
