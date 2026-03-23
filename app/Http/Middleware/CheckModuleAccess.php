<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Super admins have access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check module access
        if (!$user->canAccessModule($moduleSlug)) {
            return response()->json([
                'success' => false,
                'message' => "Module '{$moduleSlug}' is not enabled for your tenant."
            ], 403);
        }

        return $next($request);
    }
}
