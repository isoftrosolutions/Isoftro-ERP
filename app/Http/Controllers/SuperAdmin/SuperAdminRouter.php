<?php

namespace App\Http\Controllers\SuperAdmin;

class SuperAdminRouter {
    private $db;
    private $mapping = [
        'overview' => DashboardController::class,
        'tenants' => TenantController::class,
        'add-tenant' => TenantController::class,
        'edit-tenant' => TenantController::class,
        'view-tenant' => TenantController::class,
        'revenue' => RevenueController::class,
        'analytics' => AnalyticsController::class,
        'support' => SupportController::class,
        'logs' => LogController::class,
        'system' => SystemController::class,
        'settings' => SystemController::class,
        'plans' => PlanController::class
    ];

    public function __construct() {
        if (!isLoggedIn() || getCurrentUser()['role'] !== 'superadmin') {
            abort(403);
        }
        $this->db = getDBConnection();
    }

    public function handle($page, $action = 'index') {
        error_log("[SuperAdminRouter] Handling page: $page");
        $controllerClass = $this->mapping[$page] ?? DashboardController::class;
        error_log("[SuperAdminRouter] Controller: $controllerClass");
        if (!class_exists($controllerClass)) {
            error_log("[SuperAdminRouter] Class $controllerClass not found, falling back to DashboardController");
            $controllerClass = DashboardController::class;
        }

        $controller = new $controllerClass($this->db);
        
        // If controller has its own handle method for sub-pages
        if (method_exists($controller, 'handle')) {
            error_log("[SuperAdminRouter] Calling controller->handle()");
            return $controller->handle($page);
        }

        if (method_exists($controller, $action)) {
            return $controller->$action();
        } elseif (method_exists($controller, 'index')) {
            return $controller->index();
        } else {
            abort(404);
        }
    }
}
