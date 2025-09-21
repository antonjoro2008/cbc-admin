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
        //
    })->create();