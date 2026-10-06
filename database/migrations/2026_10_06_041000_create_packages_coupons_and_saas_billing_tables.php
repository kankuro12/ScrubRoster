<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Packages table
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->integer('max_employees')->default(5);
            $table->integer('trial_days')->default(14);
            $table->json('features')->nullable();
            $table->boolean('has_google_calendar')->default(true);
            $table->boolean('has_google_sheets')->default(true);
            $table->boolean('has_qr_login')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Coupons table
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('discount_type')->default('percentage'); // 'percentage' or 'fixed'
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->integer('max_redemptions')->nullable();
            $table->integer('redeemed_count')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Update Tenants table
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('subscription_status')->default('trial'); // trial, active, past_due, canceled
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('subscription_ends_at')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->string('billing_provider')->nullable(); // stripe, paypal
            $table->string('billing_customer_id')->nullable();
            $table->string('billing_subscription_id')->nullable();
            $table->integer('max_employees_override')->nullable();
            $table->boolean('google_calendar_sync_enabled')->default(false);
            $table->string('google_calendar_id')->nullable();
            $table->boolean('google_calendar_auto_sync')->default(true);
            $table->dateTime('google_calendar_last_synced_at')->nullable();
            $table->boolean('google_sheet_sync_enabled')->default(false);
            $table->string('google_sheet_url_or_id')->nullable();
            $table->boolean('google_sheet_auto_sync')->default(true);
            $table->dateTime('google_sheet_last_synced_at')->nullable();
            $table->string('google_sync_token', 64)->nullable()->unique();
        });

        // 4. Tenant Payments / Subscriptions table
        Schema::create('tenant_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('payment_gateway'); // stripe, paypal
            $table->string('gateway_transaction_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_cycle', 10)->default('monthly');
            $table->string('status')->default('completed'); // completed, pending, failed, refunded
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 5. Seed default packages & coupons
        $now = now();
        $packages = [
            [
                'id' => 1,
                'name' => 'Starter Tier',
                'slug' => 'starter',
                'description' => 'Essential field dispatch & attendance for small cleaning outfits.',
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
                'max_employees' => 5,
                'trial_days' => 14,
                'features' => json_encode([
                    'Up to 5 field employees',
                    'Mobile & Kiosk QR Badge Quick-Login',
                    'Tasks & Inspection Checklists',
                    'Google Calendar Feed Sync',
                    'GPS Clock-in & Basic Geofencing',
                ]),
                'has_google_calendar' => true,
                'has_google_sheets' => true,
                'has_qr_login' => true,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Growth Fleet',
                'slug' => 'growth',
                'description' => 'Full automated operations, Google Sheets sync and advanced dispatch for expanding teams.',
                'price_monthly' => 79.00,
                'price_yearly' => 790.00,
                'max_employees' => 25,
                'trial_days' => 14,
                'features' => json_encode([
                    'Up to 25 field employees',
                    'Unlimited Properties & Clients',
                    'Automated Google Sheets Live Sync',
                    'Automated Google Calendar Sync',
                    'Depot QR Scanner Kiosks',
                    'Supervisor Approvals & Incidents',
                    'Priority Shift Dispatcher',
                ]),
                'has_google_calendar' => true,
                'has_google_sheets' => true,
                'has_qr_login' => true,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Industrial Enterprise',
                'slug' => 'enterprise',
                'description' => 'High-capacity workforce compliance, dedicated quotas and direct integrations.',
                'price_monthly' => 199.00,
                'price_yearly' => 1990.00,
                'max_employees' => 100,
                'trial_days' => 30,
                'features' => json_encode([
                    'Up to 100 field employees',
                    'Dedicated Organization Subdomains',
                    'High-frequency Google Sync Engine',
                    'Complete Audit Trail & Compliance',
                    'Multi-Manager & Branch Delegation',
                    'Customizable Geofence Enforcements',
                    '24/7 Priority Field Support',
                ]),
                'has_google_calendar' => true,
                'has_google_sheets' => true,
                'has_qr_login' => true,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('packages')->insert($packages);

        $coupons = [
            [
                'code' => 'SCRUB20',
                'name' => 'ScrubRoster Launch 20% Special',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_redemptions' => 100,
                'redeemed_count' => 0,
                'expires_at' => $now->copy()->addMonths(6),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CLEANWAY20',
                'name' => 'Legacy 20% Special',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_redemptions' => 100,
                'redeemed_count' => 0,
                'expires_at' => $now->copy()->addMonths(6),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'OPS50',
                'name' => '$50 Field Credit',
                'discount_type' => 'fixed',
                'discount_value' => 50.00,
                'max_redemptions' => 50,
                'redeemed_count' => 0,
                'expires_at' => $now->copy()->addMonths(3),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('coupons')->insert($coupons);

        // Assign existing tenants to default Starter package with 14-day trial & secure google sync token
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            DB::table('tenants')->where('id', $tenant->id)->update([
                'package_id' => 1,
                'subscription_status' => 'trial',
                'trial_ends_at' => $now->copy()->addDays(14),
                'google_sync_token' => Str::random(40),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
        
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['package_id']);
            $table->dropColumn([
                'package_id',
                'subscription_status',
                'trial_ends_at',
                'subscription_ends_at',
                'billing_cycle',
                'billing_provider',
                'billing_customer_id',
                'billing_subscription_id',
                'max_employees_override',
                'google_calendar_sync_enabled',
                'google_calendar_id',
                'google_calendar_auto_sync',
                'google_calendar_last_synced_at',
                'google_sheet_sync_enabled',
                'google_sheet_url_or_id',
                'google_sheet_auto_sync',
                'google_sheet_last_synced_at',
                'google_sync_token',
            ]);
        });

        Schema::dropIfExists('coupons');
        Schema::dropIfExists('packages');
    }
};
