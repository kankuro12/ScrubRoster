<?php

namespace App\Services\Billing;

use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class TenantBillingAccess
{
    public function __construct(protected TenantContext $context) {}

    public function tenant(Request $request): Tenant
    {
        $user = $request->user();
        abort_unless($user && $user->role === User::ROLE_ADMIN, 403);
        $tenant = $this->context->getTenant() ?? $user->tenant;
        abort_unless($tenant && $tenant->status === 'active', 403, 'Select an active tenant workspace first.');
        abort_if($user->tenant_id !== null && $user->tenant_id !== $tenant->id, 403);

        return $tenant;
    }

    public function payment(Request $request, TenantPayment $payment): void
    {
        abort_unless($payment->tenant_id === $this->tenant($request)->id, 403, 'Unauthorized invoice access.');
    }
}
