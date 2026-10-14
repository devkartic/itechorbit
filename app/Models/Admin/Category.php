<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'image_path',
        'order_number',
        'status',
    ];

    public static function active_categories(): object
    {
        return User::filtering_collections(self::all(), 'status', 1);
    }

    /**
     * Get the Posts for the Category.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
