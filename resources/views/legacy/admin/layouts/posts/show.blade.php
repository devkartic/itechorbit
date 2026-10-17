@extends('legacy.admin.app')
@section('page_title', 'Post Details')
@section('bread_camp', 'Post Details')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Posted category- <a href="#"
                                                       class="text-bold text-decoration-none">{{ $post->category->name }}</a>
            </h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-info">
                                <div class="card-header p-3">
                                    <div class="card-title text-bold">{{ $post->title }}</div>
                                </div><!-- /.card-header -->
                                <div class="card-body">
                                    <div class="tab-content">
                                        <div class="active tab-pane">
                                            <!-- Post -->
                                            <div class="post">
                                                <div class="user-block">
                                                    @if(!empty($post->user->basic->image_path) && file_exists($post->user->basic->image_path))
                                                        <img class="img-circle img-bordered-sm"
                                                             src="{{ asset($post->user->basic->image_path) }}"
                                                             alt="User profile picture">
                                                    @else
                                                        <img class="img-circle img-bordered-sm"
                                                             src="{{ asset('admin/dist/img/user-avatar.webp') }}"
                                                             alt="User profile picture">
                                                    @endif
                                                    <span class="username"><a
                                                            href="{{ url('users/'.$post->created_by) }}">{{ $post->user->name }}</a><a
                                                            href="{{ url('posts') }}" class="float-right btn-tool"><i
                                                                class="fas fa-times"></i></a></span>
                                                    <span class="description">@if($post->is_private==true)
                                                            Drafted
                                                        @else
                                                            Shared publicly
                                                        @endif - {{ \Carbon\Carbon::parse($post->created_at)->format('d M Y \a\t g:i A') }}</span>
                                                </div>
                                                <!-- /.user-block -->
                                                <div class="entry my-3">
                                                    @if(!empty($post->image_path) && file_exists($post->image_path))
                                                        <div class="entry-img">
                                                            <img src="{{ asset($post->image_path) }}" alt=""
                                                                 class="img-fluid">
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="mb-2">{!! $post->body !!}</div>
                                                @if(!empty($post->codebase))
                                                    <div class="mb-3">{!! $post->codebase !!}</div>
                                                @endif

                                                <p>
                                                    <a href="#" class="link-black text-sm mr-2"><i
                                                            class="fas fa-share mr-1"></i> Share</a>
                                                    <a href="#" class="link-black text-sm"><i
                                                            class="far fa-thumbs-up mr-1"></i> Like</a>
                                                    <span class="float-right">
                          <a href="#" class="link-black text-sm">
                            <i class="far fa-comments mr-1"></i> Comments (5)
                          </a>
                        </span>
                                                </p>

                                                <input class="form-control form-control-sm" type="text"
                                                       placeholder="Type a comment">
                                            </div>
                                            <!-- /.post -->
                                        </div>
                                        <!-- /.tab-pane -->
                                    </div>
                                    <!-- /.tab-content -->
                                </div><!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection
