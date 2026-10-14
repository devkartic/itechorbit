<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'order_number',
    ];

    public static function active_tags(): object
    {
        return User::filtering_collections(self::all(), 'status', 1);
    }

    public static function tags_to_json($tags = null)
    {
        if (empty($tags)) {
            return $tags;
        }
        $post_tags = [];
        foreach ($tags as $tag) {
            $find_tag = Tag::find($tag);
            if ($find_tag) {
                $post_tags[] = Tag::find($tag)->name;
            }
        }

        return json_encode($post_tags);
    }
}
