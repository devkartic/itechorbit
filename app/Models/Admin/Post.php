<?php

namespace App\Models\Admin;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'tags',
        'user_id',
        'title',
        'body',
        'codebase',
        'image_path',
        'is_private',
        'status',
        'updated_by',
    ];

    /**
     * Get the Category for the post.
     */
    public static function public_posts(): object
    {
        return self::where('status', 1)->where('is_private', 0);
    }

    /**
     * Get the Category for the post.
     */
    public static function view_times_increment($post)
    {
        $post->view_times = $post->view_times + 1;

        return $post->save();
    }

    /**
     * Get the Category for the post.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the User for the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the User for the post.
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id');
    }
}
