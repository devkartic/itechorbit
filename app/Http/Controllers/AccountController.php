<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Http\Requests\UpdateAccountRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->status, 403, 'This account is disabled.');

            return $next($request);
        });
    }

    public function profile(Request $request)
    {
        return $this->page($request, 'profile');
    }

    public function settings(Request $request)
    {
        return $this->page($request, 'settings');
    }

    private function page(Request $request, string $mode)
    {
        return Inertia::render('Account', [
            'mode' => $mode,
            'account' => [...$request->user()->only(['name', 'email']), 'verified' => $request->user()->hasVerifiedEmail(), 'joined' => $request->user()->created_at?->toDateString()],
        ]);
    }

    public function update(UpdateAccountRequest $request)
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));
        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        return to_route('account.settings')->with('success', $emailChanged ? 'Account updated. Verify your new email address to access the workspace.' : 'Account details updated.');
    }

    public function password(UpdateAccountPasswordRequest $request)
    {
        $request->user()->forceFill(['password' => Hash::make($request->validated('password')), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();
        $request->session()->flash('success', 'Password updated.');

        // A full visit refreshes the CSRF meta token after session regeneration.
        return $request->header('X-Inertia') ? Inertia::location(route('account.settings')) : to_route('account.settings');
    }
}
