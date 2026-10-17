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
        if (! $request->expectsJson() && (in_array($status, [403, 404, 503]) || ($status === 500 && ! config('app.debug')))) {
            return Inertia::render('Shared/Error', ['status' => $status, 'seo' => [
                'title' => $status.' | iTechOrbit', 'description' => 'The requested page is unavailable.',
                'canonical' => rtrim(config('app.url'), '/').$request->getPathInfo(), 'noindex' => true,
            ]])->toResponse($request)->setStatusCode($status);
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
