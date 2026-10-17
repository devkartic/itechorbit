<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialProviders;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class SocialLoginController extends Controller
{
    public function __construct(private SocialProviders $providers) {}

    public function login(Request $request, string $provider)
    {
        return $this->start($request, $provider, false);
    }

    public function connect(Request $request, string $provider)
    {
        abort_unless($request->user()->status, 403, 'This account is disabled.');

        return $this->start($request, $provider, true);
    }

    private function start(Request $request, string $provider, bool $connect)
    {
        $request->session()->forget('social_login');
        if (! $this->providers->enabled($provider)) {
            return $this->failure($connect, 'This sign-in provider is not configured yet. Please use another sign-in method.');
        }
        try {
            $redirect = Socialite::driver($this->providers->driver($provider))->redirect();
            $request->session()->put('social_login', ['provider' => $provider, 'connect' => $connect,
                'user_id' => $connect ? $request->user()->id : null, 'expires' => now()->addMinutes(10)->timestamp]);

            return $request->header('X-Inertia') ? Inertia::location($redirect->getTargetUrl()) : $redirect;
        } catch (Throwable $exception) {
            // Never log OAuth exceptions: request URLs and response bodies can contain tokens or secrets.
            return $this->failure($connect, 'Unable to start provider sign-in. Please try again later.');
        }
    }

    public function callback(Request $request, string $provider)
    {
        $context = $request->session()->pull('social_login');
        $connect = (bool) ($context['connect'] ?? false);
        if (! $context || ($context['provider'] ?? null) !== $provider || ($context['expires'] ?? 0) < now()->timestamp) {
            return $this->failure($connect, 'Your sign-in session expired. Please try again.');
        }
        if ($connect && (! $request->user() || $request->user()->id !== $context['user_id'])) {
            return $this->failure(false, 'Your account session changed. Sign in again before connecting a provider.');
        }
        if ($request->user() && ! $request->user()->status) {
            abort(403, 'This account is disabled.');
        }
        if (! $connect && $request->user()) {
            return redirect('/account/settings')->withErrors(['social' => 'Sign out before signing in as another account.']);
        }
        if (! $this->providers->enabled($provider)) {
            return $this->failure($connect, 'This sign-in provider is not configured yet.');
        }
        if ($request->has('error')) {
            return $this->failure($connect, 'Provider sign-in was cancelled or declined. Please try again.');
        }
        try {
            $remote = Socialite::driver($this->providers->driver($provider))->user();
            $identity = (string) $remote->getId();
            if ($identity === '' || strlen($identity) > 255) {
                throw ValidationException::withMessages(['social' => 'The provider did not return a valid account identity.']);
            }
            $user = DB::transaction(function () use ($request, $provider, $identity, $remote, $connect) {
                $linked = SocialAccount::where('provider', $provider)->where('provider_user_id', $identity)->lockForUpdate()->first();
                if ($linked) {
                    if ($connect && $linked->user_id !== $request->user()->id) {
                        throw ValidationException::withMessages(['social' => 'This provider account is already connected to another user.']);
                    }
                    $user = User::whereKey($linked->user_id)->lockForUpdate()->firstOrFail();
                } else {
                    $email = trim((string) $remote->getEmail());
                    $verified = $provider === 'github' || filter_var($remote->user['email_verified'] ?? $remote->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    // Socialite's GitHub driver retrieves only the verified primary email using user:email.
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || ! $verified) {
                        throw ValidationException::withMessages(['social' => 'The provider must share a verified email address. Check its email permissions or register with email instead.']);
                    }
                    if ($connect) {
                        $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                        if (SocialAccount::where('user_id', $user->id)->where('provider', $provider)->exists()) {
                            throw ValidationException::withMessages(['social' => 'A different account from this provider is already connected.']);
                        }
                    } else {
                        if (User::whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
                            throw ValidationException::withMessages(['social' => 'An account with this email already exists. Sign in using your existing method, then connect this provider in Account settings.']);
                        }
                        $user = new User;
                        $user->forceFill(['name' => Str::limit(trim((string) $remote->getName()) ?: Str::before($email, '@'), 255, ''),
                            'email' => $email, 'email_verified_at' => now(), 'role_id' => 4, 'status' => 1, 'portfolio_status' => 0,
                            'password' => Hash::make(Str::random(64))])->save();
                    }
                    abort_unless($user->status, 403, 'This account is disabled.');
                    SocialAccount::create(['user_id' => $user->id, 'provider' => $provider, 'provider_user_id' => $identity]);
                }
                abort_unless($user->status, 403, 'This account is disabled.');

                return $user;
            });
        } catch (ValidationException $exception) {
            return $this->failure($connect, $exception->errors()['social'][0]);
        } catch (InvalidStateException $exception) {
            return $this->failure($connect, 'Your sign-in session expired. Please try again.');
        } catch (HttpException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return $this->failure($connect, 'This account may already be registered or connected. Try signing in again, or connect it from Account settings.');
        } catch (Throwable $exception) {
            return $this->failure($connect, 'Unable to complete provider sign-in. Please try again later.');
        }
        if ($connect) {
            return redirect('/account/settings')->with('success', SocialProviders::PROVIDERS[$provider].' connected successfully.');
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/home');
    }

    private function failure(bool $connect, string $message)
    {
        return redirect($connect ? '/account/settings' : '/login')->withErrors(['social' => $message]);
    }
}
