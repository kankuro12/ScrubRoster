<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add invoice tracking fields to tenant_payments
        Schema::table('tenant_payments', function (Blueprint $table): void {
            $table->string('invoice_number', 50)->nullable()->unique()->after('gateway_transaction_id');
            $table->dateTime('invoice_sent_at')->nullable()->after('metadata');
            $table->string('invoice_recipient_email')->nullable()->after('invoice_sent_at');
        });

        // 2. Tenant Queries (Support & Platform Inquiry Hub)
        Schema::create('tenant_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category')->default('general'); // general, billing, quota_upgrade, compliance, technical_support
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('open'); // open, waiting_tenant_response, tenant_responded, resolved, closed
            $table->string('created_by_role')->default('tenant_admin'); // tenant_admin, platform_admin
            $table->dateTime('last_responded_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });

        // 3. Tenant Query Messages (Threaded responses between Platform Admin and Tenant Admin)
        Schema::create('tenant_query_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_query_id')->constrained('tenant_queries')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type')->default('tenant_admin'); // tenant_admin, platform_admin
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        // 4. Tenant Alerts & Notification History
        Schema::create('tenant_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type'); // trial_expiring, trial_expired, quota_warning, quota_exceeded, subscription_renewal, payment_succeeded, query_response, system_notice
            $table->string('severity')->default('info'); // info, warning, danger, success
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->dateTime('email_sent_at')->nullable();
            $table->string('email_recipient')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_alerts');
        Schema::dropIfExists('tenant_query_messages');
        Schema::dropIfExists('tenant_queries');

        Schema::table('tenant_payments', function (Blueprint $table): void {
            $table->dropColumn(['invoice_number', 'invoice_sent_at', 'invoice_recipient_email']);
        });
    }
};
