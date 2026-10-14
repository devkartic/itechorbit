<div class="blog-comments">
    <h4 class="comments-count text-primary"><i class="fa-solid fa-comment"></i> {{ $post->comments->count() }} Comments
    </h4>

    @include('blog.layouts.comments.create')

    @include('blog.layouts.comments.replies', ['comments' => $post->comments])
    <!-- End comments & Author -->
</div>
