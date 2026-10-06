<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantManagerController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        $search = $request->query('q');

        $tenantsQuery = Tenant::query()
            ->with(['package'])
            ->withCount([
                'users',
                'employees' => function ($q) {
                    $q->where('status', 'active');
                },
            ]);

        if ($search) {
            $tenantsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $tenants = $tenantsQuery->latest()->paginate(15)->withQueryString();
        $packages = Package::orderBy('sort_order')->get();
        $coupons = Coupon::latest()->get();

        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants' => Tenant::where('subscription_status', 'trial')->count(),
            'total_employees' => User::where('role', User::ROLE_CLEANER)->count(),
        ];

        $platformSettings = [
            'default_trial_days' => $settings->get('platform_default_trial_days', 14, 'system'),
            'stripe_enabled' => $settings->get('platform_stripe_enabled', '1', 'system') === '1',
            'stripe_mode' => $settings->get('platform_stripe_mode', 'test', 'system'),
            'stripe_publishable_key' => $settings->get('platform_stripe_publishable_key', 'pk_test_sample', 'system'),
            'paypal_enabled' => $settings->get('platform_paypal_enabled', '1', 'system') === '1',
            'paypal_mode' => $settings->get('platform_paypal_mode', 'sandbox', 'system'),
            'paypal_client_id' => $settings->get('platform_paypal_client_id', 'sb_client_id_sample', 'system'),
            'google_calendar_auto_sync' => $settings->get('platform_google_calendar_auto_sync', '1', 'system') === '1',
            'google_sheet_auto_sync' => $settings->get('platform_google_sheet_auto_sync', '1', 'system') === '1',
        ];

        // Queries across tenants
        $queries = \App\Models\TenantQuery::with(['tenant', 'user', 'latestMessage'])
            ->latest('updated_at')
            ->paginate(15, ['*'], 'queries_page')
            ->withQueryString();

        // Proactive Alerts & Invoices
        $alerts = \App\Models\TenantAlert::with('tenant')
            ->latest()
            ->paginate(15, ['*'], 'alerts_page')
            ->withQueryString();

        $payments = \App\Models\TenantPayment::withoutGlobalScopes()
            ->with(['tenant', 'package', 'coupon'])
            ->latest()
            ->paginate(15, ['*'], 'payments_page')
            ->withQueryString();

        $queryStats = [
            'total' => \App\Models\TenantQuery::count(),
            'tenant_responded' => \App\Models\TenantQuery::where('status', \App\Models\TenantQuery::STATUS_TENANT_RESPONDED)->count(),
            'waiting_tenant' => \App\Models\TenantQuery::where('status', \App\Models\TenantQuery::STATUS_WAITING_TENANT)->count(),
            'resolved' => \App\Models\TenantQuery::where('status', \App\Models\TenantQuery::STATUS_RESOLVED)->count(),
        ];

        return view('pages.tenant-manager', [
            'tenants' => $tenants,
            'packages' => $packages,
            'coupons' => $coupons,
            'queries' => $queries,
            'alerts' => $alerts,
            'payments' => $payments,
            'queryStats' => $queryStats,
            'stats' => $stats,
            'platformSettings' => $platformSettings,
            'currentTab' => $request->query('tab', 'tenants'),
        ]);
    }

    public function storeTenant(Request $request, SettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:tenants,slug'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['required', 'string', 'min:8'],
            'package_id' => ['required', 'exists:packages,id'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $defaultTrialDays = (int) $settings->get('platform_default_trial_days', 14, 'system');
        $trialDays = $validated['trial_days'] ?? $defaultTrialDays;

        DB::transaction(function () use ($validated, $trialDays) {
            $tenant = Tenant::createWithSlug($validated['name'], $validated['slug'] ?? null);
            $tenant->update([
                'package_id' => $validated['package_id'],
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'google_sync_token' => Str::random(40),
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ]);
        });

        return redirect()->route('tenant-manager.index', ['tab' => 'tenants'])->with('status', 'Tenant organization and administrator created successfully.');
    }

    public function updateTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'package_id' => ['required', 'exists:packages,id'],
            'subscription_status' => ['required', 'in:trial,active,past_due,canceled'],
            'trial_days_add' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_employees_override' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'package_id' => $validated['package_id'],
            'subscription_status' => $validated['subscription_status'],
            'max_employees_override' => $validated['max_employees_override'] ?: null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['trial_days_add'])) {
            $baseDate = ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) ? $tenant->trial_ends_at : now();
            $updateData['trial_ends_at'] = $baseDate->copy()->addDays((int) $validated['trial_days_add']);
        }

        $tenant->update($updateData);

        return redirect()->route('tenant-manager.index', ['tab' => 'tenants'])->with('status', "Tenant '{$tenant->name}' updated.");
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $newStatus = $tenant->status === 'active' ? 'inactive' : 'active';
        $tenant->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'activated' : 'suspended';
        return redirect()->route('tenant-manager.index', ['tab' => 'tenants'])->with('status', "Tenant '{$tenant->name}' has been {$label}.");
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_employees' => ['required', 'integer', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'features' => ['nullable', 'string'],
            'has_google_calendar' => ['nullable', 'boolean'],
            'has_google_sheets' => ['nullable', 'boolean'],
            'has_qr_login' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
        ]);

        $featuresList = ! empty($validated['features'])
            ? array_filter(array_map('trim', explode("\n", $validated['features'])))
            : [];

        Package::createWithSlug([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'max_employees' => $validated['max_employees'],
            'trial_days' => $validated['trial_days'],
            'features' => $featuresList,
            'has_google_calendar' => $request->boolean('has_google_calendar'),
            'has_google_sheets' => $request->boolean('has_google_sheets'),
            'has_qr_login' => $request->boolean('has_qr_login'),
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => true,
        ]);

        return redirect()->route('tenant-manager.index', ['tab' => 'packages'])->with('status', 'Subscription package created.');
    }

    public function updatePackage(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_employees' => ['required', 'integer', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'features' => ['nullable', 'string'],
            'has_google_calendar' => ['nullable', 'boolean'],
            'has_google_sheets' => ['nullable', 'boolean'],
            'has_qr_login' => ['nullable', 'boolean'],
            'is_popular' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $featuresList = ! empty($validated['features'])
            ? array_filter(array_map('trim', explode("\n", $validated['features'])))
            : $package->features;

        $package->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'max_employees' => $validated['max_employees'],
            'trial_days' => $validated['trial_days'],
            'features' => $featuresList,
            'has_google_calendar' => $request->boolean('has_google_calendar'),
            'has_google_sheets' => $request->boolean('has_google_sheets'),
            'has_qr_login' => $request->boolean('has_qr_login'),
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('tenant-manager.index', ['tab' => 'packages'])->with('status', "Package '{$package->name}' updated.");
    }

    public function destroyPackage(Package $package): RedirectResponse
    {
        if ($package->tenants()->exists()) {
            return redirect()->route('tenant-manager.index', ['tab' => 'packages'])
                ->withErrors(['package' => 'Cannot delete package while tenants are actively assigned to it.']);
        }

        $package->delete();

        return redirect()->route('tenant-manager.index', ['tab' => 'packages'])->with('status', 'Package deleted.');
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:coupons,code'],
            'name' => ['required', 'string', 'max:100'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        Coupon::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => $validated['name'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'max_redemptions' => ! empty($validated['max_redemptions']) ? $validated['max_redemptions'] : null,
            'expires_at' => ! empty($validated['expires_at']) ? $validated['expires_at'] : null,
            'is_active' => true,
        ]);

        return redirect()->route('tenant-manager.index', ['tab' => 'coupons'])->with('status', 'Coupon created.');
    }

    public function toggleCoupon(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return redirect()->route('tenant-manager.index', ['tab' => 'coupons'])->with('status', "Coupon '{$coupon->code}' status updated.");
    }

    public function destroyCoupon(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('tenant-manager.index', ['tab' => 'coupons'])->with('status', 'Coupon removed.');
    }

    public function updateSettings(Request $request, SettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'default_trial_days' => ['required', 'integer', 'min:0', 'max:180'],
            'stripe_enabled' => ['nullable', 'boolean'],
            'stripe_mode' => ['required', 'in:test,live'],
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'paypal_enabled' => ['nullable', 'boolean'],
            'paypal_mode' => ['required', 'in:sandbox,live'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'google_calendar_auto_sync' => ['nullable', 'boolean'],
            'google_sheet_auto_sync' => ['nullable', 'boolean'],
        ]);

        $settings->set('platform_default_trial_days', $validated['default_trial_days'], 'system');
        $settings->set('platform_stripe_enabled', $request->boolean('stripe_enabled') ? '1' : '0', 'system');
        $settings->set('platform_stripe_mode', $validated['stripe_mode'], 'system');
        $settings->set('platform_stripe_publishable_key', $validated['stripe_publishable_key'] ?? '', 'system');
        $settings->set('platform_paypal_enabled', $request->boolean('paypal_enabled') ? '1' : '0', 'system');
        $settings->set('platform_paypal_mode', $validated['paypal_mode'], 'system');
        $settings->set('platform_paypal_client_id', $validated['paypal_client_id'] ?? '', 'system');
        $settings->set('platform_google_calendar_auto_sync', $request->boolean('google_calendar_auto_sync') ? '1' : '0', 'system');
        $settings->set('platform_google_sheet_auto_sync', $request->boolean('google_sheet_auto_sync') ? '1' : '0', 'system');

        return redirect()->route('tenant-manager.index', ['tab' => 'settings'])->with('status', 'Platform settings and sync configurations saved.');
    }

    /**
     * Platform Admin initiates an inquiry / query to a specific tenant admin.
     */
    public function storeQuery(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'title' => ['nullable', 'string', 'max:200'],
            'subject' => ['nullable', 'string', 'max:200'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'message' => ['required', 'string', 'min:5'],
        ]);

        $title = $validated['title'] ?? $validated['subject'] ?? 'Platform Notice';
        $category = $validated['category'] === 'quota' ? \App\Models\TenantQuery::CATEGORY_QUOTA : $validated['category'];
        $priority = $validated['priority'] === 'medium' ? \App\Models\TenantQuery::PRIORITY_NORMAL : $validated['priority'];

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $query = DB::transaction(function () use ($tenant, $request, $validated, $title, $category, $priority) {
            $q = \App\Models\TenantQuery::create([
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()?->id,
                'title' => $title,
                'category' => $category,
                'priority' => $priority,
                'status' => \App\Models\TenantQuery::STATUS_WAITING_TENANT,
                'created_by_role' => 'platform_admin',
                'last_responded_at' => now(),
            ]);

            \App\Models\TenantQueryMessage::create([
                'tenant_query_id' => $q->id,
                'user_id' => $request->user()?->id,
                'sender_type' => \App\Models\TenantQueryMessage::SENDER_PLATFORM_ADMIN,
                'message' => $validated['message'],
            ]);

            return $q;
        });

        // Email and in-app alert to tenant admin
        $tenantAdmin = $tenant->admins()->where('status', 'active')->first();
        if ($tenantAdmin) {
            try {
                \Illuminate\Support\Facades\Mail::to($tenantAdmin->email)
                    ->send(new \App\Mail\TenantQueryMail($query, $query->messages->first(), 'tenant_admin'));

                \App\Models\TenantAlert::create([
                    'tenant_id' => $tenant->id,
                    'type' => \App\Models\TenantAlert::TYPE_QUERY_RESPONSE,
                    'severity' => \App\Models\TenantAlert::SEVERITY_WARNING,
                    'title' => "Inquiry From Platform Admin: {$query->title}",
                    'message' => "Platform team opened inquiry #QUERY-{$query->id}: '{$validated['title']}'. Please review and provide your response.",
                    'action_url' => route('tenant.queries.show', $query),
                    'action_label' => 'Submit Response',
                    'email_sent_at' => now(),
                    'email_recipient' => $tenantAdmin->email,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending query notification: " . $e->getMessage());
            }
        }

        return redirect()->route('tenant-manager.index', ['tab' => 'queries'])
            ->with('status', "Inquiry sent to {$tenant->name}. The tenant admin has been alerted and can now reply.");
    }

    /**
     * Platform Admin updates query status.
     */
    public function updateQueryStatus(\App\Models\TenantQuery $query, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,waiting_tenant_response,tenant_responded,resolved,closed'],
        ]);

        $query->update([
            'status' => $validated['status'],
            'resolved_at' => in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null,
        ]);

        return redirect()->back()->with('status', "Query #QUERY-{$query->id} status updated to " . strtoupper($validated['status']) . ".");
    }

    /**
     * Dispatch custom alert to tenant with email.
     */
    public function dispatchAlert(Request $request, \App\Services\Tenants\TenantAlertService $alertService): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'type' => ['required', 'string'],
            'severity' => ['required', 'in:info,warning,danger,success'],
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'action_label' => ['nullable', 'string', 'max:100'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $alertService->dispatchAlert(
            $tenant,
            $validated['type'],
            $validated['title'],
            $validated['message'],
            $validated['severity'],
            $validated['action_url'] ?? null,
            $validated['action_label'] ?? null,
            $request->boolean('send_email', true)
        );

        return redirect()->route('tenant-manager.index', ['tab' => 'alerts'])
            ->with('status', "Alert dispatched to {$tenant->name}" . ($request->boolean('send_email') ? " with email." : "."));
    }

    /**
     * Trigger proactive system-wide alert scan.
     */
    public function scanAlerts(\App\Services\Tenants\TenantAlertService $alertService): RedirectResponse
    {
        $result = $alertService->scanAndDispatchAutomatedAlerts();

        return redirect()->route('tenant-manager.index', ['tab' => 'alerts'])
            ->with('status', "Automated scan complete! Dispatched {$result['dispatched_count']} proactive alert(s).");
    }

    /**
     * Resend invoice email from super admin console.
     */
    public function resendInvoice(\App\Models\TenantPayment $payment, \App\Services\Billing\InvoiceService $invoiceService): RedirectResponse
    {
        $sent = $invoiceService->generateAndSendInvoice($payment);

        if ($sent) {
            return redirect()->back()->with('status', "Invoice #{$payment->getInvoiceNumber()} resent to {$payment->invoice_recipient_email}!");
        }

        return redirect()->back()->withErrors(['invoice' => 'Could not resend invoice email. Check tenant admin contact.']);
    }
}
