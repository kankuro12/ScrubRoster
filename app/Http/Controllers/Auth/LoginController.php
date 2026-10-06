<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (in_array($user->status, [User::STATUS_INACTIVE, User::STATUS_SUSPENDED, User::STATUS_ARCHIVED], true)) {
            return back()->withErrors([
                'email' => 'This account is currently deactivated.',
            ]);
        }

        // Only tenant admins can log in at normal login; other roles must use /{prefix}/tenant
        if ($user->tenant_id && (int) $user->role !== User::ROLE_ADMIN) {
            $prefix = $user->tenant?->slug ?? 'cleanway';
            return back()->withErrors([
                'email' => "Staff and supervisors must sign in via your company portal at /{$prefix}/tenant.",
            ])->onlyInput('email');
        }

        // Authenticate into appropriate guard
        if ((int) $user->role === User::ROLE_ADMIN) {
            Auth::guard('tenant_admin')->login($user, $request->boolean('remember'));
        } elseif ((int) $user->role === User::ROLE_SUPERVISOR) {
            Auth::guard('tenant_manager')->login($user, $request->boolean('remember'));
        } else {
            Auth::guard('employee')->login($user, $request->boolean('remember'));
        }

        // Also authenticate default web guard
        Auth::guard('web')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        if ($user->tenant) {
            $request->session()->put('tenant_id', $user->tenant_id);
            app(\App\Services\Tenancy\TenantContext::class)->setTenant($user->tenant);
            \Illuminate\Support\Facades\URL::defaults(['tenant' => $user->tenant->slug]);
        }

        app(AuditLogger::class)->log('login', get_class($user), $user->id, [
            'actor_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);

        if ($user->tenant) {
            return redirect()->intended(route('dashboard', ['tenant' => $user->tenant->slug]));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        app(AuditLogger::class)->log('logout', $user ? get_class($user) : User::class, $user?->id, [
            'actor_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
        ]);

        Auth::guard('tenant_admin')->logout();
        Auth::guard('tenant_manager')->logout();
        Auth::guard('employee')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
