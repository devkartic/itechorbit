<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'nonce-".Vite::cspNonce()."'".$dev."; style-src 'self' 'unsafe-inline'".$dev."; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'".$dev."; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
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
