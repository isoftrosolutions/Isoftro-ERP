<?php

namespace App\Http\Controllers\SuperAdmin;

use PDO;

class PlanController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        // Get plans with their features and active tenant counts
        try {
            // Get plan features from plan_features table
            $planFeatures = $this->db->query("SELECT * FROM plan_features ORDER BY plan_name, feature_name")->fetchAll();

            // Group features by plan
            $featuresByPlan = [];
            foreach ($planFeatures as $pf) {
                $featuresByPlan[$pf['plan_name']][] = $pf;
            }

            // Count active tenants per plan
            $tenantCountsRaw = $this->db->query("
                SELECT plan, COUNT(*) as cnt 
                FROM tenants 
                WHERE status IN ('active','trial') 
                GROUP BY plan
            ")->fetchAll();
            $tenantCounts = [];
            foreach ($tenantCountsRaw as $row) {
                $tenantCounts[$row['plan']] = $row['cnt'];
            }

            // Get distinct plan names from tenants table
            $planNames = array_unique(array_merge(
                array_keys($featuresByPlan),
                array_column($tenantCountsRaw, 'plan')
            ));

            $plans = [];
            foreach ($planNames as $name) {
                if (!$name) continue;

                // Try to get price from platform_settings or plan_features
                $priceRow = $this->db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
                $priceRow->execute(["plan_{$name}_price"]);
                $price = $priceRow->fetchColumn() ?: 0;

                $studentLimitRow = $this->db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
                $studentLimitRow->execute(["plan_{$name}_student_limit"]);
                $studentLimit = $studentLimitRow->fetchColumn() ?: 0;

                $smsRow = $this->db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
                $smsRow->execute(["plan_{$name}_sms_credits"]);
                $smsCredits = $smsRow->fetchColumn() ?: 0;

                $plans[] = [
                    'id'             => $name,
                    'name'           => ucfirst($name),
                    'price'          => (int)$price,
                    'students'       => (int)$studentLimit,
                    'sms'            => (int)$smsCredits,
                    'active_tenants' => $tenantCounts[$name] ?? 0,
                    'features'       => $featuresByPlan[$name] ?? [],
                ];
            }
        } catch (\Exception $e) {
            error_log("[PlanController] Error: " . $e->getMessage());
            $plans = [];
        }

        return view('super-admin.plans', ['plans' => $plans]);
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'plans': return $this->index();
            default:      return $this->index();
        }
    }
}
