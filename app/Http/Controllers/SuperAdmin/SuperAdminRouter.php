<?php

namespace App\Http\Controllers\SuperAdmin;

class SuperAdminRouter {
    private $db;
    private $mapping = [
        'overview'            => DashboardController::class,
        // Tenants
        'tenants'             => TenantController::class,
        'add-tenant'          => TenantController::class,
        'edit-tenant'         => TenantController::class,
        'view-tenant'         => TenantController::class,
        'tenants-suspended'   => TenantController::class,
        // Plans
        'plans'               => PlanController::class,
        'plans-flags'         => PlanController::class,
        'plans-assign'        => PlanController::class,
        // Revenue
        'revenue'             => RevenueController::class,
        'revenue-invoices'    => RevenueController::class,
        // Analytics
        'analytics'           => AnalyticsController::class,
        // Support
        'support'             => SupportController::class,
        'support-resolved'    => SupportController::class,
        'support-impersonate' => SupportController::class,
        // System / Settings
        'system'              => SystemController::class,
        'system-maintenance'  => SystemController::class,
        'system-push'         => SystemController::class,
        'settings'            => SystemController::class,
        'settings-brand'      => SystemController::class,
        'settings-sms-tpl'    => SystemController::class,
        // Logs
        'logs'                => LogController::class,
        'logs-errors'         => LogController::class,
        'logs-api'            => LogController::class,
        // Profile
        'profile'             => ProfileController::class,
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
