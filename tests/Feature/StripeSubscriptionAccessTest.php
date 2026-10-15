<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StripeSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $status, int $days): User
    {
        $tenant = Tenant::createWithSlug('Access', 'access');
        $tenant->update(['stripe_subscription_id' => 'sub_access', 'stripe_status' => $status,
            'subscription_status' => $status, 'subscription_ends_at' => now()->addDays($days)]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN]);
    }

    public function test_expired_stripe_tenant_cannot_use_operations_but_can_open_billing(): void
    {
        $admin = $this->admin('past_due', -1);
        $this->actingAs($admin)->get(route('properties'))->assertForbidden();
        $this->get(route('billing.index'))->assertOk();
    }

    public function test_api_enforces_paid_period_and_keeps_identity_accessible(): void
    {
        $admin = $this->admin('past_due', -1);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/properties')->assertForbidden()->assertJson(['message' => 'This workspace requires a paid subscription. A tenant administrator can manage billing.']);
        $this->getJson('/api/v1/me')->assertOk();
    }

    public function test_past_due_tenant_can_work_until_the_paid_period_ends(): void
    {
        $admin = $this->admin('past_due', 1);
        $this->actingAs($admin)->get(route('properties'))->assertOk();
    }

    public function test_tenant_admin_cannot_grant_themselves_a_new_trial_through_platform_management(): void
    {
        $admin = $this->admin('canceled', -1);
        $this->actingAs($admin)->put(route('tenant-manager.tenants.update', $admin->tenant), [
            'name' => $admin->tenant->name, 'status' => 'active',
            'package_id' => Package::first()->id, 'max_employees_override' => null,
            'subscription_status' => 'trial', 'trial_days_add' => 30,
        ])->assertForbidden();
        $this->assertSame('canceled', $admin->tenant->fresh()->subscription_status);
    }

    public function test_paid_tenant_admin_cannot_edit_platform_subscription_settings(): void
    {
        $admin = $this->admin('active', 30);
        $this->actingAs($admin)->get(route('tenant-manager.index'))->assertForbidden();
    }
}
