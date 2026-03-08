<?php
/**
 * QueueService
 * Handles dispatching jobs to the background queue.
 */

namespace App\Services;

class QueueService {
    protected $db;
    protected $table = 'job_queue';

    public function __construct() {
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
    }

    /**
     * Dispatch a job to the queue
     */
    public function dispatch($jobType, $payload, $tenantId = null) {
        $query = "INSERT INTO {$this->table} (tenant_id, job_type, payload) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([
            $tenantId,
            $jobType,
            json_encode($payload)
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get pending jobs
     */
    public function getPendingJobs($limit = 10) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update job status
     */
    public function updateStatus($jobId, $status, $errorMessage = null) {
        $query = "UPDATE {$this->table} SET status = ?, error_message = ?, attempts = attempts + 1 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $errorMessage, $jobId]);
    }
}
