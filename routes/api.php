<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SuperAdminController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\AccountingController;
use App\Http\Controllers\API\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Authenticated routes
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Super Admin Routes
    Route::middleware('super_admin')->prefix('super')->group(function () {
        Route::get('/tenants', [SuperAdminController::class, 'tenants']);
        Route::get('/stats', [SuperAdminController::class, 'stats']);
        Route::post('/tenants/{id}/modules', [SuperAdminController::class, 'assignModules']);
        Route::post('/impersonate/{userId}', [SuperAdminController::class, 'impersonate']);
        Route::post('/stop-impersonation', [SuperAdminController::class, 'stopImpersonation']);

        // ─── ADD-ON FEATURES MANAGEMENT ───
        // List all available add-ons
        Route::get('/addons', [SuperAdminController::class, 'getAvailableAddons']);
        // Get add-on details
        Route::get('/addons/{addonId}', [SuperAdminController::class, 'getAddonDetails']);
        // Create new add-on feature
        Route::post('/addons', [SuperAdminController::class, 'createAddon']);
        // Get pricing summary
        Route::get('/addons/pricing', [SuperAdminController::class, 'getAddonPricing']);

        // ─── TENANT ADD-ON MANAGEMENT ───
        // Get add-ons for a tenant
        Route::get('/tenants/{tenantId}/addons', [SuperAdminController::class, 'getTenantAddons']);
        // Assign single add-on to tenant
        Route::post('/tenants/{tenantId}/addons/{addonId}', [SuperAdminController::class, 'assignAddonToTenant']);
        // Assign multiple add-ons at once
        Route::post('/tenants/{tenantId}/addons/batch', [SuperAdminController::class, 'assignMultipleAddons']);
        // Remove add-on from tenant
        Route::delete('/tenants/{tenantId}/addons/{addonId}', [SuperAdminController::class, 'removeAddonFromTenant']);
        // Get add-on usage
        Route::get('/tenants/{tenantId}/addons/{addonId}/usage', [SuperAdminController::class, 'getAddonUsage']);
    });

    // Module-specific Routes
    
    // Academic / Students
    Route::middleware('module:academic')->prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/{id}', [StudentController::class, 'show']);
        Route::put('/{id}', [StudentController::class, 'update']);
        Route::delete('/{id}', [StudentController::class, 'destroy']);
    });

    // Accounting / Finance
    Route::middleware('module:finance')->prefix('accounting')->group(function () {
        Route::get('/transactions', [AccountingController::class, 'index']);
        Route::post('/transactions', [AccountingController::class, 'store']);
        Route::get('/summary', [AccountingController::class, 'summary']);
    });

    // Attendance
    Route::middleware('module:attendance')->prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::post('/', [AttendanceController::class, 'store']);
        Route::get('/student/{studentId}', [AttendanceController::class, 'studentSummary']);
    });
});
