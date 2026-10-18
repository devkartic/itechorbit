<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use App\Models\Admin\Post;
use App\Models\Admin\Tag;
use App\Models\User;
use App\Services\Admin\AdminPresentation;
use App\Services\Shared\MediaFiles;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    private const UPLOADS = [
        'uploaded_image' => ['column' => 'image_path', 'directory' => 'uploads/posts/images'],
    ];

    /**
     * Check permission for routs, roles, links.
     *
     * @return Response or exception
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            PermissionController::permission_verify();

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return app(AdminPresentation::class)->render('posts', 'index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $categories = Category::all()->sortBy('name');
        $tags = Tag::all()->sortBy('name');

        return app(AdminPresentation::class)->render('posts', 'create', compact('categories', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->create_validator($request);
        MediaFiles::save(new Post, [
            'title' => $request->input('title'),
            'category_id' => $request->input('category_id'),
            'tags' => Tag::tags_to_json($request->input('tags')),
            'body' => $request->input('body'),
            'codebase' => $request->input('codebase'),
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
            'is_private' => filter_var($request->input('is_private'), FILTER_VALIDATE_BOOLEAN),
            'user_id' => Auth::user()->id,
        ], $request, self::UPLOADS);

        return response()->json(['success' => 'Post created successfully']);
    }

    private function create_validator($request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'body' => ['required', 'string', 'max:1000000'],
            'uploaded_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Post $post)
    {
        User::verify_ownership($post->user_id);

        return app(AdminPresentation::class)->render('posts', 'show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Post $post)
    {
        User::verify_ownership($post->user_id);

        $categories = Category::all()->sortBy('name');
        $tags = Tag::all()->sortBy('name');

        return app(AdminPresentation::class)->render('posts', 'edit', compact('categories', 'tags', 'post'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Post $post)
    {
        User::verify_ownership($post->user_id);

        $this->update_validator($request);

        $post->title = $request->input('title');
        $post->category_id = $request->input('category_id');
        $post->tags = Tag::tags_to_json($request->input('tags'));
        $post->body = $request->input('body');
        $post->codebase = $request->input('codebase');
        $post->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $post->is_private = filter_var($request->input('is_private'), FILTER_VALIDATE_BOOLEAN);
        $post->updated_by = Auth::user()->id;
        MediaFiles::save($post, $post->getDirty(), $request, self::UPLOADS);

        return response()->json(['success' => 'Post updated successfully']);
    }

    private function update_validator($request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'body' => ['required', 'string', 'max:1000000'],
            'uploaded_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Post $post)
    {
        User::verify_ownership($post->user_id);

        MediaFiles::destroy($post, ['image_path']);

        return response()->json(['success' => 'Post deleted successfully']);
    }

    public function get_posts_ajax(Request $request): object
    {

        $draw = $request->input('draw');

        $columns = ['id', 'category', 'title', 'body', 'created_at', 'updated_at', 'created_by', 'updated_by', 'id'];

        $total = User::filtered_posts()->count();

        $filtered = $total;

        $limit = $request->input('length');
        $start = $request->input('start');
        $columnName = $columns[$request->input('order.0.column')];
        $orderBy = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $posts = User::filtered_posts()->offset($start)
                ->limit($limit)
                ->orderBy($columnName, $orderBy)
                ->get();
        } else {
            $search_text = $request->input('search.value');

            $posts = User::filtered_posts()
                ->Where(function ($query) use ($search_text) {
                    $query->where('categories.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.title', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.body', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.created_at', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.updated_at', 'LIKE', "%{$search_text}%")
                        ->orWhere('cb.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('ub.name', 'LIKE', "%{$search_text}%");
                })
                ->offset($start)
                ->limit($limit)
                ->orderBy($columnName, $orderBy)
                ->get();

            $filtered = User::filtered_posts()
                ->Where(function ($query) use ($search_text) {
                    $query->where('categories.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.title', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.body', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.created_at', 'LIKE', "%{$search_text}%")
                        ->orWhere('posts.updated_at', 'LIKE', "%{$search_text}%")
                        ->orWhere('cb.name', 'LIKE', "%{$search_text}%")
                        ->orWhere('ub.name', 'LIKE', "%{$search_text}%");
                })
                ->count();
        }
        $data = [];
        foreach ($posts as $post) {

            $show = route('posts.show', $post->id);
            $edit = route('posts.edit', $post->id);
            $delete = route('posts.destroy', $post->id);

            $row['id'] = ++$start;
            $row['category'] = $post->category;
            $row['title'] = $post->title;
            $row['body'] = Str::words($post->body, 10, ' ...');
            $row['created_at'] = Carbon::parse($post->created_at)->format('d-M-Y g:i A');
            $row['updated_at'] = Carbon::parse($post->updated_at)->format('d-M-Y g:i A');
            $row['created_by'] = $post->created_by;
            $row['updated_by'] = $post->updated_by;
            $row['actions'] = '<div class="btn-toolbar" role="toolbar">';
            $row['actions'] .= '<div class="btn-group mr-1 mb-1" role="group" aria-label="First Group">';
            $row['actions'] .= '<a type="button" href="'.$show.'" class="btn btn-xs btn-block btn-outline-info btn-sm">Show</a>';
            $row['actions'] .= '</div>';
            $row['actions'] .= '<div class="btn-group mr-1 mb-1" role="group" aria-label="Second Group">';
            $row['actions'] .= '<a type="button" href="'.$edit.'" class="btn btn-xs btn-block btn-outline-warning btn-sm">Edit</a>';
            $row['actions'] .= '</div>';
            $row['actions'] .= '<div class="btn-group mr-1 mb-1" role="group" aria-label="Third Group">';
            $row['actions'] .= '<button type="button" class="btn btn-xs btn-block btn-outline-danger btn-sm" data-action="'.$delete.'" onclick="return form_delete($(this));">Delete</button>';
            $row['actions'] .= '</div>';
            $row['actions'] .= '</div>';
            $data[] = $row;
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];

        return response()->json($response);
    }
}
