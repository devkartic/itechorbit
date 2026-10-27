<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('POST') || ! ($request->routeIs('public.inquiries', 'portfolios.inquiries', 'contacts.store', 'newsletters.store', 'password.email', 'password.update') || $request->is('login', 'register')) || ! config('recaptcha.enabled')) {
            return $next($request);
        }

        $token = $request->validate(['g-recaptcha-response' => ['required', 'string', 'max:4096']], [
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA check.',
        ])['g-recaptcha-response'];
        $actions = ['public.inquiries' => 'inquiry', 'portfolios.inquiries' => 'portfolio_inquiry', 'contacts.store' => 'portfolio_inquiry',
            'newsletters.store' => 'newsletter', 'password.email' => 'password_email', 'password.update' => 'password_reset'];
        $action = $actions[$request->route()->getName()] ?? ($request->is('login') ? 'login' : 'register');
        $valid = false;
        if (filled(config('recaptcha.site_key')) && filled(config('recaptcha.secret_key')) && config('recaptcha.hostnames')) {
            try {
                // Tokens are single use: never automatically retry verification.
                $response = Http::asForm()->connectTimeout(3)->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('recaptcha.secret_key'), 'response' => $token,
                ]);
                $valid = $response->successful() && $response->json('success') === true
                    && in_array($response->json('hostname'), config('recaptcha.hostnames'), true);
                if (config('recaptcha.version') === 'v3') {
                    $score = $response->json('score');
                    $threshold = config('recaptcha.minimum_score');
                    $valid = $valid && (is_float($score) || is_int($score)) && $score >= 0 && $score <= 1
                        && $threshold >= 0 && $threshold <= 1 && $score >= $threshold && $response->json('action') === $action;
                } elseif (config('recaptcha.version') !== 'v2') {
                    $valid = false;
                }
            } catch (ConnectionException) {
                // Neither tokens, secrets nor provider responses belong in logs.
            }
        }
        if (! $valid) {
            throw ValidationException::withMessages(['g-recaptcha-response' => 'The reCAPTCHA check could not be verified. Please try again.']);
        }

        return $next($request);
    }
}
