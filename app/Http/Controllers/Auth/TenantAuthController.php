<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\QrLoginToken;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantAuthController extends Controller
{
    public function showLoginForm(Tenant $tenant): View
    {
        return view('auth.tenant-login', [
            'tenant' => $tenant,
        ]);
    }

    public function login(Request $request, Tenant $tenant): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Find user specifically within this tenant workspace
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => "These credentials do not match our records for {$tenant->name}.",
            ])->onlyInput('email');
        }

        if (in_array($user->status, [User::STATUS_INACTIVE, User::STATUS_SUSPENDED, User::STATUS_ARCHIVED], true)) {
            return back()->withErrors([
                'email' => 'This account is currently deactivated. Please contact your manager.',
            ]);
        }

        // Log into role-specific guard
        if ((int) $user->role === User::ROLE_ADMIN) {
            Auth::guard('tenant_admin')->login($user, $request->boolean('remember'));
        } elseif ((int) $user->role === User::ROLE_SUPERVISOR) {
            Auth::guard('tenant_manager')->login($user, $request->boolean('remember'));
        } else {
            Auth::guard('employee')->login($user, $request->boolean('remember'));
        }

        // Also log into web guard for unified session management
        Auth::guard('web')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        app(TenantContext::class)->setTenant($tenant);

        app(AuditLogger::class)->log('tenant_login', get_class($user), $user->id, [
            'actor_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        if ((int) $user->role === User::ROLE_CLEANER) {
            return redirect()->route('tasks.my', ['tenant' => $tenant->slug]);
        }

        return redirect()->route('dashboard', ['tenant' => $tenant->slug]);
    }

    public function qrLogin(Request $request, Tenant $tenant, string $token): RedirectResponse
    {
        $qrToken = QrLoginToken::where('tenant_id', $tenant->id)
            ->where('token', $token)
            ->valid()
            ->first();

        if (! $qrToken) {
            return redirect()->route('tenant.login', ['tenant' => $tenant->slug])
                ->withErrors(['email' => 'Quick-login QR code is invalid, expired, or has already been used. Please request a fresh QR badge from your supervisor.']);
        }

        $user = $qrToken->user;

        if (! $user || in_array($user->status, [User::STATUS_INACTIVE, User::STATUS_SUSPENDED, User::STATUS_ARCHIVED], true)) {
            return redirect()->route('tenant.login', ['tenant' => $tenant->slug])
                ->withErrors(['email' => 'Account is inactive. Contact management.']);
        }

        // Mark token as used
        $qrToken->markUsed();

        // Log into guard based on role
        if ((int) $user->role === User::ROLE_ADMIN) {
            Auth::guard('tenant_admin')->login($user);
        } elseif ((int) $user->role === User::ROLE_SUPERVISOR) {
            Auth::guard('tenant_manager')->login($user);
        } else {
            Auth::guard('employee')->login($user);
        }
        Auth::guard('web')->login($user);

        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        app(TenantContext::class)->setTenant($tenant);

        app(AuditLogger::class)->log('qr_login', get_class($user), $user->id, [
            'actor_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        if ((int) $user->role === User::ROLE_CLEANER) {
            return redirect()->route('cleaner-tools', ['tenant' => $tenant->slug])
                ->with('status', "Welcome {$user->name}! Fast QR sign-in verified.");
        }

        return redirect()->route('dashboard', ['tenant' => $tenant->slug])
            ->with('status', "Welcome {$user->name}! Fast QR sign-in verified.");
    }

    public function showSignupForm(Tenant $tenant): View
    {
        return view('auth.tenant-signup', [
            'tenant' => $tenant,
            'branches' => Branch::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('name')->get(),
        ]);
    }

    public function signup(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $user = DB::transaction(function () use ($validated, $tenant) {
            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_CLEANER,
                'status' => User::STATUS_ACTIVE,
                'phone' => $validated['phone'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'start_date' => now()->toDateString(),
            ]);
        });

        Auth::guard('employee')->login($user);
        Auth::guard('web')->login($user);

        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        app(TenantContext::class)->setTenant($tenant);

        app(AuditLogger::class)->log('employee_self_signup', get_class($user), $user->id, [
            'actor_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);

        return redirect()->route('cleaner-tools', ['tenant' => $tenant->slug])
            ->with('status', "Welcome {$user->name}! You have joined {$tenant->name}.");
    }
}
