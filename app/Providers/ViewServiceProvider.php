<?php

namespace App\Providers;

use App\View\Composers\Admin\NotificationsComposer;
use App\View\Composers\Admin\SidebarComposer;
use App\View\Composers\Blog\RightSidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Using class based composers...
        View::composer('admin.compose.sidebar_menus', SidebarComposer::class);
        View::composer('admin.compose.notifications', NotificationsComposer::class);
        View::composer('blog.compose.right_sidebar', RightSidebarComposer::class);
    }
}
