<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'institution' => \App\Http\Middleware\InstitutionMiddleware::class,
            'parent' => \App\Http\Middleware\ParentMiddleware::class,
        ])->validateCsrfTokens(except: [
                    'api/login',
                    'api/register',
                ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication exceptions for API routes
        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please provide a valid Bearer token.',
                    'error' => 'Unauthenticated'
                ], 401);
            }
        });
        
        // Handle route not found exceptions for API routes
        $exceptions->render(function (Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please provide a valid Bearer token.',
                    'error' => 'Unauthenticated'
                ], 401);
            }
        });
    })->create();