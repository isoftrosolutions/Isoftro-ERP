<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log security violations
            if (str_contains($e->getMessage(), 'Tenant isolation violation') ||
                str_contains($e->getMessage(), 'Invalid tenant access')) {
                \Log::warning('Security violation attempt', [
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                    'url' => request()->fullUrl(),
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Prevent information leakage in production
        if (app()->environment('production')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'An error occurred. Please try again.',
                    'code' => 500
                ], 500);
            }

            // For web requests, show generic error page
            if ($exception instanceof HttpException) {
                $statusCode = $exception->getStatusCode();
            } else {
                $statusCode = 500;
            }

            return response()->view('errors.generic', [
                'message' => 'Something went wrong. Please contact support if this persists.'
            ], $statusCode);
        }

        return parent::render($request, $exception);
    }
}