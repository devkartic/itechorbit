<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PermissionController;
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
}
