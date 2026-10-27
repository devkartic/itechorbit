<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Psr\Log\LogLevel;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'g-recaptcha-response',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);
        $status = $response->getStatusCode();
        // Asset consumers must receive a failed resource, not an Inertia document.
        // Navigating to the same missing file in a browser still shows our 404.
        $htmlNavigation = str_contains(strtolower($request->header('Accept', '')), 'text/html');
        $assetRequest = $request->is('build/*', 'storage/*', 'uploads/*')
            || in_array($request->header('Sec-Fetch-Dest'), ['script', 'style', 'image', 'font', 'audio', 'video', 'worker']);
        if ($status === 404 && ! $request->expectsJson() && ! $request->header('X-Inertia') && ! $htmlNavigation && $assetRequest) {
            return response('', 404)->header('X-Robots-Tag', 'noindex');
        }
        if (! $request->expectsJson() && (in_array($status, [403, 404, 503]) || ($status === 500 && ! config('app.debug')))) {
            // Unknown routes can fail before the web middleware shares layout
            // props. Supply safe defaults so SSR can render the header/footer.
            return Inertia::render('Shared/Error', [
                'site' => Inertia::getShared('site', ['name' => 'iTechOrbit', 'url' => rtrim(config('app.url'), '/'), 'aiService' => null]),
                'auth' => Inertia::getShared('auth', ['user' => null]),
                'flash' => Inertia::getShared('flash', []),
                'status' => $status, 'seo' => [
                    'title' => match ($status) {
                        403 => 'Access forbidden | iTechOrbit',
                        404 => 'Page not found | iTechOrbit',
                        default => $status.' | iTechOrbit',
                    },
                    'description' => $status === 404 ? 'The page or file you’re looking for may have moved, or no longer exists.' : 'The requested page is unavailable.',
                    'canonical' => rtrim(config('app.url'), '/').$request->getPathInfo(), 'noindex' => true,
                ],
            ])->toResponse($request)->setStatusCode($status);
        }

        return $response;
    }

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
