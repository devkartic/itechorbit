<?php

namespace App\AiPro\Http;

use App\AiPro\Models\Site;
use Closure;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Schema;

class Cors extends HandleCors
{
    public function handle($request, Closure $next)
    {
        if (! $request->is('api/ai-pro/*')) {
            return parent::handle($request, $next);
        }
        $request->headers->set('Accept', 'application/json');
        if (! config('ai_pro.enabled') || ! Schema::hasTable('ai_pro_sites')) {
            return response()->json(['message' => 'iTechOrbit AI is unavailable.', 'status' => 'unavailable'], 503);
        }
        preg_match('#^api/ai-pro/v1/sites/([a-zA-Z0-9-]+)/#', $request->path(), $matches);
        $site = Site::where('public_id', $matches[1] ?? '')->where('enabled', true)->first();
        $origin = $request->header('Origin');
        // Same-origin GETs may omit Origin; accept only the browser's explicit same-origin metadata.
        if (! $origin && $request->isMethod('GET') && $request->header('Sec-Fetch-Site') === 'same-origin') {
            $origin = $request->getSchemeAndHttpHost();
        }
        // Browsers omit Fetch Metadata on ordinary HTTP development domains.
        // Accept a same-origin GET referrer only when no Origin or metadata exists.
        if (! $origin && $request->isMethod('GET') && ! $request->header('Sec-Fetch-Site')) {
            $referrer = parse_url($request->header('Referer') ?? '');
            if (isset($referrer['scheme'], $referrer['host'])) {
                $referrerOrigin = $referrer['scheme'].'://'.$referrer['host'].(isset($referrer['port']) ? ':'.$referrer['port'] : '');
                if ($referrerOrigin === $request->getSchemeAndHttpHost()) {
                    $origin = $referrerOrigin;
                }
            }
        }
        if (! $site || ! $origin || ! in_array($origin, $site->origins, true)) {
            return response()->json(['message' => 'Website or origin is not allowed.'], 403);
        }
        $request->attributes->set('ai_pro_site', $site);
        $response = $request->isMethod('OPTIONS') ? response('', 204) : $next($request);
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
        $response->headers->set('Vary', 'Origin', false);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
