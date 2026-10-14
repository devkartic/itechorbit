<?php

namespace App\Http\Controllers;

use App\Models\Admin\Post;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);

        if (! Auth::check()) {
            abort(403, '<span class="text-danger">Comment failed!</span> <span class="fw-bold text-primary">SignIn</span> must be required');
        }

        $comment = new Comment;
        $comment->comment = $request->input('comment');
        $comment->user()->associate($request->user());
        $post = Post::public_posts()->findOrFail($request->input('post_id'));
        $post->comments()->save($comment);

        return response()->json(['success' => 'Comment successfully']);
    }

    public function replyStore(Request $request)
    {
        $this->create_validator($request);

        $reply = new Comment;
        $reply->comment = $request->input('comment');
        $reply->user()->associate($request->user());
        $parent = Comment::where('commentable_type', Post::class)->where('commentable_id', $request->input('post_id'))->findOrFail($request->input('comment_id'));
        $reply->parent_id = $parent->id;
        $post = Post::public_posts()->findOrFail($request->input('post_id'));
        $post->comments()->save($reply);

        return response()->json(['success' => 'Comment replied successfully']);

    }

    private function create_validator($request)
    {
        $request->validate([
            'comment' => ['required', 'string', 'max:255'],
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'comment_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Comment $comment)
    {
        User::verify_ownership($comment->user_id);

        return response()->json($comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Comment $comment)
    {
        User::verify_ownership($comment->user_id);

        $this->update_validator($request);

        $comment->comment = $request->input('comment');
        $comment->save();

        return response()->json(['success' => 'Comment updated successfully']);
    }

    private function update_validator($request)
    {
        $request->validate([
            'comment' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Comment $comment)
    {
        User::verify_ownership($comment->user_id);

        $delete = self::find_child($comment->id);

        $comment->delete();

        return response()->json(['success' => 'Comment deleted successfully']);
    }

    private static function delete_child($comment): bool
    {
        return $comment->delete();
    }

    private static function find_child($comment_id): bool
    {
        $delete = false;
        $comments = Comment::where('parent_id', $comment_id)->get();
        if ($comments) {
            foreach ($comments as $comment) {
                self::find_child($comment->id);
                $delete = self::delete_child($comment);
            }
        }

        return $delete;
    }
}
