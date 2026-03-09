<?php

namespace App\Services;

use Exception;

/**
 * Service to handle online class integrations (Zoom, Google Meet, etc.)
 * Provides mock implementations for API calls.
 */
class OnlineClassService
{
    /**
     * Schedule a meeting via Zoom (Mock)
     */
    public function scheduleZoomMeeting(array $data): array
    {
        error_log("[Zoom] Mock scheduling: " . json_encode($data));
        
        $meetingId = str_pad((string)rand(100000000, 999999999), 11, '0', STR_PAD_LEFT);
        $password = substr(md5((string)time()), 0, 8);
        
        return [
            'success' => true,
            'meeting_id' => $meetingId,
            'password' => $password,
            'join_url' => "https://zoom.us/j/{$meetingId}?pwd={$password}",
            'start_url' => "https://zoom.us/s/{$meetingId}?role=host",
            'raw' => ['provider' => 'zoom', 'status' => 'waiting']
        ];
    }

    /**
     * Schedule a meeting via Google Meet (Mock)
     */
    public function scheduleGoogleMeet(array $data): array
    {
        error_log("[GoogleMeet] Mock scheduling: " . json_encode($data));
        
        $meetCode = substr(md5((string)time()), 0, 3) . "-" . substr(md5((string)time()), 4, 4) . "-" . substr(md5((string)time()), 9, 3);
        
        return [
            'success' => true,
            'meeting_id' => $meetCode,
            'password' => null,
            'join_url' => "https://meet.google.com/{$meetCode}",
            'start_url' => "https://meet.google.com/{$meetCode}",
            'raw' => ['provider' => 'google_meet', 'status' => 'active']
        ];
    }

    /**
     * Get class attendance report
     */
    public function getClassAttendance(\PDO $db, int $tenantId, int $classId): array
    {
        $stmt = $db->prepare("
            SELECT oca.*, s.full_name as student_name, s.roll_no
            FROM online_class_attendance oca
            JOIN students s ON oca.student_id = s.id
            WHERE oca.online_class_id = :cid AND oca.tenant_id = :tid
            ORDER BY s.full_name ASC
        ");
        $stmt->execute(['cid' => $classId, 'tid' => $tenantId]);
        return $stmt->fetchAll();
    }
}
