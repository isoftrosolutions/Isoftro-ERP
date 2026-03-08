<?php

namespace App\Helpers;

/**
 * Handles all finance-related emails (Receipts, Invoices, Reminders).
 */
class FinanceEmailHelper extends MailHelper
{
    /**
     * Send a payment receipt email (synchronous).
     */
    public static function sendReceipt(\PDO $db, int $tenantId, array $receiptData, string $pdfPath = ''): bool
    {
        $payload = array_merge($receiptData, [
            'pdf_path' => $pdfPath,
            'template_key' => $receiptData['amount_due'] > 0 ? 'payment_success_partial' : 'payment_success_full'
        ]);
        
        return self::processJob($db, $tenantId, 'payment_receipt', $payload);
    }

    /**
     * Send a fee reminder email (synchronous).
     */
    public static function sendFeeReminder(\PDO $db, int $tenantId, array $reminderData, int $daysRemaining): bool
    {
        $templateKey = 'fee_reminder_7days';
        if ($daysRemaining <= 0) $templateKey = 'fee_overdue_notice';
        elseif ($daysRemaining <= 3) $templateKey = 'fee_reminder_3days';
        
        $payload = array_merge($reminderData, ['template_key' => $templateKey]);
        return self::processJob($db, $tenantId, 'fee_reminder', $payload);
    }

    /**
     * Send an invoice notification (synchronous).
     */
    public static function sendInvoice(\PDO $db, int $tenantId, array $invoiceData): bool
    {
        $payload = array_merge($invoiceData, ['template_key' => 'invoice_generated']);
        return self::processJob($db, $tenantId, 'invoice_notice', $payload);
    }
}
