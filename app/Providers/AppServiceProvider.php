<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ResetPassword::toMailUsing(function ($user, $token) {
            return (new MailMessage)->subject('Reset your password')->view(['html' => 'mail.auth', 'text' => 'mail.auth-text'], [
                'title' => 'Reset your password', 'recipientName' => $user->name,
                'intro' => 'We received a password reset request for your iTechOrbit account.',
                'actionLabel' => 'Reset password',
                'actionUrl' => url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)),
                'expiry' => 'This password reset link expires in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.',
                'outro' => 'If you did not request this, you can safely ignore this email. Your password will stay the same.',
            ]);
        });
        VerifyEmail::toMailUsing(function ($user, $url) {
            return (new MailMessage)->subject('Verify your email address')->view(['html' => 'mail.auth', 'text' => 'mail.auth-text'], [
                'title' => 'Verify your email address', 'recipientName' => $user->name,
                'intro' => 'Please verify your email address to finish setting up your iTechOrbit account.',
                'actionLabel' => 'Verify email address', 'actionUrl' => $url,
                'expiry' => 'This verification link expires in '.config('auth.verification.expire', 60).' minutes.',
                'outro' => 'If you did not create an account, you can safely ignore this email.',
            ]);
        });
        // Keep default build URLs on the browser's origin behind TLS proxies.
        // An explicitly configured asset CDN still uses Laravel's URL generator.
        Vite::createAssetPathsUsing(fn ($path, $secure) => config('app.asset_url')
            ? asset($path, $secure)
            : '/'.ltrim($path, '/'));
        if ($this->app->environment('local')) {
            // This Vite setup provides HMR, not an Inertia development SSR endpoint.
            // Keep the configured SSR policy when serving built assets.
            Inertia::disableSsr(fn () => Vite::isRunningHot() || ! config('inertia.ssr.enabled'));
        }
        // SSR supplies visible content; CSS and fonts should arrive before hydration code.
        Vite::useScriptTagAttributes(['fetchpriority' => 'low']);
        Vite::usePreloadTagAttributes(fn ($src, $url) => ['fetchpriority' => str_ends_with($url, '.css') ? 'high' : 'low']);
        RateLimiter::for('mail-notices', fn () => Limit::perMinute(config('notices.per_minute'))->by('mail-notices'));
    }
}
