@extends('blog.app')
@section('page_title', 'Post Details')
@section('content')
    <div class="col-lg-8 entries">

        <article class="entry entry-single">

            @if(!empty($post->image_path) && file_exists($post->image_path))
                <div class="entry-img">
                    <img src="{{ asset($post->image_path) }}" alt="" class="img-fluid">
                </div>
            @endif

            <h2 class="entry-title">
                <a href="#">{{ $post->title }}</a>
            </h2>

            <div class="entry-meta">
                <button type="button" class="btn btn-sm btn-outline-primary position-relative mb-1"><i
                        class="bi bi-person"></i>{{ $post->user->name }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary position-relative mb-1"><i
                        class="bi bi-clock"></i>{{ \Carbon\Carbon::parse($post->created_at)->format('M d, Y \a\t H:i A') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary position-relative mb-1"><i
                        class="bi bi-eye"></i>Views<span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">{{ $post->view_times }}+</span>
                </button>
            </div>

            <div class="entry-content">{!! $post->body !!} @if(!empty($post->codebase)) {!! $post->codebase !!} @endif</div>

            <div class="entry-footer mt-5">
                <i class="bi bi-folder"></i>
                <ul class="cats">
                    <li><a href="#">{{ $post->category->name }}</a></li>
                </ul>

                @if(!empty($post->tags))
                    @php $post->tags = json_decode($post->tags) @endphp
                    <i class="bi bi-tags"></i>
                    <ul class="tags">
                        @foreach($post->tags as $tag)
                            <li><a href="#">{{ $tag }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </article>
        <!-- End blog entry -->
        @if(!empty($post->user->basic))
            <div class="blog-author d-flex align-items-center">
                @if(!empty($post->user->basic->image_path) && file_exists($post->user->basic->image_path))
                    <img src="{{ asset($post->user->basic->image_path) }}" class="rounded-circle float-left" alt="">
                @else
                    <img src="{{ asset('admin/dist/img/user-avatar.webp') }}" class="rounded-circle float-left" alt="">
                @endif
                <div>
                    <h4>{{ $post->user->basic->first_name .' '. $post->user->basic->last_name  }}</h4>
                    @if(sizeof($post->user->social_links))
                        <div class="social-links">
                            @foreach($post->user->social_links as $social_link)
                                <a href="{{ $social_link->social_url }}" class="me-1" target="_blank">{!! $social_link->fa_icon !!}</a>
                            @endforeach
                        </div>
                    @endif
                    <p>{{ $post->user->basic->career_objective }}</p>
                </div>
            </div>
        @endif
        <!-- End blog author bio -->

        @include('blog.layouts.comments.show')
        <!-- End blog comments -->
    </div>
    <!-- End blog entries list -->
@endsection
