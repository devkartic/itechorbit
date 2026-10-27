<?php

namespace App\Http\Middleware;

use App\Models\Admin\ServiceInfo;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [...parent::share($request),
            'recaptcha' => ['enabled' => (bool) config('recaptcha.enabled'), 'version' => config('recaptcha.version'), 'siteKey' => (string) config('recaptcha.site_key')],
            'site' => ['name' => 'iTechOrbit', 'url' => rtrim(config('app.url'), '/'), 'aiService' => fn () => $request->routeIs('blogs.*', 'public.services.*', 'public.products.*', 'public.work*')
                ? ServiceInfo::published()->where('icon', 'sparkles')->where('highlight', true)->first(['title', 'slug'])?->only(['title', 'slug']) : null],
            'auth' => ['user' => $request->user() ? [...$request->user()->only(['id', 'name']), 'publicProfileUrl' => $request->user()->status && $request->user()->portfolio_status && $request->user()->portfolio_id ? '/portfolios/'.rawurlencode($request->user()->portfolio_id) : null] : null, 'csrfToken' => $request->session()->token()],
            'flash' => ['success' => fn () => $request->session()->get('success'), 'status' => fn () => $request->session()->get('status')],
        ];
    }
}
