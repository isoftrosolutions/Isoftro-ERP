<?php

namespace App\Helpers;

/**
 * Handles all student lifecycle emails (Registration, Verification, etc.).
 */
class StudentEmailHelper extends MailHelper
{
    /**
     * Send a welcome email with credentials to a new student (synchronous).
     */
    public static function sendWelcomeEmail(\PDO $db, int $tenantId, array $studentData): bool
    {
        return self::processJob($db, $tenantId, 'student_welcome', $studentData);
    }

    /**
     * Dispatch email verification link.
     */
    public static function sendEmailVerification(\PDO $db, int $tenantId, string $email, string $name, string $link): bool
    {
        $payload = [
            'student_email' => $email,
            'student_name' => $name,
            'verification_link' => $link,
            'template_key' => 'student_account_verification'
        ];
        return self::processJob($db, $tenantId, 'student_status', $payload);
    }

    /**
     * Dispatch profile update notification.
     */
    public static function notifyProfileUpdated(\PDO $db, int $tenantId, array $studentData): bool
    {
        $studentData['current_date'] = date('Y-m-d');
        $studentData['template_key'] = 'student_profile_updated';
        return self::processJob($db, $tenantId, 'student_status', $studentData);
    }
}
