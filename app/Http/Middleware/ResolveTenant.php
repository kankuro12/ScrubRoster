<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantContext $context
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;
        $tenantParam = $request->route('tenant');
        $reserved = ['admin', 'login', 'logout', 'signup', 'register', 'forgot-password', 'reset-password', 'api', 'storage', 'up', 'supervisor-only-approvals', 'integrations'];
        if (is_string($tenantParam) && in_array(strtolower($tenantParam), $reserved, true)) {
            return $next($request);
        }

        if ($tenantParam instanceof Tenant) {
            $tenant = $tenantParam;
        } elseif (is_string($tenantParam) && ! empty($tenantParam)) {
            $tenant = Tenant::where('slug', strtolower($tenantParam))->first();
            if (! $tenant) {
                abort(404, 'Tenant workspace not found.');
            }
        } elseif ($request->user() && $request->user()->tenant_id) {
            $tenant = $request->user()->tenant;
        } elseif ($request->header('X-Tenant')) {
            $tenant = Tenant::where('slug', strtolower($request->header('X-Tenant')))->first();
        } elseif ($request->hasSession() && $request->session()->has('tenant_id')) {
            $tenant = Tenant::find($request->session()->get('tenant_id'));
        }

        if ($tenant) {
            if ($tenant->status !== 'active') {
                abort(403, 'This workspace is currently inactive.');
            }

            $this->context->setTenant($tenant);
            URL::defaults(['tenant' => $tenant->slug]);
            View::share('currentTenant', $tenant);

            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
