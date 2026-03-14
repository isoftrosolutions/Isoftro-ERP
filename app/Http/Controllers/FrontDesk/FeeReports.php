<?php
/**
 * Fee Reports API Controller
 * Provides detailed financial reporting for the current tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// CSRF and role check via Middleware
require_once __DIR__ . '/../../Middleware/FrontDeskMiddleware.php';
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$userRole = $auth['role'];

$action = $_GET['action'] ?? 'summary';

try {
    $db = \DB::connection()->getPdo();

    if ($action === 'summary') {
        $stats = [];
        $stmt = $db->prepare("SELECT SUM(amount) FROM payment_transactions WHERE tenant_id = :tid AND payment_date = CURDATE() AND status = 'completed'");
        $stmt->execute(['tid' => $tenantId]);
        $stats['today_collection'] = (float)$stmt->fetchColumn() ?: 0.00;

        $stmt = $db->prepare("SELECT SUM(amount) FROM payment_transactions WHERE tenant_id = :tid AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) AND status = 'completed'");
        $stmt->execute(['tid' => $tenantId]);
        $stats['month_collection'] = (float)$stmt->fetchColumn() ?: 0.00;

        $stmt = $db->prepare("SELECT SUM(amount_due - amount_paid) FROM fee_records WHERE tenant_id = :tid AND (amount_due > amount_paid)");
        $stmt->execute(['tid' => $tenantId]);
        $stats['total_outstanding'] = (float)$stmt->fetchColumn() ?: 0.00;

        $stmt = $db->prepare("SELECT COUNT(DISTINCT student_id) FROM fee_records WHERE tenant_id = :tid AND due_date < CURDATE() AND amount_due > amount_paid");
        $stmt->execute(['tid' => $tenantId]);
        $stats['defaulter_count'] = (int)$stmt->fetchColumn();

        echo json_encode(['success' => true, 'data' => $stats]);
    } elseif ($action === 'collection_summary') {
        $start = $_GET['start'] ?? date('Y-m-d');
        $end = $_GET['end'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("
            SELECT payment_method, SUM(amount) as total, COUNT(*) as count 
            FROM payment_transactions 
            WHERE tenant_id = ? AND payment_date BETWEEN ? AND ?
            GROUP BY payment_method
        ");
        $stmt->execute([$tenantId, $start, $end]);
        $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $summary]);
    } elseif ($action === 'defaulters') {
        $query = "SELECT s.id as student_id, u.name as full_name, s.roll_no, b.name as batch_name,
                  SUM(fr.amount_due - fr.amount_paid) as total_due,
                  MIN(fr.due_date) as oldest_due_date
                  FROM fee_records fr
                  JOIN students s ON fr.student_id = s.id JOIN users u ON s.user_id = u.id
                  LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                  WHERE fr.tenant_id = :tid AND fr.due_date < CURDATE() AND fr.amount_due > fr.amount_paid
                  GROUP BY s.id, u.name, s.roll_no, b.name
                  ORDER BY total_due DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute(['tid' => $tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
    } elseif ($action === 'collection_trend') {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-$i months"));
            $monthEnd = date('Y-m-t', strtotime("-$i months"));
            $monthName = date('M', strtotime("-$i months"));

            $stmt = $db->prepare("SELECT SUM(amount) FROM payment_transactions WHERE tenant_id = :tid AND payment_date BETWEEN :s AND :e AND status = 'completed'");
            $stmt->execute(['tid' => $tenantId, 's' => $monthStart, 'e' => $monthEnd]);
            $amount = (float)$stmt->fetchColumn() ?: 0.00;

            $trend[] = ['month' => $monthName, 'amount' => $amount];
        }

        echo json_encode(['success' => true, 'data' => $trend]);
    } elseif ($action === 'detailed_collection') {
        $start = $_GET['start'] ?? date('Y-m-d');
        $end = $_GET['end'] ?? date('Y-m-d');
        $method = $_GET['payment_method'] ?? '';
        $batchId = $_GET['batch_id'] ?? '';
        
        $params = ['tid' => $tenantId, 'start' => $start, 'end' => $end];
        
        $query = "
            SELECT pt.*, u.name as student_name, s.roll_no, b.name as batch_name,
                   c.name as course_name, fi.name as fee_name
            FROM payment_transactions pt
            JOIN students s ON pt.student_id = s.id JOIN users u ON s.user_id = u.id
            JOIN fee_records fr ON pt.fee_record_id = fr.id
            JOIN fee_items fi ON fr.fee_item_id = fi.id
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
            LEFT JOIN courses c ON b.course_id = c.id
            WHERE pt.tenant_id = :tid 
            AND DATE(pt.payment_date) BETWEEN :start AND :end
            AND pt.status = 'completed'
        ";
        
        if ($method) {
            $query .= " AND pt.payment_method = :method";
            $params['method'] = $method;
        }
        if ($batchId) {
            $query .= " AND e.batch_id = :batch_id";
            $params['batch_id'] = $batchId;
        }
        
        $query .= " ORDER BY pt.payment_date DESC, pt.id DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } elseif ($action === 'batch_summary') {
        $query = "
            SELECT b.id as batch_id, b.name as batch_name, c.name as course_name,
                   SUM(fr.amount_due) as total_due,
                   SUM(fr.amount_paid) as total_paid,
                   SUM(fr.amount_due - fr.amount_paid) as outstanding_amount
            FROM fee_records fr
            JOIN batches b ON fr.batch_id = b.id
            JOIN courses c ON b.course_id = c.id
            WHERE fr.tenant_id = :tid
            GROUP BY b.id, b.name, c.name
            ORDER BY c.name, b.name
        ";
        $stmt = $db->prepare($query);
        $stmt->execute(['tid' => $tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } elseif ($action === 'discount_report') {
        $query = "
            SELECT fr.id as fee_record_id, fr.due_date, fr.fine_waived, fr.notes,
                   u.name as student_name, s.roll_no, b.name as batch_name,
                   fi.name as fee_name
            FROM fee_records fr
            JOIN students s ON fr.student_id = s.id JOIN users u ON s.user_id = u.id
            JOIN fee_items fi ON fr.fee_item_id = fi.id
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
            WHERE fr.tenant_id = :tid 
            AND fr.fine_waived > 0
            ORDER BY fr.updated_at DESC
            LIMIT 500
        ";
        $stmt = $db->prepare($query);
        $stmt->execute(['tid' => $tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
    } elseif ($action === 'export_excel' || $action === 'export_pdf') {
        $reportType = $_GET['report_type'] ?? 'collection';
        require_once __DIR__ . '/../../Services/ReportExportService.php';
        $exportService = new \App\Services\ReportExportService();
        
        $data = [];
        $headers = [];
        $title = '';
        $filename = '';

        if ($reportType === 'collection') {
            $start = $_GET['start'] ?? date('Y-m-d');
            $end = $_GET['end'] ?? date('Y-m-d');
            
            $stmt = $db->prepare("
                SELECT pt.receipt_number, DATE(pt.payment_date) as payment_date, u.name as full_name, s.roll_no, 
                       b.name as batch, pt.payment_method, pt.amount
                FROM payment_transactions pt
                JOIN students s ON pt.student_id = s.id JOIN users u ON s.user_id = u.id
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                WHERE pt.tenant_id = :tid 
                AND DATE(pt.payment_date) BETWEEN :start AND :end
                AND pt.status = 'completed'
                ORDER BY pt.payment_date DESC
            ");
            $stmt->execute(['tid' => $tenantId, 'start' => $start, 'end' => $end]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Receipt No', 'Date', 'Student Name', 'Roll No', 'Batch', 'Method', 'Amount'];
            $title = "Collection Report ($start to $end)";
            $filename = "Collection_Report_{$start}_{$end}";
        } elseif ($reportType === 'defaulters') {
            $query = "SELECT s.roll_no, u.name as student_name, b.name as batch_name,
                      SUM(fr.amount_due - fr.amount_paid) as total_due,
                      MIN(fr.due_date) as oldest_due_date
                      FROM fee_records fr
                      JOIN students s ON fr.student_id = s.id JOIN users u ON s.user_id = u.id
                      LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                      WHERE fr.tenant_id = :tid AND fr.due_date < CURDATE() AND fr.amount_due > fr.amount_paid
                      GROUP BY s.id, u.name, s.roll_no, b.name
                      ORDER BY total_due DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute(['tid' => $tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Roll No', 'Student Name', 'Batch', 'Total Due', 'Oldest Due Date'];
            $title = "Defaulters Report";
            $filename = "Defaulters_Report_" . date('Y-m-d');
        } elseif ($reportType === 'batch_summary') {
            $query = "
                SELECT b.name as batch_name, c.name as course_name,
                       SUM(fr.amount_due) as total_due,
                       SUM(fr.amount_paid) as total_paid,
                       SUM(fr.amount_due - fr.amount_paid) as outstanding_amount
                FROM fee_records fr
                JOIN batches b ON fr.batch_id = b.id
                JOIN courses c ON b.course_id = c.id
                WHERE fr.tenant_id = :tid
                GROUP BY b.id, b.name, c.name
                ORDER BY c.name, b.name
            ";
            $stmt = $db->prepare($query);
            $stmt->execute(['tid' => $tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Batch Name', 'Course Name', 'Total Due', 'Total Paid', 'Outstanding Amount'];
            $title = "Batch Summary Report";
            $filename = "Batch_Summary_" . date('Y-m-d');
        } elseif ($reportType === 'discount_report') {
            $query = "
                SELECT u.name as student_name, s.roll_no, b.name as batch_name,
                       fi.name as fee_name, fr.due_date, fr.fine_waived, fr.notes
                FROM fee_records fr
                JOIN students s ON fr.student_id = s.id JOIN users u ON s.user_id = u.id
                JOIN fee_items fi ON fr.fee_item_id = fi.id
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                WHERE fr.tenant_id = :tid 
                AND fr.fine_waived > 0
                ORDER BY fr.updated_at DESC
                LIMIT 500
            ";
            $stmt = $db->prepare($query);
            $stmt->execute(['tid' => $tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Student Name', 'Roll No', 'Batch', 'Fee Name', 'Due Date', 'Discount/Fine Waived', 'Notes'];
            $title = "Discount Report";
            $filename = "Discount_Report_" . date('Y-m-d');
        } elseif ($reportType === 'detailed_collection') {
            $start = $_GET['start'] ?? date('Y-m-d');
            $end = $_GET['end'] ?? date('Y-m-d');
            $method = $_GET['payment_method'] ?? '';
            $batchId = $_GET['batch_id'] ?? '';
            
            $params = ['tid' => $tenantId, 'start' => $start, 'end' => $end];
            $query = "
                SELECT DATE(pt.payment_date) as payment_date, pt.receipt_number, u.name as student_name, s.roll_no, b.name as batch_name,
                       fi.name as fee_name, pt.payment_method, pt.amount
                FROM payment_transactions pt
                JOIN students s ON pt.student_id = s.id JOIN users u ON s.user_id = u.id
                JOIN fee_records fr ON pt.fee_record_id = fr.id
                JOIN fee_items fi ON fr.fee_item_id = fi.id
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                WHERE pt.tenant_id = :tid 
                AND DATE(pt.payment_date) BETWEEN :start AND :end
                AND pt.status = 'completed'
            ";
            
            if ($method) {
                $query .= " AND pt.payment_method = :method";
                $params['method'] = $method;
            }
            if ($batchId) {
                $query .= " AND e.batch_id = :batch_id";
                $params['batch_id'] = $batchId;
            }
            
            $query .= " ORDER BY pt.payment_date DESC, pt.id DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Date', 'Receipt No', 'Student Name', 'Roll No', 'Batch', 'Fee Name', 'Method', 'Amount'];
            $title = "Detailed Collection Report ($start to $end)";
            $filename = "Detailed_Collection_{$start}_{$end}";
        } else {
            throw new Exception("Export for report type '$reportType' is not implemented yet.");
        }

        if ($action === 'export_excel') {
            $exportService->exportExcel($headers, $data, "{$filename}.xlsx", $title);
        } else {
            ob_start();
            echo '<style>body{font-family: Arial, sans-serif;} table{width: 100%; border-collapse: collapse;} th, td{border: 1px solid #ddd; padding: 8px; text-align: left;} th{background-color: #f2f2f2;}</style>';
            echo "<h2>{$title}</h2>";
            echo '<table>';
            echo '<tr>';
            foreach ($headers as $h) echo "<th>$h</th>";
            echo '</tr>';
            $totals = array_fill(0, count($headers), 0);
            $hasTotals = false;
            foreach ($data as $row) {
                echo '<tr>';
                $i = 0;
                foreach ($row as $key => $val) {
                    if (in_array(strtolower($key), ['amount', 'total_due', 'total_paid', 'outstanding_amount', 'fine_waived'])) {
                        echo "<td>" . number_format($val, 2) . "</td>";
                        $totals[$i] += (float)$val;
                        $hasTotals = true;
                    } else {
                        echo "<td>$val</td>";
                    }
                    $i++;
                }
                echo '</tr>';
            }
            if ($hasTotals) {
                echo '<tr>';
                for ($i = 0; $i < count($headers); $i++) {
                    if ($totals[$i] > 0 || in_array(strtolower($headers[$i]), ['amount', 'total due', 'total paid', 'outstanding amount', 'discount/fine waived'])) {
                        echo '<td><b>' . number_format($totals[$i], 2) . '</b></td>';
                    } elseif ($i === 0) {
                        echo '<td style="text-align:right"><b>Total</b></td>';
                    } else {
                        echo '<td></td>';
                    }
                }
                echo '</tr>';
            }
            echo '</table>';
            $html = ob_get_clean();
            $exportService->exportPdf($html, "{$filename}.pdf");
        }
    } else {
        throw new Exception("Invalid report action: " . $action);
    }

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
