<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_widget_file_keeps_its_content_type_caching_and_security_headers(): void
    {
        $response = $this->get('/ai-pro/widget.js')->assertOk()
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');

        $this->assertSame(resource_path('ai-pro/widget.js'), $response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString("object-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_streamed_and_json_responses_keep_their_payloads_and_security_headers(): void
    {
        Route::middleware('web')->get('/_test/security-stream', fn () => response()->stream(function () {
            echo 'Streamed content';
        }, 200, ['Content-Type' => 'text/plain']));
        Route::middleware('web')->get('/_test/security-json', fn () => response()->json(['status' => 'ok']));

        $this->get('/_test/security-stream')->assertOk()->assertStreamedContent('Streamed content')
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson('/_test/security-json')->assertOk()->assertExactJson(['status' => 'ok'])
            ->assertHeader('Content-Type', 'application/json')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_vite_fonts_are_allowed_only_in_local_development(): void
    {
        Route::middleware('web')->get('/_test/font-policy', fn () => response('Font policy'));
        $this->app->instance('env', 'local');
        $local = $this->get('/_test/font-policy')->assertOk()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("font-src 'self' data: http://localhost:5173 http://127.0.0.1:5173;", $local);
        $this->app->instance('env', 'production');
        $production = $this->get('/_test/font-policy')->assertOk()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("font-src 'self' data:;", $production);
        $this->assertStringNotContainsString('5173', $production);
    }
}
