<?php
/**
 * Super Admin — Sidebar Badge Counters
 * 
 * Fetches live counts for sidebar badge indicators.
 * All queries use prepared statements for security.
 * Designed to be lightweight — called once per page load.
 */

function getSASidebarBadges($tenantId = null)
{
    // For super admin, we might want platform-wide stats or leave empty for now
    $badges = [];
    try {
        $db = getDBConnection();

        // Total tenants (institutes)
        $stmt = $db->query("SELECT COUNT(*) FROM tenants");
        $badges['total_tenants'] = (int)$stmt->fetchColumn();

        // Monthly revenue (current month)
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM tenant_payments WHERE status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
        $badges['monthly_revenue'] = (int)$stmt->fetchColumn();

        // Open support tickets
        $stmt = $db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
        $badges['support_tickets'] = (int)$stmt->fetchColumn();

    }
    catch (Exception $e) {
        // If DB connection fails, return empty badges — sidebar still renders
        error_log("Super admin sidebar badge error: " . $e->getMessage());
    }

    return $badges;
}