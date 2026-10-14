<?php

namespace App\View\Composers\Blog;

use App\Models\Admin\Category;
use App\Models\Admin\Tag;
use App\Models\Blog;
use Illuminate\View\View;

class RightSidebarComposer
{
    /**
     * Bind data to the view.
     *
     * @return void
     */
    public function compose(View $view)
    {
        $categories = Category::active_categories()->sortBy('name');
        $tags = Tag::active_tags();
        $popular_posts = Blog::popular_posts(5);
        $view->with(compact('categories', 'tags', 'popular_posts'));
    }
}
