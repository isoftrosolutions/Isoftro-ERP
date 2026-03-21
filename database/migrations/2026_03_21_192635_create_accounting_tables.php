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
        // 1. Fiscal Years
        Schema::create('acc_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('name', 50); // e.g., 2080-81
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. Chart of Accounts
        Schema::create('acc_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('code', 20)->nullable();
            $table->string('name', 100);
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('acc_accounts')->onDelete('cascade');
            $table->boolean('is_group')->default(false); // True if it's a category/parent
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Vouchers
        Schema::create('acc_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('voucher_no', 20)->unique();
            $table->date('date');
            $table->enum('type', ['journal', 'receipt', 'payment', 'contra']);
            $table->text('narration')->nullable();
            $table->foreignId('fiscal_year_id')->constrained('acc_fiscal_years');
            $table->string('status', 20)->default('draft'); // draft, verified, approved, posted
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // 4. Ledger Postings
        Schema::create('acc_ledger_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('acc_vouchers')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('acc_accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_ledger_postings');
        Schema::dropIfExists('acc_vouchers');
        Schema::dropIfExists('acc_accounts');
        Schema::dropIfExists('acc_fiscal_years');
    }
};
