<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create tenants table
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('domain')->nullable()->unique();
                $table->string('status')->default('active');
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Add tenant_id to users and adjust uniqueness to (tenant_id, email) & (tenant_id, employee_no)
        if (! Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        // Drop global unique indexes on users to allow cross-tenant shared emails
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_email_unique');
            DB::statement('DROP INDEX IF EXISTS users_employee_no_unique');
        } else {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_email_unique');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_employee_no_unique');
                });
            } catch (\Throwable $e) {}
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'employee_no']);
        });

        // 3. Add tenant_id to all tenant-scoped tables
        $tables = [
            'branches',
            'teams',
            'properties',
            'property_categories',
            'property_tags',
            'clients',
            'linen_types',
            'bed_types',
            'tasks',
            'task_types',
            'checklist_templates',
            'shifts',
            'incidents',
            'attendance_events',
            'attendance_correction_requests',
            'gps_exceptions',
            'audit_logs',
            'settings',
            'task_recurrences',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->index()->constrained('tenants')->nullOnDelete();
                });
            }
        }

        // 4. Create qr_login_tokens table for quick login
        if (! Schema::hasTable('qr_login_tokens')) {
            Schema::create('qr_login_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('token', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'token']);
                $table->index(['user_id', 'expires_at']);
            });
        }

        // 5. Backfill existing data with default tenant if existing data exists
        $hasData = DB::table('users')->exists();
        if ($hasData) {
            $defaultTenantId = DB::table('tenants')->insertGetId([
                'name' => 'CleanWay Primary',
                'slug' => 'cleanway',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);

            foreach ($tables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                    DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_login_tokens');

        $tables = [
            'branches',
            'teams',
            'properties',
            'property_categories',
            'property_tags',
            'clients',
            'linen_types',
            'bed_types',
            'tasks',
            'task_types',
            'checklist_templates',
            'shifts',
            'incidents',
            'attendance_events',
            'attendance_correction_requests',
            'gps_exceptions',
            'audit_logs',
            'settings',
            'task_recurrences',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'email']);
                $table->dropUnique(['tenant_id', 'employee_no']);
                $table->dropConstrainedForeignId('tenant_id');
                $table->unique('email');
                $table->unique('employee_no');
            });
        }

        Schema::dropIfExists('tenants');
    }
};
