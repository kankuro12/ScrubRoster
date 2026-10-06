<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\TenantQueryMail;
use App\Models\TenantAlert;
use App\Models\TenantQuery;
use App\Models\TenantQueryMessage;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TenantQueryController extends Controller
{
    /**
     * List queries for the authenticated tenant admin.
     */
    public function index(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $statusFilter = $request->query('status');
        $categoryFilter = $request->query('category');

        $queriesQuery = TenantQuery::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['latestMessage', 'user'])
            ->withCount('messages');

        if ($statusFilter) {
            $queriesQuery->where('status', $statusFilter);
        }

        if ($categoryFilter) {
            $queriesQuery->where('category', $categoryFilter);
        }

        $queries = $queriesQuery->latest('updated_at')->paginate(12)->withQueryString();

        $stats = [
            'total' => TenantQuery::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            'waiting_tenant' => TenantQuery::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('status', TenantQuery::STATUS_WAITING_TENANT)->count(),
            'open' => TenantQuery::withoutGlobalScopes()->where('tenant_id', $tenant->id)->whereIn('status', [TenantQuery::STATUS_OPEN, TenantQuery::STATUS_TENANT_RESPONDED])->count(),
            'resolved' => TenantQuery::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('status', TenantQuery::STATUS_RESOLVED)->count(),
        ];

        return view('pages.queries', [
            'tenant' => $tenant,
            'queries' => $queries,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'categoryFilter' => $categoryFilter,
        ]);
    }

    /**
     * Show single query conversation thread.
     */
    public function show(TenantQuery $query, Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        // Ensure user belongs to the query's tenant or is super admin
        if ($query->tenant_id !== $tenant?->id && $request->user()?->role !== User::ROLE_ADMIN && $request->user()?->tenant_id !== null) {
            abort(403, 'Unauthorized access to this inquiry.');
        }

        $query->load(['tenant', 'user', 'messages.user']);

        return view('pages.query-detail', [
            'tenant' => $tenant ?? $query->tenant,
            'query' => $query,
        ]);
    }

    /**
     * Tenant admin submits a new query to platform team.
     */
    public function store(Request $request, TenantContext $context): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            return redirect()->back()->withErrors(['query' => 'Tenant context missing.']);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'subject' => ['nullable', 'string', 'max:200'],
            'category' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'message' => ['required', 'string', 'min:5'],
        ]);

        $subject = $validated['title'] ?? $validated['subject'] ?? 'Tenant Inquiry';
        $category = $validated['category'] === 'quota' ? TenantQuery::CATEGORY_QUOTA : $validated['category'];
        $priority = $validated['priority'] === 'medium' ? TenantQuery::PRIORITY_NORMAL : $validated['priority'];

        $query = DB::transaction(function () use ($tenant, $request, $validated, $subject, $category, $priority) {
            $q = TenantQuery::create([
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()?->id,
                'title' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => TenantQuery::STATUS_OPEN,
                'created_by_role' => 'tenant_admin',
                'last_responded_at' => now(),
            ]);

            TenantQueryMessage::create([
                'tenant_query_id' => $q->id,
                'user_id' => $request->user()?->id,
                'sender_type' => TenantQueryMessage::SENDER_TENANT_ADMIN,
                'message' => $validated['message'],
            ]);

            return $q;
        });

        // Notify super admin
        $superAdmin = User::whereNull('tenant_id')->where('role', User::ROLE_ADMIN)->first();
        if ($superAdmin) {
            try {
                Mail::to($superAdmin->email)->send(new TenantQueryMail($query, $query->messages->first(), 'platform_admin'));
            } catch (\Throwable $e) {
                Log::error("Failed alerting super admin of query: " . $e->getMessage());
            }
        }

        return redirect()->route('tenant.queries.show', $query)
            ->with('status', 'Inquiry submitted successfully! Our support and platform team will review and reply shortly.');
    }

    /**
     * Submit response / message in query thread.
     */
    public function reply(TenantQuery $query, Request $request, TenantContext $context): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2'],
        ]);

        $user = $request->user();
        $isPlatformAdmin = ($user?->role === User::ROLE_ADMIN && $user?->tenant_id === null);

        $senderType = $isPlatformAdmin
            ? TenantQueryMessage::SENDER_PLATFORM_ADMIN
            : TenantQueryMessage::SENDER_TENANT_ADMIN;

        $newStatus = $isPlatformAdmin
            ? TenantQuery::STATUS_WAITING_TENANT
            : TenantQuery::STATUS_TENANT_RESPONDED;

        $msg = DB::transaction(function () use ($query, $user, $senderType, $newStatus, $validated) {
            $m = TenantQueryMessage::create([
                'tenant_query_id' => $query->id,
                'user_id' => $user?->id,
                'sender_type' => $senderType,
                'message' => $validated['message'],
            ]);

            $query->update([
                'status' => $newStatus,
                'last_responded_at' => now(),
            ]);

            return $m;
        });

        // Send email alert to the opposite party
        if ($isPlatformAdmin) {
            // Notify tenant admin
            $tenantAdmin = $query->tenant->admins()->where('status', 'active')->first()
                ?? $query->user;
            if ($tenantAdmin) {
                try {
                    Mail::to($tenantAdmin->email)->send(new TenantQueryMail($query, $msg, 'tenant_admin'));

                    // Create in-app alert for tenant
                    TenantAlert::create([
                        'tenant_id' => $query->tenant_id,
                        'type' => TenantAlert::TYPE_QUERY_RESPONSE,
                        'severity' => TenantAlert::SEVERITY_INFO,
                        'title' => "New Inquiry Message: #QUERY-{$query->id}",
                        'message' => "Platform administration sent a response to '{$query->title}': " . substr($validated['message'], 0, 120) . "...",
                        'action_url' => route('tenant.queries.show', $query),
                        'action_label' => 'View & Respond',
                        'email_sent_at' => now(),
                        'email_recipient' => $tenantAdmin->email,
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Failed alerting tenant admin of reply: " . $e->getMessage());
                }
            }
        } else {
            // Notify platform super admin
            $superAdmin = User::whereNull('tenant_id')->where('role', User::ROLE_ADMIN)->first();
            if ($superAdmin) {
                try {
                    Mail::to($superAdmin->email)->send(new TenantQueryMail($query, $msg, 'platform_admin'));
                } catch (\Throwable $e) {
                    Log::error("Failed alerting super admin of reply: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('status', 'Response sent successfully.');
    }

    /**
     * Mark query as resolved.
     */
    public function resolve(TenantQuery $query): RedirectResponse
    {
        $query->update([
            'status' => TenantQuery::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Inquiry marked as resolved.');
    }
}
