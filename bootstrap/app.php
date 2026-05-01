<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Load legacy logic
require_once __DIR__ . '/legacy.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Apply tenant identify globally for all routes
        $middleware->append([
             \App\Http\Middleware\IdentifyTenant::class,
             \App\Http\Middleware\SecurityHeaders::class,
             \App\Http\Middleware\CorsMiddleware::class,
        ]);

        $middleware->alias([
            'auth.superadmin' => \App\Http\Middleware\SuperAdminAuth::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'module' => \App\Http\Middleware\CheckModuleAccess::class,
            'jwt.auth' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'tenant.identify' => \App\Http\Middleware\IdentifyTenant::class,
            'login.throttle' => \App\Http\Middleware\LoginRateLimitMiddleware::class,
            'api.throttle' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        $middleware->encryptCookies(except: [
            'token',
        ]);

        // Enable CSRF protection - only except API routes that use JWT
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/token',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
