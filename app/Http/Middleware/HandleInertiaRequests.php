<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [...parent::share($request),
            'site' => ['name' => 'iTechOrbit', 'url' => rtrim(config('app.url'), '/')],
            'auth' => ['user' => $request->user() ? [...$request->user()->only(['id', 'name']), 'publicProfileUrl' => $request->user()->status && $request->user()->portfolio_status && $request->user()->portfolio_id ? '/portfolios/'.rawurlencode($request->user()->portfolio_id) : null] : null, 'csrfToken' => $request->session()->token()],
            'flash' => ['success' => fn () => $request->session()->get('success'), 'status' => fn () => $request->session()->get('status')],
        ];
    }
}
