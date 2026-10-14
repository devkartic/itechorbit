<?php

namespace App\View\Composers\Admin;

use App\CacheRepositories\Menus;
use Illuminate\View\View;

class SidebarComposer
{
    /**
     * The user repository implementation.
     *
     * @var App\Models\Admin\Menu
     */
    protected $menus;

    /**
     * Create a new profile composer.
     *
     * @param  App\Models\Admin\Menu  $menus
     * @return void
     */
    public function __construct()
    {
        $this->menus = Menus::verified_menus('order_number');
    }

    /**
     * Bind data to the view.
     *
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('menus', $this->menus);
    }
}
