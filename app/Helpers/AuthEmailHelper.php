<?php

namespace App\Helpers;

/**
 * Handles all authentication and security related emails.
 */
class AuthEmailHelper extends MailHelper
{
    /**
     * Send a password reset OTP email synchronously (not queued).
     * OTPs are time-critical — the user is waiting on-screen for this.
     */
    public static function sendPasswordResetOtp(\PDO $db, int $tenantId, string $userEmail, string $userName, string $otp): bool
    {
        $payload = [
            'email' => $userEmail,
            'student_name' => $userName,
            'reset_token' => $otp,
            'template_key' => 'password_reset_request'
        ];
        
        // Send synchronously — OTPs can't wait for the background worker
        return self::processJob($db, $tenantId, 'password_reset', $payload);
    }

    /**
     * Dispatch a password change success notification.
     */
    public static function notifyPasswordChanged(\PDO $db, int $tenantId, string $userEmail, string $userName): bool
    {
        $payload = [
            'email' => $userEmail,
            'student_name' => $userName,
            'current_date' => date('Y-m-d H:i:s'),
            'template_key' => 'password_changed_success'
        ];
        
        return self::processJob($db, $tenantId, 'security_alert', $payload);
    }
}
