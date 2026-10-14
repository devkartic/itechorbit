<div class="reply-form mb-3">
    <h4>Leave a Comment</h4>
    <p>Your comment will be published here. Comment field is must be required ! </p>
    <form action="{{ url('comments') }}" method="post">
        @csrf
        <div class="row">
            <input type="hidden" name="post_id" value="{{ $post->id }}">
            <div class="col form-group">
                <textarea name="comment" class="form-control mt-2 comment required_validation"
                          placeholder="Leave your comments here"></textarea>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="return formSubmit(this.form)">Post Comment</button>
    </form>
    @if(!\Illuminate\Support\Facades\Auth::check())
        <p class="text-success fw-bold fs-6 my-2">Note: For leave a comment or reply, You must have to required <a
                class="btn btn-sm btn-outline-primary fw-bold px-3" href="{{ route('login') }}"> Sign In</a></p>
    @endif
</div>
