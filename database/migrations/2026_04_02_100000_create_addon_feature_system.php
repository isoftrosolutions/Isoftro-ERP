<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add-on Features - Standalone features available separately from plans
        if (!Schema::hasTable('addon_features')) {
            Schema::create('addon_features', function (Blueprint $table) {
                $table->id();
                $table->string('addon_key', 50)->unique(); // 'advanced-analytics', 'sms-gateway', etc.
                $table->string('addon_name', 100); // Display name
                $table->text('description')->nullable();
                $table->decimal('monthly_price', 10, 2); // Pricing
                $table->decimal('annual_price', 10, 2)->nullable(); // Annual discount
                $table->text('features_included')->nullable(); // JSON or comma-separated
                $table->integer('sort_order')->default(0);
                $table->enum('category', ['analytics', 'integrations', 'communications', 'automation', 'compliance', 'support'])->default('integrations');
                $table->enum('status', ['active', 'inactive', 'beta'])->default('active');
                $table->boolean('requires_approval')->default(false); // For premium add-ons
                $table->timestamps();
                $table->index(['status', 'category']);
            });
        }

        // 2. Tenant Add-ons - Purchased or assigned add-ons for tenants
        if (!Schema::hasTable('tenant_addons')) {
            Schema::create('tenant_addons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('addon_id');
                $table->enum('status', ['active', 'suspended', 'expired', 'cancelled'])->default('active');
                $table->dateTime('activated_at')->nullable();
                $table->dateTime('expires_at')->nullable(); // For trial add-ons
                $table->decimal('price_paid', 10, 2)->nullable(); // Historical pricing
                $table->string('billing_cycle', 20)->default('monthly'); // 'monthly', 'annual'
                $table->string('order_id', 100)->nullable(); // For payment tracking
                $table->string('assigned_by', 50)->nullable(); // 'manual' or admin user ID
                $table->text('notes')->nullable(); // Why this add-on was assigned
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('addon_id')->references('id')->on('addon_features')->onDelete('cascade');
                $table->unique(['tenant_id', 'addon_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['expires_at', 'status']);
            });
        }

        // 3. Add-on Usage Tracking - For billing purposes
        if (!Schema::hasTable('addon_usage_logs')) {
            Schema::create('addon_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('addon_id');
                $table->string('metric_key', 100); // e.g., 'sms_count', 'api_calls', 'storage_gb'
                $table->bigInteger('usage_amount')->default(0);
                $table->dateTime('logged_at');
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('addon_id')->references('id')->on('addon_features')->onDelete('cascade');
                $table->index(['tenant_id', 'addon_id', 'metric_key']);
            });
        }

        // 4. Add-on Requirements/Compatibility
        if (!Schema::hasTable('addon_requirements')) {
            Schema::create('addon_requirements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('addon_id');
                $table->string('requirement_type', 50); // 'requires_plan', 'requires_addon', 'excludes_addon'
                $table->string('requirement_key', 100); // Plan key, feature key, or addon key
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->foreign('addon_id')->references('id')->on('addon_features')->onDelete('cascade');
                $table->index('addon_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_requirements');
        Schema::dropIfExists('addon_usage_logs');
        Schema::dropIfExists('tenant_addons');
        Schema::dropIfExists('addon_features');
    }
};
