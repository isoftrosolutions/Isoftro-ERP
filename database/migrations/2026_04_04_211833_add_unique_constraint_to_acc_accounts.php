<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any duplicate (tenant_id, code) rows that already exist before
        // adding the constraint. Keep the lowest id (oldest record) per pair.
        DB::statement("
            DELETE a FROM acc_accounts a
            INNER JOIN acc_accounts b
                ON  a.tenant_id = b.tenant_id
                AND a.code      = b.code
                AND a.id        > b.id
        ");

        Schema::table('acc_accounts', function (Blueprint $table) {
            $table->unique(['tenant_id', 'code'], 'acc_accounts_tenant_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('acc_accounts', function (Blueprint $table) {
            $table->dropUnique('acc_accounts_tenant_code_unique');
        });
    }
};
