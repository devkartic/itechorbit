<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PermissionController;
use App\Models\Admin\Post;
use App\Models\User;
use App\Services\AdminPresentation;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('verified');
    }

    /**
     * Show the application dashboard.
     *
     * @return string
     */
    public function index()
    {
        PermissionController::initial_verification(Auth::user());

        return app(AdminPresentation::class)->dashboard();

    }

    private function admin_dashboard(): object
    {
        $users = User::all();
        $posts = Post::all();
        $progress = $this->progress_status_of_portfolio(Auth::user());

        return view('admin.layouts.dashboards.admin', compact('users', 'posts', 'progress'));
    }

    private function user_dashboard(): object
    {
        $posts = Auth::user()->posts;

        $progress = $this->progress_status_of_portfolio(Auth::user());

        return view('admin.layouts.dashboards.user', compact('posts', 'progress'));
    }

    private function progress_status_of_portfolio($user): int
    {
        $progress = 30;
        if (! empty($user->basic)) {
            $progress += 10;
        }
        if (count($user->social_links)) {
            $progress += 10;
        }
        if (count($user->skills)) {
            $progress += 10;
        }
        if (count($user->services)) {
            $progress += 10;
        }
        if (count($user->projects)) {
            $progress += 10;
        }
        if (count($user->experiences)) {
            $progress += 10;
        }
        if (count($user->testimonials)) {
            $progress += 10;
        }

        return $progress;
    }
}
