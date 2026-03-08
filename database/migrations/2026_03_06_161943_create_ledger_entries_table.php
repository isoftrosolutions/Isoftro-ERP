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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('reference_type'); // e.g. 'payment', 'refund', 'invoice'
            $table->unsignedBigInteger('reference_id'); // e.g. transaction_id
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['debit', 'credit']);
            $table->string('description')->nullable();
            $table->date('entry_date');
            $table->timestamps();

            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
