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
        // 1. Update acc_vouchers
        Schema::table('acc_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('acc_vouchers', 'date_bs')) {
                $table->string('date_bs', 10)->after('date')->nullable();
            }
            if (!Schema::hasColumn('acc_vouchers', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->after('narration')->nullable();
            }
            if (!Schema::hasColumn('acc_vouchers', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->after('created_by')->nullable();
            }
            if (!Schema::hasColumn('acc_vouchers', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->after('approved_by')->nullable();
            }
        });

        // 2. Update acc_ledger_postings
        Schema::table('acc_ledger_postings', function (Blueprint $table) {
            if (!Schema::hasColumn('acc_ledger_postings', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->after('id')->nullable();
            }
            if (!Schema::hasColumn('acc_ledger_postings', 'sub_ledger_type')) {
                $table->string('sub_ledger_type', 50)->after('account_id')->nullable();
            }
            if (!Schema::hasColumn('acc_ledger_postings', 'sub_ledger_id')) {
                $table->unsignedBigInteger('sub_ledger_id')->after('sub_ledger_type')->nullable();
            }
            
            // Add indexes manually with checks
            $indexes = Schema::getIndexes('acc_ledger_postings');
            $indexNames = array_column($indexes, 'name');
            
            if (!in_array('acc_ledger_postings_account_id_tenant_id_index', $indexNames)) {
                $table->index(['account_id', 'tenant_id']);
            }
            if (!in_array('acc_lp_tenant_subledger_idx', $indexNames)) {
                $table->index(['tenant_id', 'sub_ledger_type', 'sub_ledger_id'], 'acc_lp_tenant_subledger_idx');
            }
        });

        // 3. Update acc_accounts
        Schema::table('acc_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('acc_accounts', 'balance_type')) {
                $table->enum('balance_type', ['dr', 'cr'])->after('opening_balance')->default('dr');
            }
            if (!Schema::hasColumn('acc_accounts', 'is_system')) {
                $table->boolean('is_system')->after('balance_type')->default(false);
            }
            if (!Schema::hasColumn('acc_accounts', 'status')) {
                $table->enum('status', ['active', 'inactive'])->after('is_system')->default('active');
            }
        });

        // 4. New table: acc_voucher_approvals
        if (!Schema::hasTable('acc_voucher_approvals')) {
            Schema::create('acc_voucher_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('voucher_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('action', ['verified', 'approved', 'rejected', 'reversed']);
                $table->text('remarks')->nullable();
                $table->timestamps();
                
                $table->foreign('voucher_id')->references('id')->on('acc_vouchers')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_voucher_approvals');
        
        Schema::table('acc_accounts', function (Blueprint $table) {
            $table->dropColumn(['balance_type', 'is_system', 'status']);
        });

        Schema::table('acc_ledger_postings', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'sub_ledger_type', 'sub_ledger_id']);
        });

        Schema::table('acc_vouchers', function (Blueprint $table) {
            $table->dropColumn(['date_bs', 'total_amount', 'approved_by', 'verified_by']);
        });
    }
};
