<?php
/**
 * ReceiptHelper
 * Centralized logic for generating receipt HTML strings and PDFs.
 */

namespace App\Helpers;

use PDO;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReceiptHelper {
    /**
     * Generate HTML string for a receipt
     */
    public static function getHtml($db, $tenantId, $transactionId, $receiptNo = null) {
        if (!$transactionId && !$receiptNo) return "";

        $query = "
            SELECT pt.*, fi.name as fee_item_name, fi.amount as fee_item_amount,
                   u.name as student_name, u.email as student_email, u.phone,
                   COALESCE(JSON_UNQUOTE(JSON_EXTRACT(s.permanent_address, '$.district')), '') as student_address,
                   s.roll_no, c.name as course_name, b.name as batch_name,
                   fr.amount_due, fr.amount_paid as record_paid, fr.fine_applied,
                   t.name as institute_name, t.address as institute_address,
                   t.phone as institute_contact, t.email as institute_email,
                   t.logo_path as institute_logo,
                   COALESCE(t.pan_number, '') as institute_pan
            FROM payment_transactions pt
            JOIN fee_records fr ON pt.fee_record_id = fr.id
            JOIN fee_items fi ON fr.fee_item_id = fi.id
            JOIN students s ON pt.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            LEFT JOIN batches b ON e.batch_id = b.id
            LEFT JOIN courses c ON b.course_id = c.id
            LEFT JOIN tenants t ON pt.tenant_id = t.id
            WHERE pt.tenant_id = :tenant
        ";

        $params = ['tenant' => $tenantId];
        if ($transactionId) {
            $query .= " AND pt.id = :tid";
            $params['tid'] = $transactionId;
        } else {
            $query .= " AND pt.receipt_number = :rno";
            $params['rno'] = $receiptNo;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$transactions) return "";
        
        $txn = $transactions[0];

        $logoPath = $txn['institute_logo'] ?? '';
        $logoUrl = '';
        if ($logoPath) {
            if (strpos($logoPath, '/uploads/') === 0 && strpos($logoPath, '/public/') !== 0) {
                $logoPath = '/public' . $logoPath;
            }
            $logoUrl = (defined('APP_URL') ? APP_URL : '') . $logoPath;
        }

        $totalPaid = 0;
        $items = [];
        foreach ($transactions as $t) {
            $totalPaid += floatval($t['amount']);
            $items[] = [
                'name' => $t['fee_item_name'],
                'amount' => $t['amount']
            ];
        }

        // Resolved authenticated user for "Received By" footer
        $receivedByName = $_SESSION['userData']['name'] ?? 'Staff';
        $receivedByRole = $_SESSION['userData']['role'] ?? '';

        $receiptData = [
            'institute_name'     => $txn['institute_name'] ?? 'Institute',
            'institute_address'  => $txn['institute_address'] ?? '',
            'institute_contact'  => $txn['institute_contact'] ?? '',
            'institute_email'    => $txn['institute_email'] ?? '',
            'institute_logo_url' => $logoUrl,
            'institute_pan'      => $txn['institute_pan'] ?? '',
            'receipt_no'         => $txn['receipt_number'],
            'date_ad'            => $txn['payment_date'],
            'date_bs'            => \App\Helpers\DateUtils::adToBs($txn['payment_date'], 'Y-m-d', 'en'),
            'student_name'       => $txn['student_name'],
            'student_email'      => $txn['student_email'] ?? '',
            'course_name'        => $txn['course_name'] ?? '',
            'batch_name'         => $txn['batch_name'] ?? '',
            'course_fee'         => floatval($txn['amount_due']),
            'paid_amount'        => $totalPaid,
            'previous_payments'  => max(0, floatval($txn['record_paid']) - $totalPaid),
            'remaining'          => max(0, floatval($txn['amount_due']) - floatval($txn['record_paid'])),
            'fine_amount'        => $txn['fine_applied'] ?? 0,
            'address'            => $txn['student_address'] ?? '',
            'contact_number'     => $txn['phone'] ?? '',
            'payment_mode'       => $txn['payment_method'],
            'transaction_id'     => $txn['id'],
            'remarks'            => $txn['notes'] ?? '',
            'items'              => $items,
            // Dynamic user attribution – from authenticated session
            'received_by_name'   => $receivedByName,
            'received_by_role'   => $receivedByRole,
        ];

        ob_start();
        // $isPdf is locally available from parameter
        require base_path('scripts/receipt_template.php');
        return ob_get_clean();
    }

    /**
     * Generate PDF and return path
     */
    public static function generatePdf($db, $tenantId, $transactionId, $receiptNo = null) {
        $html = self::getHtml($db, $tenantId, $transactionId, $receiptNo, true);
        if (!$html) return null;

        $pdfDir = __DIR__ . '/../../../uploads/receipts/';
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);
        
        $filename = 'receipt_' . ($receiptNo ?: $transactionId) . '.pdf';
        $pdfPath = $pdfDir . $filename;

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            file_put_contents($pdfPath, $dompdf->output());
            
            // NEW: Write receipt_path back to database
            if ($db && ($transactionId || $receiptNo)) {
                $relativePath = 'public/uploads/receipts/' . $filename;
                $updateQuery = "UPDATE payment_transactions SET receipt_path = :path WHERE tenant_id = :tenant";
                $updateParams = ['path' => $relativePath, 'tenant' => $tenantId];
                
                if ($transactionId) {
                    $updateQuery .= " AND id = :tid";
                    $updateParams['tid'] = $transactionId;
                } else {
                    $updateQuery .= " AND receipt_number = :rno";
                    $updateParams['rno'] = $receiptNo;
                }
                
                $stmt = $db->prepare($updateQuery);
                try { $stmt->execute($updateParams); } catch (\Exception $e) { /* Log error if needed */ }
            }
            
            return $pdfPath;
        } catch (\Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            return null;
        }
    }
}
