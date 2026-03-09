<?php

namespace App\Helpers;

/**
 * SMSHelper class providing core SMS gateway integration.
 * Supports multiple providers like eSewa (mock), Sparrow SMS, etc.
 */
class SMSHelper
{
    /**
     * Send an SMS to a recipient
     */
    public static function send(\PDO $db, int $tenantId, string $phone, string $message, ?int $recipientId = null, string $recipientType = 'other'): bool
    {
        // 1. Fetch SMS settings for the tenant
        $settings = self::getTenantSettings($db, $tenantId);
        
        if (!$settings || empty($settings['is_active'])) {
            error_log("[SMSHelper] SMS is disabled for tenant {$tenantId}");
            return false;
        }

        $provider = $settings['provider'] ?? 'sparrow'; // default
        $apiKey = $settings['api_key'] ?? '';
        $senderId = $settings['sender_id'] ?? 'HamroLabs';

        $status = 'failed';
        $response = '';

        try {
            // 2. Dispatch to provider
            switch (strtolower($provider)) {
                case 'sparrow':
                    $result = self::sendViaSparrow($phone, $message, $apiKey, $senderId);
                    $status = $result['success'] ? 'sent' : 'failed';
                    $response = $result['response'];
                    break;
                case 'nexmo':
                case 'vonage':
                    $result = self::sendViaVonage($phone, $message, $apiKey, $settings['api_secret'] ?? '', $senderId);
                    $status = $result['success'] ? 'sent' : 'failed';
                    $response = $result['response'];
                    break;
                case 'mock':
                default:
                    error_log("[SMSHelper] Mock SMS to {$phone}: {$message}");
                    $status = 'sent';
                    $response = 'Mock delivery success';
                    break;
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $response = $e->getMessage();
            error_log("[SMSHelper] Send Error: " . $e->getMessage());
        }

        // 3. Log the message
        self::logSMS($db, $tenantId, $phone, $message, $status, $response, $recipientId, $recipientType);

        return $status === 'sent';
    }

    /**
     * Get tenant SMS settings
     */
    private static function getTenantSettings(\PDO $db, int $tenantId): ?array
    {
        try {
            $stmt = $db->prepare("SELECT provider, api_key, api_secret, sender_id, is_active FROM sms_settings WHERE tenant_id = ? LIMIT 1");
            $stmt->execute([$tenantId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // If no settings found, return default mock for development
            if (!$row) {
                return [
                    'provider' => 'mock',
                    'is_active' => 1,
                    'sender_id' => 'HamroERP'
                ];
            }
            return $row;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Log SMS activity
     */
    private static function logSMS(\PDO $db, int $tenantId, string $phone, string $message, string $status, string $response, ?int $recipientId, string $recipientType): void
    {
        try {
            $stmt = $db->prepare("
                INSERT INTO communication_logs (tenant_id, type, recipient_contact, message, status, provider_response, recipient_id, recipient_type, sent_at)
                VALUES (?, 'sms', ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $tenantId, 
                $phone, 
                $message, 
                $status, 
                $response, 
                $recipientId, 
                $recipientType
            ]);
        } catch (\Exception $e) {
            error_log("[SMSHelper] Logging Error: " . $e->getMessage());
        }
    }

    /**
     * Sparrow SMS Integration
     */
    private static function sendViaSparrow(string $to, string $text, string $token, string $from): array
    {
        $args = [
            'token' => $token,
            'from'  => $from,
            'to'    => $to,
            'text'  => $text
        ];

        $url = "http://api.sparrowsms.com/v2/sms/?" . http_build_query($args);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => ($httpCode == 200),
            'response' => $response
        ];
    }

    /**
     * Vonage/Nexmo Integration
     */
    private static function sendViaVonage(string $to, string $text, string $key, string $secret, string $from): array
    {
        $url = "https://rest.nexmo.com/sms/json";
        $data = [
            'api_key' => $key,
            'api_secret' => $secret,
            'to' => $to,
            'from' => $from,
            'text' => $text
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode($response, true);
        $success = isset($resData['messages'][0]['status']) && $resData['messages'][0]['status'] == '0';

        return [
            'success' => $success,
            'response' => $response
        ];
    }
}
