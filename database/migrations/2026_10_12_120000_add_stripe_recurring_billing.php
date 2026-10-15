<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_status')->nullable();
            $table->boolean('stripe_cancel_at_period_end')->default(false);
        });

        Schema::create('stripe_checkout_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('billing_cycle', 10);
            $table->decimal('base_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('creating');
            $table->string('stripe_session_id')->nullable()->unique();
            $table->timestamp('expires_at');
            $table->json('metadata');
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['coupon_id', 'status', 'expires_at']);
        });

        Schema::table('tenant_payments', function (Blueprint $table): void {
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->foreignUuid('stripe_checkout_attempt_id')->nullable()->constrained('stripe_checkout_attempts')->restrictOnDelete();
        });

        Schema::create('stripe_webhook_events', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('event_type');
            $table->timestamp('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
        Schema::table('tenant_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stripe_checkout_attempt_id');
            $table->dropUnique(['stripe_invoice_id']);
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropColumn(['stripe_invoice_id', 'stripe_subscription_id']);
        });
        Schema::dropIfExists('stripe_checkout_attempts');
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique(['stripe_customer_id']);
            $table->dropUnique(['stripe_subscription_id']);
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id', 'stripe_status', 'stripe_cancel_at_period_end']);
        });
    }
};
