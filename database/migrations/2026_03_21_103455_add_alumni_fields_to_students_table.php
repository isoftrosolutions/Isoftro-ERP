<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
            $table->integer('alumni_year')->nullable()->after('is_active');
            $table->string('completion_status')->nullable()->after('alumni_year');
            $table->text('alumni_remarks')->nullable()->after('completion_status');
        });

        // Update existing status enum (using raw statement to ensure compatibility)
        DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('active', 'alumni', 'dropped') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'alumni_year', 'completion_status', 'alumni_remarks']);
        });

        // Revert status enum
        DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('active', 'inactive', 'on-leave', 'graduated', 'alumni', 'dropped') DEFAULT 'active'");
    }
};
