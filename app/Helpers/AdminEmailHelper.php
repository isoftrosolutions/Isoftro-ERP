<?php

namespace App\Helpers;

/**
 * Handles all administrative and general announcement emails.
 */
class AdminEmailHelper extends MailHelper
{
    /**
     * Send a general announcement (synchronous).
     */
    public static function sendAnnouncement(\PDO $db, int $tenantId, array $announcementData): bool
    {
        $payload = array_merge($announcementData, ['template_key' => 'general_announcement']);
        return self::processJob($db, $tenantId, 'announcement', $payload);
    }

    /**
     * Send account status change notification (synchronous).
     */
    public static function notifyAccountStatus(\PDO $db, int $tenantId, array $accountData, string $status): bool
    {
        $templateKey = ($status === 'suspended') ? 'account_suspended' : 'account_reactivated';
        $payload = array_merge($accountData, [
            'template_key' => $templateKey,
            ($status === 'suspended' ? 'suspension_date' : 'reactivation_date') => date('Y-m-d')
        ]);
        return self::processJob($db, $tenantId, 'admin_notice', $payload);
    }

    /**
     * Send leave request status update (synchronous).
     */
    public static function notifyLeaveStatus(\PDO $db, int $tenantId, array $leaveData): bool
    {
        $payload = array_merge($leaveData, ['template_key' => 'leave_request_status']);
        return self::processJob($db, $tenantId, 'admin_notice', $payload);
    }
}
