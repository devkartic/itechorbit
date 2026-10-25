<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Tests\TestCase;

class FrontendAssetsTest extends TestCase
{
    public function test_public_and_admin_page_styles_are_rendered_without_waiting_for_javascript(): void
    {
        $this->withVite();
        config(['app.asset_url' => null]);
        (new AppServiceProvider($this->app))->boot();
        $directory = sys_get_temp_dir().'/itechorbit-assets-'.Str::uuid();
        File::makeDirectory($directory.'/build', 0755, true);
        $this->app->usePublicPath($directory);
        $manifest = [
            'resources/js/app.tsx' => ['file' => 'assets/app.js', 'src' => 'resources/js/app.tsx', 'isEntry' => true],
            'node_modules/@fontsource/inter/files/inter-latin-600-normal.woff2' => ['file' => 'assets/font.woff2'],
        ];
        foreach (['Portfolio/Show', 'Website/Home', 'Admin/Resource'] as $component) {
            $name = str_replace('/', '-', $component);
            $manifest['resources/js/pages/'.$component.'.tsx'] = ['src' => 'resources/js/pages/'.$component.'.tsx',
                'file' => 'assets/'.$name.'.js', 'css' => ['assets/'.$name.'.css']];
            $manifest[$name.'.css'] = ['src' => $name.'.css', 'file' => 'assets/'.$name.'.css'];
        }
        file_put_contents($directory.'/build/manifest.json', json_encode($manifest));
        Vite::useHotFile($directory.'/hot');
        Route::middleware('web')->get('/_test/assets/{section}', fn ($section) => Inertia::render(match ($section) {
            'portfolio' => 'Portfolio/Show', 'website' => 'Website/Home', default => 'Admin/Resource',
        }));
        try {
            foreach (['portfolio' => 'Portfolio-Show', 'website' => 'Website-Home', 'admin' => 'Admin-Resource'] as $section => $name) {
                $response = $this->get('/_test/assets/'.$section)->assertOk();
                $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
                $this->assertMatchesRegularExpression('/<link[^>]+rel="stylesheet"[^>]+href="[^"]*'.preg_quote($name, '/').'\.css"/', $response->getContent());
                $response->assertDontSee('5173')->assertDontSee('/@vite/client');
                $response->assertSee('src="/build/assets/app.js"', false);
                $response->assertSee('href="/build/assets/'.$name.'.css"', false);
            }
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
