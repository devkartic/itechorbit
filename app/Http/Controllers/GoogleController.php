<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    public function login(): object
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): object
    {
        try {
            $google_user = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            return redirect('/login')->withErrors(['email' => 'Your Google sign-in session expired. Please try again.']);
        }
        abort_unless(filter_var($google_user->user['email_verified'] ?? $google_user->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN), 403);
        $this->registerOrLoginUser($google_user);
        request()->session()->regenerate();

        return redirect('login');
    }

    protected function registerOrLoginUser($google_user)
    {
        $user = User::where('email', $google_user->getEmail())->first();

        if (! $user) {
            $user = new User;
            $user->name = $google_user->name;
            $user->role_id = 4;
            $user->email = $google_user->email;
            $user->email_verified_at = Carbon::now();
            $user->provider_id = $google_user->id;
            $user->password = Hash::make(Str::random(64));
            $user->save();
        }

        abort_unless($user->status, 403, 'Profile has been disabled.');
        abort_if($user->provider_id && (string) $user->provider_id !== (string) $google_user->id, 403);
        Auth::login($user);
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}
