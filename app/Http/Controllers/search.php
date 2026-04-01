<?php
/**
 * iSoftro ERP — Super Admin Live Search API
 * Platform Blueprint V3.0
 * 
 * AJAX endpoint for live search functionality
 * 
 * @module SuperAdmin
 * @version 1.0.0
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Include configuration
require_once __DIR__ . '/../config.php';

// Get search parameters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Initialize response
$response = [
    'success' => true,
    'query' => $query,
    'results' => [
        'tenants' => [],
        'users' => [],
        'plans' => [],
        'invoices' => [],
        'tickets' => []
    ],
    'counts' => [
        'tenants' => 0,
        'users' => 0,
        'plans' => 0,
        'invoices' => 0,
        'tickets' => 0,
        'total' => 0
    ]
];

// Validate query (minimum 2 characters)
if (strlen($query) < 2) {
    echo json_encode($response);
    exit;
}

try {
    $pdo = getDBConnection();
    $searchTerm = '%' . $query . '%';
    
    // Search Tenants
    if ($type === 'all' || $type === 'tenants') {
        $stmt = $pdo->prepare("
            SELECT id, name, email, domain, plan, status, created_at 
            FROM tenants 
            WHERE name LIKE ? OR email LIKE ? OR domain LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $response['results']['tenants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['counts']['tenants'] = count($response['results']['tenants']);
    }
    
    // Search Users
    if ($type === 'all' || $type === 'users') {
        $stmt = $pdo->prepare("
            SELECT id, email, name, role, tenant_id, status, created_at 
            FROM users 
            WHERE email LIKE ? OR name LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $response['results']['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['counts']['users'] = count($response['results']['users']);
    }
    
    // Search Plans
    if ($type === 'all' || $type === 'plans') {
        $stmt = $pdo->prepare("
            SELECT id, name, price, billing_cycle, status 
            FROM plans 
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY price ASC
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $response['results']['plans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['counts']['plans'] = count($response['results']['plans']);
    }
    
    // Search Invoices
    if ($type === 'all' || $type === 'invoices') {
        $stmt = $pdo->prepare("
            SELECT id, invoice_number, tenant_id, amount, status, created_at 
            FROM invoices 
            WHERE invoice_number LIKE ? OR tenant_id LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $response['results']['invoices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['counts']['invoices'] = count($response['results']['invoices']);
    }
    
    // Search Support Tickets
    if ($type === 'all' || $type === 'tickets') {
        $stmt = $pdo->prepare("
            SELECT id, subject, status, priority, tenant_id, created_at 
            FROM support_tickets 
            WHERE subject LIKE ? OR description LIKE ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $response['results']['tickets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['counts']['tickets'] = count($response['results']['tickets']);
    }
    
    // Calculate total
    $response['counts']['total'] = $response['counts']['tenants'] + $response['counts']['users'] + 
                                   $response['counts']['plans'] + $response['counts']['invoices'] + 
                                   $response['counts']['tickets'];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit;
