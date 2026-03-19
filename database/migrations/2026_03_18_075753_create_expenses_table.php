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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('expense_category_id');
            $table->decimal('amount', 15, 2);
            $table->date('date_ad');
            $table->string('date_bs', 10);
            $table->text('description')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'esewa', 'khalti', 'cheque'])->default('cash');
            $table->string('receipt_path')->nullable();
            $table->boolean('is_recurring')->default(false);
            
            // Payment method specific details
            $table->string('transaction_id')->nullable(); // for eSewa, Khalti
            $table->string('bank_name')->nullable(); // for Bank Transfer, Cheque
            $table->string('reference_number')->nullable(); // for Bank Transfer
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->enum('cheque_status', ['pending', 'cleared', 'bounced'])->default('pending');
            
            // Status and tracking
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'archived'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['tenant_id', 'status', 'date_ad']);
            $table->index('expense_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
