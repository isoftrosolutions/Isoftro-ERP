<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in (using global function from config.php)
        if (!isLoggedIn()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            return redirect('/auth/login');
        }

        // Check for superadmin role
        $user = getCurrentUser();
        if ($user['role'] !== 'superadmin') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Forbidden: Super Admin access required'], 403);
            }
            abort(403, 'Forbidden: Super Admin access required');
        }

        return $next($request);
    }
}
