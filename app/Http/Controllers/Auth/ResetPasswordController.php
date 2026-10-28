<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    protected function resetPassword($user, $password)
    {
        // The password broker calls this only after validating the reset token.
        [$user, $verified] = DB::transaction(function () use ($user, $password) {
            $current = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if ($current->getEmailForPasswordReset() !== $user->getEmailForPasswordReset()) {
                throw ValidationException::withMessages(['email' => trans('passwords.token')]);
            }
            if (! $current->status && ! ($current->pending_email_activation && ! $current->hasVerifiedEmail())) {
                throw ValidationException::withMessages(['email' => 'This account is disabled. Please contact your administrator.']);
            }

            $this->setUserPassword($current, $password);
            $current->setRememberToken(Str::random(60));
            $current->saveOrFail();

            // The emailed reset token proves ownership of this same email address.
            // Existing activation rules distinguish pending signups from admin bans.
            $verified = ! $current->hasVerifiedEmail() && $current->markEmailAsVerified();

            return [$current, $verified];
        });

        if ($verified) {
            event(new Verified($user));
        }
        event(new PasswordReset($user));
        $this->guard()->login($user);
    }

    public function showResetForm(Request $request)
    {
        return Inertia::render('Auth/Index', [
            'mode' => 'reset',
            'token' => $request->route()->parameter('token'),
            'email' => $request->email,
        ]);
    }

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
}
