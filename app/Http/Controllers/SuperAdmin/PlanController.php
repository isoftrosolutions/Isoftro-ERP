<?php

namespace App\Http\Controllers\SuperAdmin;

use PDO;

class PlanController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        try {
            // Get all plans from subscription_plans table
            $plansStmt = $this->db->query("
                SELECT p.*, 
                (SELECT COUNT(*) FROM tenants WHERE plan = p.slug AND status IN ('active', 'trial')) as active_tenants
                FROM subscription_plans p 
                ORDER BY p.sort_order ASC, p.id ASC
            ");
            $plansRaw = $plansStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get features for each plan
            $featuresStmt = $this->db->query("SELECT * FROM plan_features ORDER BY sort_order ASC");
            $featuresRaw = $featuresStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $featuresByPlan = [];
            foreach ($featuresRaw as $f) {
                $featuresByPlan[$f['plan_id']][] = $f;
            }

            $plans = [];
            foreach ($plansRaw as $p) {
                $plans[] = [
                    'id'             => $p['id'],
                    'slug'           => $p['slug'],
                    'name'           => $p['name'],
                    'price'          => (float)$p['price_monthly'],
                    'students'       => (int)$p['student_limit'],
                    'description'    => $p['description'],
                    'badge_text'     => $p['badge_text'],
                    'is_featured'    => (bool)$p['is_featured'],
                    'status'         => $p['status'],
                    'active_tenants' => (int)$p['active_tenants'],
                    'features'       => $featuresByPlan[$p['id']] ?? [],
                ];
            }

            // Get all system features for the "Global Feature Toggles" or feature management
            $systemFeaturesStmt = $this->db->query("SELECT * FROM system_features ORDER BY feature_name ASC");
            $systemFeatures = $systemFeaturesStmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            error_log("[PlanController] Error: " . $e->getMessage());
            $plans = [];
            $systemFeatures = [];
    }

        return view('super-admin.plans', [
            'plans' => $plans,
            'systemFeatures' => $systemFeatures
        ]);
    }

    public function flagsView() {
        try {
            $features = $this->db->query("SELECT * FROM system_features ORDER BY feature_name ASC")->fetchAll();
        } catch (\Exception $e) {
            $features = [];
    }
        include resource_path('views/super-admin/plans-flags.php');
    }

    public function assignView() {
        try {
            $tenants = $this->db->query("
                SELECT t.id, t.name, t.subdomain, t.plan, t.status,
                       p.name as plan_name
                FROM tenants t
                LEFT JOIN subscription_plans p ON p.slug = t.plan
                WHERE t.status != 'deleted'
                ORDER BY t.name ASC
            ")->fetchAll();
            $plans = $this->db->query("SELECT id, name, slug, price_monthly FROM subscription_plans WHERE status = 'active' ORDER BY sort_order ASC")->fetchAll();
        } catch (\Exception $e) {
            $tenants = [];
            $plans = [];
    }
        include resource_path('views/super-admin/plans-assign.php');
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'plans':
            case 'sub-plans':  return $this->index();
            case 'plans-flags':return $this->flagsView();
            case 'plans-assign':return $this->assignView();
            default:           return $this->index();
        }
    }
}
