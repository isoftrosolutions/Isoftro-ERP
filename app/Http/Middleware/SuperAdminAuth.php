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
        error_log("[SuperAdminAuth] Handling request: " . $request->url());
        // Check if user is logged in (using global function from config.php)
        if (!isLoggedIn()) {
            error_log("[SuperAdminAuth] User not logged in");
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            return redirect('/auth/login');
        }

        // Check for superadmin role
        $user = getCurrentUser();
        error_log("[SuperAdminAuth] Current user role: " . ($user['role'] ?? 'none'));
        if ($user['role'] !== 'superadmin' && $user['role'] !== 'super-admin') {
            error_log("[SuperAdminAuth] Forbidden: Not a super admin");
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Forbidden: Super Admin access required'], 403);
            }
            abort(403, 'Forbidden: Super Admin access required');
        }

        return $next($request);
    }
}
