<?php

namespace App\Http\Controllers;

use App\Models\Admin\AboutInfo;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\ServiceInfo;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     * Run in terminal for publish pagination: php artisan vendor:publish --tag=laravel-pagination
     *
     * @return Response
     */
    public function index()
    {
        $posts = Blog::posts();

        return view('blog.layouts.blog_posts', compact('posts'));
    }

    /**
     * Display the specified resource.
     *
     * @param  Blog  $blog
     * @return Response
     */
    public function show($post_id)
    {
        $post = Post::public_posts()->with(['category', 'user'])->findOrFail($post_id);
        if ($post) {
            Post::view_times_increment($post);
        }

        return view('blog.layouts.blog_post_detail', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Blog  $blog
     * @return Response
     */
    public function search(Request $request)
    {
        $searching_keyword = $request->input('key');
        $posts = Blog::search_posts($searching_keyword);

        return view('blog.layouts.blog_posts', compact('posts', 'searching_keyword'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Blog  $blog
     * @return Response
     */
    public function category($category_id)
    {
        $posts = Blog::category_posts($category_id);

        return view('blog.layouts.blog_posts', compact('posts'));
    }

    public function about()
    {
        $about = AboutInfo::where('status', 1)->first();

        return view('blog.layouts.about', compact('about'));
    }

    public function services()
    {
        $service = ServiceInfo::where('status', 1)->first();

        return view('blog.layouts.services', compact('service'));
    }

    public function privacy_policy()
    {
        $policy = PrivacyPolicy::where('status', 1)->first();

        return view('blog.layouts.policies', compact('policy'));
    }

    public function contact()
    {
        $contact = ContactInfo::where('status', 1)->first();

        return view('blog.layouts.contact', compact('contact'));
    }
}
