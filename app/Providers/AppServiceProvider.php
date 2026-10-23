<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
