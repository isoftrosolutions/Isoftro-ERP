<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Load legacy logic
require_once __DIR__ . '/legacy.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.superadmin' => \App\Http\Middleware\SuperAdminAuth::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/api/login',
            '/api/admin/*',
            '/api/frontdesk/*',
            '/api/student/*',
            '/api/institute_search.php',
            '/api/superadmin/*',
            '/api/super-admin/*',
            '/api/update_plan_features.php',
            '/api/auth/change-password',
            '/auth/send_password_reset',
            '/auth/verify-otp',
            '/auth/reset-password',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
