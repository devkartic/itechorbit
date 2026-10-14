@extends('blog.app')
@section('content')
    <div class="col-lg-8 entries">
        @if(!empty($searching_keyword))
            <div
                class="alert @if($posts->total() > 0) alert-success @else alert-warning @endif alert-dismissible fade show"
                role="alert">
                <strong>Search results!</strong> Your search keyword matched <span
                    class="fw-bold">{{ $posts->total() }}</span> posts.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @foreach($posts as $post)
            <article class="entry">
                @if(!empty($post->image_path) && file_exists($post->image_path))
                    <div class="entry-img">
                        <img src="{{ asset($post->image_path) }}" alt="" class="img-fluid">
                    </div>
                @endif
                <h2 class="entry-title">
                    <a href="{{ route('blogs.show', $post->id) }}">{{ $post->title }}</a>
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

                <div class="entry-content">
                    <p>{!! \Illuminate\Support\Str::words($post->body, 30, ' ...') !!}</p>
                    <div class="read-more justify-content-start">
                        <a href="{{ route('blogs.show', $post->id) }}">Read More</a>
                    </div>
                </div>

                <div class="entry-footer mt-4">
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
        @endforeach
        <!-- End blog entry -->

        <!-- Pagination -->
        @if($posts->total() > 0)
            {{ $posts->links('blog.includes.pagination') }}
        @endif
        <!-- End Pagination -->
    </div>
@endsection
