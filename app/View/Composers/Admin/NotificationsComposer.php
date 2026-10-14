<?php

namespace App\View\Composers\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationsComposer
{
    /**
     * The user repository implementation.
     *
     * @var App\Models\Contact
     */
    protected $emails;

    /**
     * Create a new notification composer.
     *
     * @param  App\Models\Contact  $emails
     * @return void
     */
    public function __construct()
    {
        $this->emails = Auth::user()->contacts->where('read_status', 0);
    }

    /**
     * Bind data to the view.
     *
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('notifications', $this->emails);
    }
}
