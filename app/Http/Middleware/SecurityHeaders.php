<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Vite;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        Vite::useCspNonce();
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $dev = app()->environment('local') ? ' http://localhost:* http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:*' : '';
        $devFonts = app()->environment('local') ? ' http://localhost:5173 http://127.0.0.1:5173' : '';
        $view = $response instanceof Response ? $response->getOriginalContent() : null;
        $component = $view instanceof View ? ($view->getData()['page']['component'] ?? '') : '';
        $publicPage = str_starts_with($component, 'Website/') || str_starts_with($component, 'Portfolio/');
        $tagManager = $publicPage && trim((string) config('site.tag_manager_id')) !== '';
        $analytics = $publicPage && trim((string) config('site.analytics_id')) !== '';
        $googleScripts = $tagManager || $analytics ? ' https://*.googletagmanager.com' : '';
        $googleConnections = $tagManager || $analytics ? ' https://*.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com' : '';
        $googleFrames = $tagManager ? "; frame-src 'self' https://www.googletagmanager.com" : '';
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'nonce-".Vite::cspNonce()."'".$dev.$googleScripts."; style-src 'self' 'unsafe-inline'".$dev."; img-src 'self' data: blob: https:; font-src 'self' data:".$devFonts."; connect-src 'self'".$dev.$googleConnections.$googleFrames."; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        if ($request->user() || $request->is('login', 'register', 'password/*', 'email/*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
