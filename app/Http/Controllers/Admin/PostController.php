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
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends Controller
{
    protected ?string $destination_path;

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
        $this->destination_path = public_path('uploads/posts/images');
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
        $image_path = null;
        if ($request->file('uploaded_image') != null && $request->file('uploaded_image')->isValid()) {
            try {
                $file = $request->file('uploaded_image');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_image')));
                $image = (string) Str::uuid().'.'.$file->extension();
                $image_path = 'uploads/posts/images/'.$image;
                $file->move($this->destination_path, $image);
            } catch (FileNotFoundException $e) {

            }
        }

        Post::create([
            'title' => $request->input('title'),
            'category_id' => $request->input('category_id'),
            'tags' => Tag::tags_to_json($request->input('tags')),
            'body' => $request->input('body'),
            'codebase' => $request->input('codebase'),
            'image_path' => $image_path,
            'status' => filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN),
            'is_private' => filter_var($request->input('is_private'), FILTER_VALIDATE_BOOLEAN),
            'user_id' => Auth::user()->id,
        ]);

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

        $old_file_image = $post->image_path;
        $image_path = null;
        if ($request->file('uploaded_image') != null && $request->file('uploaded_image')->isValid()) {
            try {
                $file = $request->file('uploaded_image');
                $tempName = strtolower(str_replace(' ', '', $request->input('uploaded_image')));
                $image = (string) Str::uuid().'.'.$file->extension();
                $image_path = 'uploads/posts/images/'.$image;
                $file->move($this->destination_path, $image);
                if ($old_file_image) {
                    MediaFiles::delete($old_file_image);
                }
            } catch (FileNotFoundException $e) {

            }
        }

        $post->title = $request->input('title');
        $post->category_id = $request->input('category_id');
        $post->tags = Tag::tags_to_json($request->input('tags'));
        $post->body = $request->input('body');
        $post->codebase = $request->input('codebase');
        if ($image_path) {
            $post->image_path = $image_path;
        }
        $post->status = filter_var($request->input('is_checked'), FILTER_VALIDATE_BOOLEAN);
        $post->is_private = filter_var($request->input('is_private'), FILTER_VALIDATE_BOOLEAN);
        $post->updated_by = Auth::user()->id;
        $post->save();

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

        $post->delete();

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
