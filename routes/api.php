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
Route::post('/login', [AuthController::class, 'login']);

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
