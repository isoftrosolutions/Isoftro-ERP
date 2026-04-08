<?php

namespace App\Http\Controllers\SuperAdmin;

class RevenueController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $mrr                  = $this->calculateMRR();
        $recentPayments       = $this->getRecentPayments();
        $yearlyRev            = $this->calculateYearly();
        $activeInstituteCount = $this->db->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn() ?: 1;
        
        return view('super-admin.revenue', [
            'mrr'                  => $mrr,
            'recentPayments'       => $recentPayments,
            'yearlyRev'            => $yearlyRev,
            'activeInstituteCount' => $activeInstituteCount,
        ]);
    }

    private function calculateYearly() {
        $stmt = $this->db->query("SELECT SUM(amount) FROM subscriptions WHERE status = 'active' AND billing_cycle = 'yearly'");
        return $stmt->fetchColumn() ?: 0;
    }

    public function invoicesView() {
        try {
            $payments = $this->db->query("
                SELECT tp.*, t.name as tenant_name, t.email as tenant_email, t.plan
                FROM tenant_payments tp
                JOIN tenants t ON tp.tenant_id = t.id
                ORDER BY tp.created_at DESC LIMIT 200
            ")->fetchAll();
        } catch (\Exception $e) {
            $payments = [];
        }
        include resource_path('views/super-admin/revenue-invoices.php');
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'revenue':
            case 'mrr':
            case 'payments':        return $this->index();
            case 'revenue-invoices':return $this->invoicesView();
            default:                return $this->index();
        }
    }

    private function calculateMRR() {
        try {
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(s.amount), 0)
                FROM subscriptions s
                WHERE s.status = 'active' AND s.billing_cycle = 'monthly'
            ");
            return $stmt->fetchColumn() ?: 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRecentPayments() {
        try {
            $stmt = $this->db->query("
                SELECT tp.*, t.name as tenant_name, t.plan
                FROM tenant_payments tp
                JOIN tenants t ON tp.tenant_id = t.id
                ORDER BY tp.created_at DESC
                LIMIT 100
            ");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function generateInvoice($paymentId) {
        // PDF generation logic utilizing Dompdf or similar...
        // For now, redirect to raw view or success response
        echo json_encode(['success' => true, 'url' => APP_URL . "/api/invoices/download/$paymentId"]);
    }
}
