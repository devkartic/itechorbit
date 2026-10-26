<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\SocialProviders;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    protected function loggedOut(Request $request)
    {
        Inertia::clearHistory();
    }

    protected function credentials(Request $request)
    {
        return array_merge($request->only($this->username(), 'password'), [
            fn ($query) => $query->where(fn ($query) => $query->where('status', 1)
                ->orWhere(fn ($query) => $query->where('pending_email_activation', true)->whereNull('email_verified_at'))),
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
    }

    public function showLoginForm()
    {
        return Inertia::render('Auth/Index', ['mode' => 'login', 'socialProviders' => app(SocialProviders::class)->available()]);
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
