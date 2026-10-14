<div class="col-lg-4">
    <div class="sidebar">
        <h3 class="sidebar-title">Search</h3>
        <div class="sidebar-item search-form">
            <form action="{{ route('blogs.search') }}" method="post">
                @csrf
                <input type="text" name="key" @if(!empty($searching_keyword)) value="{{ $searching_keyword }}"
                       @endif placeholder="Search here">
                <button type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <!-- End sidebar search form-->
        @if(sizeof($categories) > 0)
        <h3 class="sidebar-title">Categories</h3>
        <div class="sidebar-item tags pb-3">
            <ul>
                @foreach($categories as $category)
                    <li><a href="{{ route('blogs.category', $category->id) }}">{{ $category->name }} <span class="text-muted">({{ $category->posts->count() }})</span></a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
        <!-- End sidebar categories-->
        @if(sizeof($popular_posts) > 0)
        <h3 class="sidebar-title">Popular Posts</h3>
        <div class="sidebar-item recent-posts">
            @foreach($popular_posts as $popular_post)
                <div class="post-item clearfix">
                    @if(!empty($popular_post->image_path) && file_exists($popular_post->image_path))
                        <img src="{{ asset($popular_post->image_path) }}" alt="">
                    @else
                        <img class="popular-post-no-image" src="{{ asset('blog/img/blog/no_image.webp') }}" alt="">
                    @endif
                    <h4><a href="{{ url('blogs/'. $popular_post->id) }}">{{ $popular_post->title }}</a></h4>
                    <time
                        datetime="2020-01-01">{{ \Carbon\Carbon::parse($popular_post->created_at)->format('M d, Y \a\t H:i A') }}</time>
                </div>
            @endforeach
        </div>
        @endif
        <!-- End sidebar recent posts-->
        @if(sizeof($tags) > 0)
            <h3 class="sidebar-title">Tags</h3>
            <div class="sidebar-item tags">
                <ul>
                    @foreach($tags as $key => $tag)
                        <li><a href="#">{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- End sidebar tags-->
    </div>
    <!-- End sidebar -->
</div>
