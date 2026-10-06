<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Property;
use App\Models\QrLoginToken;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_self_signup_creates_workspace_and_admin(): void
    {
        $response = $this->get(route('signup'));
        $response->assertOk();

        $response = $this->post(route('signup.submit'), [
            'company_name' => 'Metro Cleaning Services',
            'slug' => 'metro-clean',
            'name' => 'Michael Scott',
            'email' => 'michael@metroclean.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $tenant = Tenant::where('slug', 'metro-clean')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Metro Cleaning Services', $tenant->name);

        $admin = User::where('tenant_id', $tenant->id)->where('email', 'michael@metroclean.local')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(User::ROLE_ADMIN, $admin->role);

        // Check redirect to dashboard
        $response->assertRedirect(route('dashboard', ['tenant' => 'metro-clean']));

        // Verify guard authentication
        $this->assertTrue(Auth::guard('tenant_admin')->check());
        $this->assertEquals($admin->id, Auth::guard('tenant_admin')->id());
    }

    public function test_tenant_admin_can_log_in_via_normal_login(): void
    {
        $tenant = Tenant::create([
            'name' => 'Apex Facilities',
            'slug' => 'apex',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Apex Admin',
            'email' => 'admin@apex.local',
            'password' => Hash::make('secretpassword'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@apex.local',
            'password' => 'secretpassword',
        ]);

        $response->assertRedirect(route('dashboard', ['tenant' => 'apex']));
        $this->assertTrue(Auth::guard('tenant_admin')->check());
        $this->assertEquals($admin->id, Auth::guard('tenant_admin')->id());
    }

    public function test_employee_attempting_normal_login_is_instructed_to_use_tenant_portal(): void
    {
        $tenant = Tenant::create([
            'name' => 'Star Cleaners',
            'slug' => 'star',
            'status' => 'active',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Star Cleaner 1',
            'email' => 'cleaner@star.local',
            'password' => Hash::make('cleanerpass'),
            'role' => User::ROLE_CLEANER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'cleaner@star.local',
            'password' => 'cleanerpass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('/star/tenant', session('errors')->first('email'));
        $this->assertFalse(Auth::check());
    }

    public function test_tenant_login_authenticates_supervisor_and_cleaner(): void
    {
        $tenant = Tenant::create([
            'name' => 'Delta Crew',
            'slug' => 'delta',
            'status' => 'active',
        ]);

        $supervisor = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Supervisor Dave',
            'email' => 'dave@delta.local',
            'password' => Hash::make('davepass'),
            'role' => User::ROLE_SUPERVISOR,
            'status' => User::STATUS_ACTIVE,
        ]);

        $cleaner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cleaner Carla',
            'email' => 'carla@delta.local',
            'password' => Hash::make('carlapass'),
            'role' => User::ROLE_CLEANER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Login as Supervisor at /{prefix}/tenant
        $response = $this->post(route('tenant.login.submit', ['tenant' => 'delta']), [
            'email' => 'dave@delta.local',
            'password' => 'davepass',
        ]);
        $response->assertRedirect(route('dashboard', ['tenant' => 'delta']));
        $this->assertTrue(Auth::guard('tenant_manager')->check());
        $this->assertEquals($supervisor->id, Auth::guard('tenant_manager')->id());

        // Logout
        $this->post(route('logout'));
        $this->assertFalse(Auth::check());

        // Login as Cleaner at /{prefix}/tenant
        $response = $this->post(route('tenant.login.submit', ['tenant' => 'delta']), [
            'email' => 'carla@delta.local',
            'password' => 'carlapass',
        ]);
        $response->assertRedirect(route('tasks.my', ['tenant' => 'delta']));
        $this->assertTrue(Auth::guard('employee')->check());
        $this->assertEquals($cleaner->id, Auth::guard('employee')->id());
    }

    public function test_multiple_tenants_can_have_employee_with_same_email_without_conflict(): void
    {
        $tenantA = Tenant::create(['name' => 'Company A', 'slug' => 'comp-a', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Company B', 'slug' => 'comp-b', 'status' => 'active']);

        $sameEmail = 'shared.cleaner@fieldops.local';

        // Create cleaner in Company A
        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'John Doe (At Company A)',
            'email' => $sameEmail,
            'password' => Hash::make('passwordA'),
            'role' => User::ROLE_CLEANER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Create cleaner in Company B with the EXACT SAME EMAIL
        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'John Doe (At Company B)',
            'email' => $sameEmail,
            'password' => Hash::make('passwordB'),
            'role' => User::ROLE_CLEANER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertNotEquals($userA->id, $userB->id);
        $this->assertEquals($userA->email, $userB->email);

        // Login at Company A's portal with Company A's password
        $responseA = $this->post(route('tenant.login.submit', ['tenant' => 'comp-a']), [
            'email' => $sameEmail,
            'password' => 'passwordA',
        ]);
        $responseA->assertRedirect(route('tasks.my', ['tenant' => 'comp-a']));
        $this->assertEquals($userA->id, Auth::id());
        $this->assertEquals($tenantA->id, Auth::user()->tenant_id);

        $this->post(route('logout'));

        // Login at Company B's portal with Company B's password
        $responseB = $this->post(route('tenant.login.submit', ['tenant' => 'comp-b']), [
            'email' => $sameEmail,
            'password' => 'passwordB',
        ]);
        $responseB->assertRedirect(route('tasks.my', ['tenant' => 'comp-b']));
        $this->assertEquals($userB->id, Auth::id());
        $this->assertEquals($tenantB->id, Auth::user()->tenant_id);
    }

    public function test_tenant_admin_opens_employee_qr_and_fast_scan_login_no_nonsense(): void
    {
        $tenant = Tenant::create(['name' => 'Rapid Facilities', 'slug' => 'rapid', 'status' => 'active']);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Boss',
            'email' => 'boss@rapid.local',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $cleaner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Fast Cleaner',
            'email' => 'cleaner@rapid.local',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CLEANER,
            'employee_no' => 'RF-99',
            'status' => User::STATUS_ACTIVE,
        ]);

        // Admin generates QR token for the employee
        $response = $this->actingAs($admin)->postJson(route('personnel.qr-token', ['user' => $cleaner->id]));
        $response->assertOk();

        $tokenData = $response->json();
        $this->assertNotEmpty($tokenData['token']);
        $this->assertStringContainsString($tenant->slug . '/qr-login/' . $tokenData['token'], $tokenData['qr_url']);

        // Log out admin so we simulate the employee's phone
        $this->post(route('logout'));
        $this->assertFalse(Auth::check());

        // Employee scans QR code (visits the QR URL) — ZERO credentials requested ("no nonsense")
        $qrScanResponse = $this->get($tokenData['qr_url']);

        // Redirected straight to active cleaner dashboard
        $qrScanResponse->assertRedirect(route('cleaner-tools', ['tenant' => 'rapid']));

        // Employee is authenticated in employee guard
        $this->assertTrue(Auth::guard('employee')->check());
        $this->assertEquals($cleaner->id, Auth::guard('employee')->id());
        $this->assertEquals($cleaner->id, Auth::id());

        // Token is now used
        $tokenRecord = QrLoginToken::where('token', $tokenData['token'])->first();
        $this->assertNotNull($tokenRecord->used_at);

        // Attempting to reuse the exact same token fails safely
        $this->post(route('logout'));
        $reuseResponse = $this->get($tokenData['qr_url']);
        $reuseResponse->assertRedirect(route('tenant.login', ['tenant' => 'rapid']));
        $this->assertFalse(Auth::check());
    }

    public function test_tenant_employee_self_signup(): void
    {
        $tenant = Tenant::create(['name' => 'Eco Cleaners', 'slug' => 'eco', 'status' => 'active']);

        $response = $this->get(route('tenant.signup', ['tenant' => 'eco']));
        $response->assertOk();

        $postResponse = $this->post(route('tenant.signup.submit', ['tenant' => 'eco']), [
            'name' => 'Eco Worker',
            'email' => 'worker@eco.local',
            'phone' => '0211234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $postResponse->assertRedirect(route('cleaner-tools', ['tenant' => 'eco']));

        $user = User::where('tenant_id', $tenant->id)->where('email', 'worker@eco.local')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_CLEANER, $user->role);
        $this->assertTrue(Auth::guard('employee')->check());
    }

    public function test_tenant_data_is_isolated_between_tenants(): void
    {
        $tenant1 = Tenant::create(['name' => 'Tenant 1', 'slug' => 't1', 'status' => 'active']);
        $tenant2 = Tenant::create(['name' => 'Tenant 2', 'slug' => 't2', 'status' => 'active']);

        $branch1 = Branch::create(['tenant_id' => $tenant1->id, 'name' => 'T1 Branch']);
        $branch2 = Branch::create(['tenant_id' => $tenant2->id, 'name' => 'T2 Branch']);

        $prop1 = Property::create([
            'tenant_id' => $tenant1->id,
            'name' => 'T1 Property',
            'address' => '101 First Ave',
        ]);

        $prop2 = Property::create([
            'tenant_id' => $tenant2->id,
            'name' => 'T2 Property',
            'address' => '202 Second Ave',
        ]);

        $user1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'T1 Admin',
            'email' => 'admin@t1.local',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        // When TenantContext is active for Tenant 1:
        app(TenantContext::class)->setTenant($tenant1);

        $properties = Property::all();
        $this->assertTrue($properties->contains($prop1));
        $this->assertFalse($properties->contains($prop2));

        $branches = Branch::all();
        $this->assertTrue($branches->contains($branch1));
        $this->assertFalse($branches->contains($branch2));

        // When TenantContext is switched to Tenant 2:
        app(TenantContext::class)->setTenant($tenant2);

        $properties2 = Property::all();
        $this->assertFalse($properties2->contains($prop1));
        $this->assertTrue($properties2->contains($prop2));

        $branches2 = Branch::all();
        $this->assertFalse($branches2->contains($branch1));
        $this->assertTrue($branches2->contains($branch2));

        // Reset context
        app(TenantContext::class)->setTenant(null);
    }
}
