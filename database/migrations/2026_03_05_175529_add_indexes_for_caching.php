<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $statements = [
            "CREATE INDEX idx_students_batch ON students(batch_id, tenant_id)",
            "CREATE INDEX idx_students_status ON students(status, tenant_id)",
            "CREATE INDEX idx_payments_due_date ON payments(due_date, status, tenant_id)",
            "CREATE INDEX idx_payments_balance ON payments(balance, tenant_id, status)",
            "CREATE INDEX idx_inquiries_status_created ON inquiries(status, created_at, tenant_id)",
            "CREATE INDEX idx_submissions_graded ON assignment_submissions(graded_at, tenant_id)",
            "CREATE INDEX idx_attendance_date_status ON attendance_records(date, status, tenant_id)",
            "CREATE INDEX idx_audit_logs_created ON audit_logs(created_at, tenant_id, user_id, action)"
        ];

        foreach ($statements as $statement) {
            try {
                \Illuminate\Support\Facades\DB::statement($statement);
            } catch (\Exception $e) {
                // safely ignore if index already exists or table is missing
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $statements = [
            "DROP INDEX idx_students_batch ON students",
            "DROP INDEX idx_students_status ON students",
            "DROP INDEX idx_payments_due_date ON payments",
            "DROP INDEX idx_payments_balance ON payments",
            "DROP INDEX idx_inquiries_status_created ON inquiries",
            "DROP INDEX idx_submissions_graded ON assignment_submissions",
            "DROP INDEX idx_attendance_date_status ON attendance_records",
            "DROP INDEX idx_audit_logs_created ON audit_logs"
        ];

        foreach ($statements as $statement) {
            try {
                \Illuminate\Support\Facades\DB::statement($statement);
            } catch (\Exception $e) {
                // safely ignore
            }
        }
    }
};
