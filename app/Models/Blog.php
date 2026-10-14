<?php

namespace App\Models;

use App\Models\Admin\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    public static function posts($limit = 20)
    {
        return Post::public_posts()->orderBy('id', 'DESC')->paginate($limit)->fragment('posts');
    }

    public static function popular_posts($limit = 20)
    {
        return Post::public_posts()->orderBy('view_times', 'DESC')->limit($limit)->get();
    }

    public static function search_posts($key, $limit = 20)
    {
        return Post::public_posts()->where(function ($query) use ($key) {
            $query->where('title', 'like', "%{$key}%")->orWhere('body', 'like', "%{$key}%");
        })->orderBy('view_times', 'DESC')->paginate($limit)->fragment('posts');
    }

    public static function category_posts($category_id, $limit = 20)
    {
        return Post::public_posts()->where('category_id', $category_id)->orderBy('view_times', 'DESC')->paginate($limit)->fragment('posts');
    }
}
