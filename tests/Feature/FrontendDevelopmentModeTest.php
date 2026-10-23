<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Inertia\Inertia;
use Inertia\Ssr\SsrState;
use Tests\TestCase;

class FrontendDevelopmentModeTest extends TestCase
{
    public function test_vite_local_mode_skips_ssr_but_built_mode_keeps_it(): void
    {
        $this->withVite();
        $this->app->instance('env', 'local');
        config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);
        (new AppServiceProvider($this->app))->boot();
        $originalHotFile = Vite::hotFile();
        $hotFile = tempnam(sys_get_temp_dir(), 'itechorbit-vite-test-');
        Vite::useHotFile($hotFile);
        Route::middleware('web')->get('/_test/frontend-mode', fn () => Inertia::render('Auth/Index', ['mode' => 'login']));
        Http::fake(['*' => Http::response(['head' => [], 'body' => '<div>Built SSR verification</div>'])]);
        try {
            file_put_contents($hotFile, 'http://127.0.0.1:5173');
            $this->get('/_test/frontend-mode')->assertOk()->assertSee('http://127.0.0.1:5173/@vite/client', false);
            Http::assertNothingSent();
            unlink($hotFile);
            $this->app->forgetInstance(SsrState::class);
            $this->get('/_test/frontend-mode')->assertOk()->assertSee('Built SSR verification');
            Http::assertSentCount(1);
            config(['inertia.ssr.enabled' => false]);
            $this->app->forgetInstance(SsrState::class);
            $this->get('/_test/frontend-mode')->assertOk()->assertDontSee('Built SSR verification');
            Http::assertSentCount(1);
        } finally {
            Vite::useHotFile($originalHotFile);
            if (is_file($hotFile)) {
                unlink($hotFile);
            }
        }
    }
}
