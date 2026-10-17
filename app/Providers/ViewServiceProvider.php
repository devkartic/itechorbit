<?php

namespace App\Providers;

use App\View\Composers\Admin\NotificationsComposer;
use App\View\Composers\Admin\SidebarComposer;
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
        View::composer('legacy.admin.compose.sidebar_menus', SidebarComposer::class);
        View::composer('legacy.admin.compose.notifications', NotificationsComposer::class);
    }
}
