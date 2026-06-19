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

        // Route middleware aliases used across the app.
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // Always redirect guests to the login route.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Render branded error pages for the common HTTP statuses while
        // keeping JSON responses uniform for API/XHR clients.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                return response()->json([
                    'error' => [
                        'code' => match (true) {
                            $e instanceof \Illuminate\Validation\ValidationException => 'VALIDATION_ERROR',
                            $e instanceof \Illuminate\Auth\AuthenticationException => 'UNAUTHENTICATED',
                            $e instanceof \Illuminate\Auth\Access\AuthorizationException => 'FORBIDDEN',
                            $status === 404 => 'NOT_FOUND',
                            default => 'SERVER_ERROR',
                        },
                        'message' => $e->getMessage() ?: 'An unexpected error occurred.',
                        'details' => $e instanceof \Illuminate\Validation\ValidationException
                            ? $e->errors()
                            : null,
                    ],
                ], $status);
            }

            return null; // fall back to Blade error views
        });
    })->create();
