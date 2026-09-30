<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Loaded once it exists: the first command or schedule creates it.
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SetSecurityHeaders::class);

        // The sidebar writes its state from the browser, where the cookie cannot be encrypted.
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?Response {
            $status = $response->statusCode();

            if ($response->request->expectsJson()) {
                return null;
            }

            if ($status === 419) {
                Inertia::flash('toast', ['type' => 'error', 'message' => __('foundation.http_error.expired')]);

                return back();
            }

            if (in_array($status, [403, 404, 429, 503], true) || ($status === 500 && ! config('app.debug'))) {
                // Prepared as Laravel prepares its own error pages: without a content type, a server
                // that adds none leaves browsers, held to nosniff, showing the page as text.
                return $response->render('http-error', ['status' => $status])
                    ->withSharedData()
                    ->toResponse($response->request)
                    ->prepare($response->request);
            }

            return null;
        });
    })->create();
