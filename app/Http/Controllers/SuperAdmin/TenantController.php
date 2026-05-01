<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\SuperAdmin\TenantModel;
use App\Models\SuperAdmin\AuditLogModel;

class TenantController {
    private $db;
    private $tenantModel;
    private $auditModel;

    public function __construct($db) {
        $this->db = $db;
        $this->tenantModel = new TenantModel($db);
        $this->auditModel = new AuditLogModel($db);
    }

    public function index() {
        $tenants = $this->tenantModel->getAll();
        include resource_path('views/super-admin/tenants.php');
    }

    public function create() {
        include resource_path('views/super-admin/add-tenant.php');
    }

    public function edit() {
        $id = request('id');
        $tenant = $this->tenantModel->find($id);
        $assignedModules = $this->tenantModel->getFeatures($id);
        
        return view('super-admin.edit-tenant', [
            'tenant' => $tenant,
            'assignedModules' => $assignedModules
        ]);
    }

    public function show() {
        $id = request('id');
        $tenant = $this->tenantModel->find($id);
        $assignedModules = $this->tenantModel->getFeatures($id);
        $auditLogs = $this->auditModel->getLogsByTenant($id, 10);
        
        return view('super-admin.view-tenant', [
            'tenant' => $tenant,
            'assignedModules' => $assignedModules,
            'auditLogs' => $auditLogs
        ]);
    }

    public function suspendedView() {
        try {
            $tenants = $this->db->query("
                SELECT t.*, (SELECT COUNT(*) FROM students s WHERE s.tenant_id = t.id) as student_count
                FROM tenants t WHERE t.status = 'suspended' ORDER BY t.updated_at DESC
            ")->fetchAll();
        } catch (\Exception $e) {
            $tenants = [];
    }
        include resource_path('views/super-admin/tenants-suspended.php');
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'tenants':           return $this->index();
            case 'add-tenant':        return $this->create();
            case 'edit-tenant':       return $this->edit();
            case 'view-tenant':       return $this->show();
            case 'tenants-suspended': return $this->suspendedView();
            default:
                if (method_exists($this, $action)) return $this->$action();
                return $this->index();
        }
    }

    public function store() {
        // Validation logic here
        // CSRF check already done via router
        $data = $_POST;
        if ($this->tenantModel->create($data)) {
            $this->auditModel->logAction(getCurrentUser()['id'], null, 'tenant_create', ['name' => $data['name']]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }

    public function suspend($id) {
        if ($this->tenantModel->updateStatus($id, 'suspended')) {
            $this->auditModel->logAction(getCurrentUser()['id'], $id, 'tenant_suspend');
            echo json_encode(['success' => true]);
        }
    }

    public function activate($id) {
        if ($this->tenantModel->updateStatus($id, 'active')) {
            $this->auditModel->logAction(getCurrentUser()['id'], $id, 'tenant_activate');
            echo json_encode(['success' => true]);
        }
    }

    public function impersonate($id) {
        // Security check
        if (getCurrentUser()['role'] !== 'superadmin') {
            die('Access Denied');
        }

        // Logic handled by SuperAdminController for now, but migrating soon...
        // For now redirect or return success for SPA
    }
}
