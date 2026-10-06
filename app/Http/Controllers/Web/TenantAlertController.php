<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TenantAlert;
use App\Services\Tenancy\TenantContext;
use App\Services\Tenants\TenantAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAlertController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $alerts = TenantAlert::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(15);

        $unreadCount = TenantAlert::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_read', false)
            ->count();

        return view('pages.alerts', [
            'tenant' => $tenant,
            'alerts' => $alerts,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(TenantAlert $alert, Request $request, TenantContext $context): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if ($alert->tenant_id === $tenant?->id) {
            $alert->markAsRead();
        }

        return redirect()->back()->with('status', 'Alert marked as read.');
    }

    public function dismissAll(Request $request, TenantContext $context): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if ($tenant) {
            TenantAlert::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        return redirect()->back()->with('status', 'All notifications marked as read.');
    }

    public function scan(Request $request, TenantContext $context, TenantAlertService $alertService): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        $result = $alertService->scanAndDispatchAutomatedAlerts($tenant);

        $count = $result['dispatched_count'];
        $msg = $count > 0
            ? "Scan complete! {$count} alert(s) dispatched to your organization."
            : 'Scan complete! All systems operational, no urgent alerts pending.';

        return redirect()->back()->with('status', $msg);
    }
}
