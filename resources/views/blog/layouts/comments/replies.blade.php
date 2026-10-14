@if(sizeof($comments) > 0)
    <div class="reply-form-custom mb-3">
        @php $is_logged_in = \Illuminate\Support\Facades\Auth::user(); @endphp
        @foreach($comments as $comment)
            @if($is_logged_in && $comment->user_id == \Illuminate\Support\Facades\Auth::user()->id)
                @php $comment->user->name = 'You' @endphp
            @endif
            <div class="reply_block">
                <div class="@if(!empty($comment->parent_id)) @endif pb-1">
                    <div id="comment_{{ $comment->id }}" class="comment-author d-flex align-items-center mt-1">
                        @if(!empty($comment->user->basic->image_path) && file_exists($comment->user->basic->image_path))
                            <img src="{{ asset($comment->user->basic->image_path) }}" class="rounded-circle float-left"
                                 alt="">
                        @else
                            <img src="{{ asset('admin/dist/img/user-avatar.webp') }}" class="rounded-circle float-left"
                                 alt="">
                        @endif
                        <div class="pt-0">
                            <h4>{{ $comment->user->name  }} <a href="#" class="reply text-muted"><i
                                        class="fa fa-turn-down"></i>
                                    Reply</a></h4>
                            <p>{{ $comment->comment }}</p>
                            @if($is_logged_in && $is_logged_in->id === $comment->user_id)
                                <button class="btn btn-sm text-warning"
                                        data-action="{{ route('comments.edit', $comment->id) }}"
                                        data-action-update="{{ route('comments.update', $comment->id) }}"
                                        onclick="return comment_edit($(this));"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-sm text-danger"
                                        data-action="{{ route('comments.destroy', $comment->id) }}"
                                        onclick="return form_delete($(this));"><i class="fa fa-trash"></i></button>
                            @endif
                        </div>
                    </div>

                    @if($is_logged_in)
                        <form action="{{ route('reply.store') }}" method="post" class="comment-{{$comment->id}} mb-3">
                            @csrf
                            <div class="row">
                                <input type="hidden" name="post_id" value="{{ $post->id }}">
                                <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                                <div class="col form-group">
                            <textarea name="comment" class="form-control mt-2 comment required_validation"
                                      placeholder="Reply here" rows="1"></textarea>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="return formSubmit(this.form)">
                                Reply
                                Comment
                            </button>
                        </form>
                    @endif
                </div>
                @include('blog.layouts.comments.replies', ['comments' => $comment->replies])
            </div>
        @endforeach
    </div>
@endif
