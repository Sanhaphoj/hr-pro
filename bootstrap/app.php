<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all proxies when behind a reverse proxy/tunnel
        $middleware->trustProxies(at: '*');

        // Apply the user's chosen UI language on every web request.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Route middleware aliases used across the app.
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
            // ?download=pdf on a printable document returns it as a PDF file.
            'pdf' => \App\Http\Middleware\RenderPdf::class,
        ]);

        // Always redirect guests to the login route.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Render branded error pages for the common HTTP statuses while
        // keeping JSON responses uniform for API/XHR clients.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                $status = match (true) {
                    $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                    $e instanceof \Illuminate\Validation\ValidationException => $e->status,
                    $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                    default => 500,
                };

                return response()->json([
                    'error' => [
                        'code' => match (true) {
                            $e instanceof \Illuminate\Validation\ValidationException => 'VALIDATION_ERROR',
                            $e instanceof \Illuminate\Auth\AuthenticationException => 'UNAUTHENTICATED',
                            $e instanceof \Illuminate\Auth\Access\AuthorizationException => 'FORBIDDEN',
                            $status === 404 => 'NOT_FOUND',
                            default => 'SERVER_ERROR',
                        },
                        // A 500's own message can carry SQL or paths; only debug shows it.
                        'message' => ($status >= 500 && ! config('app.debug')) || ! $e->getMessage()
                            ? 'An unexpected error occurred.'
                            : $e->getMessage(),
                        'details' => $e instanceof \Illuminate\Validation\ValidationException
                            ? $e->errors()
                            : null,
                    ],
                ], $status);
            }

            return null; // fall back to Blade error views
        });
    })->create();
