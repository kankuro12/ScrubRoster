<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantRegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'min:2', 'max:50', 'alpha_dash', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $slug = Str::slug($validated['slug']);

        $tenant = DB::transaction(function () use ($validated, $slug, &$admin) {
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'slug' => $slug,
                'status' => 'active',
            ]);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Depot',
                'active' => true,
            ]);

            $admin = User::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'start_date' => now()->toDateString(),
            ]);

            return $tenant;
        });

        Auth::guard('tenant_admin')->login($admin);
        Auth::guard('web')->login($admin);

        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        app(TenantContext::class)->setTenant($tenant);

        app(AuditLogger::class)->log('tenant_self_signup', Tenant::class, $tenant->id, [
            'actor_id' => $admin->id,
            'tenant_id' => $tenant->id,
        ]);

        return redirect()->route('dashboard', ['tenant' => $tenant->slug])
            ->with('status', "Welcome to ScrubRoster! Workspace '{$tenant->name}' has been created successfully.");
    }
}
