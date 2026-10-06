<?php

namespace App\Services\Tenants;

use App\Mail\TenantAlertMail;
use App\Models\Tenant;
use App\Models\TenantAlert;
use App\Models\TenantQuery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TenantAlertService
{
    /**
     * Dispatch an in-app alert and optional email to the tenant's admin.
     */
    public function dispatchAlert(
        Tenant $tenant,
        string $type,
        string $title,
        string $message,
        string $severity = TenantAlert::SEVERITY_INFO,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        bool $sendEmail = true,
        ?string $recipientEmail = null
    ): TenantAlert {
        $alert = TenantAlert::create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'is_read' => false,
        ]);

        if ($sendEmail) {
            $recipient = $recipientEmail;
            if (! $recipient) {
                $adminUser = $tenant->admins()->where('status', 'active')->first()
                    ?? $tenant->users()->where('status', 'active')->first();
                $recipient = $adminUser?->email;
            }

            if ($recipient) {
                try {
                    Mail::to($recipient)->send(new TenantAlertMail($alert));
                    $alert->update([
                        'email_sent_at' => now(),
                        'email_recipient' => $recipient,
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Failed sending tenant alert email to {$recipient}: " . $e->getMessage());
                }
            }
        }

        return $alert;
    }

    /**
     * Scan tenants and dispatch proactive alerts (trial, quota, queries) without spamming duplicates.
     */
    public function scanAndDispatchAutomatedAlerts(?Tenant $singleTenant = null): array
    {
        $tenantsQuery = $singleTenant
            ? Tenant::where('id', $singleTenant->id)
            : Tenant::where('status', 'active');

        $tenants = $tenantsQuery->with(['package'])->get();
        $dispatchedCount = 0;
        $summary = [];

        foreach ($tenants as $tenant) {
            // 1. Check Trial Status
            if ($tenant->subscription_status === 'trial') {
                $daysRemaining = $tenant->getRemainingTrialDays();

                if ($daysRemaining > 0 && $daysRemaining <= 3) {
                    // Check if alert already sent in the last 48 hours
                    $alreadySent = TenantAlert::where('tenant_id', $tenant->id)
                        ->where('type', TenantAlert::TYPE_TRIAL_EXPIRING)
                        ->where('created_at', '>=', now()->subHours(48))
                        ->exists();

                    if (! $alreadySent) {
                        $this->dispatchAlert(
                            $tenant,
                            TenantAlert::TYPE_TRIAL_EXPIRING,
                            "Free Trial Expiring in {$daysRemaining} Days",
                            "Your free evaluation trial for '{$tenant->name}' ends in {$daysRemaining} days. Upgrade to a commercial tier to keep Google Calendar feeds, Sheets sync, and mobile QR access active.",
                            TenantAlert::SEVERITY_WARNING,
                            route('billing.index'),
                            'Upgrade Subscription Plan',
                            true
                        );
                        $dispatchedCount++;
                        $summary[] = "{$tenant->name}: Trial expiring in {$daysRemaining}d alert sent";
                    }
                } elseif ($daysRemaining === 0 && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast()) {
                    $alreadySent = TenantAlert::where('tenant_id', $tenant->id)
                        ->where('type', TenantAlert::TYPE_TRIAL_EXPIRED)
                        ->where('created_at', '>=', now()->subHours(72))
                        ->exists();

                    if (! $alreadySent) {
                        $this->dispatchAlert(
                            $tenant,
                            TenantAlert::TYPE_TRIAL_EXPIRED,
                            "Free Trial Expired — Immediate Action Required",
                            "Your evaluation trial has ended. Field dispatch, mobile badge check-in, and automated cloud sync are currently pending renewal. Select a fleet package to resume full operations.",
                            TenantAlert::SEVERITY_DANGER,
                            route('billing.index'),
                            'Renew / Activate Plan',
                            true
                        );
                        $dispatchedCount++;
                        $summary[] = "{$tenant->name}: Trial expired alert sent";
                    }
                }
            }

            // 2. Check Employee Quota Limits
            $activeCount = $tenant->getActiveEmployeesCount();
            $maxEmployees = $tenant->getMaxEmployees();

            if ($maxEmployees > 0) {
                $pct = round(($activeCount / $maxEmployees) * 100);

                if ($activeCount >= $maxEmployees) {
                    $alreadySent = TenantAlert::where('tenant_id', $tenant->id)
                        ->where('type', TenantAlert::TYPE_QUOTA_EXCEEDED)
                        ->where('created_at', '>=', now()->subHours(48))
                        ->exists();

                    if (! $alreadySent) {
                        $this->dispatchAlert(
                            $tenant,
                            TenantAlert::TYPE_QUOTA_EXCEEDED,
                            "Personnel Quota Limit Reached ({$activeCount}/{$maxEmployees})",
                            "Your cleaning fleet has reached its maximum plan capacity of {$maxEmployees} cleaners. Additional staff invitations or badge logins cannot be added until you upgrade.",
                            TenantAlert::SEVERITY_DANGER,
                            route('billing.index'),
                            'Increase Employee Quota',
                            true
                        );
                        $dispatchedCount++;
                        $summary[] = "{$tenant->name}: Quota limit reached alert sent";
                    }
                } elseif ($pct >= 85) {
                    $alreadySent = TenantAlert::where('tenant_id', $tenant->id)
                        ->where('type', TenantAlert::TYPE_QUOTA_WARNING)
                        ->where('created_at', '>=', now()->subHours(48))
                        ->exists();

                    if (! $alreadySent) {
                        $this->dispatchAlert(
                            $tenant,
                            TenantAlert::TYPE_QUOTA_WARNING,
                            "Approaching Cleaner Capacity ({$activeCount}/{$maxEmployees} slots used)",
                            "Your organization is utilizing {$pct}% of its allotted employee capacity. You have " . ($maxEmployees - $activeCount) . " cleaner slot(s) remaining.",
                            TenantAlert::SEVERITY_WARNING,
                            route('billing.index'),
                            'View Upgrade Options',
                            true
                        );
                        $dispatchedCount++;
                        $summary[] = "{$tenant->name}: Quota warning ({$pct}%) alert sent";
                    }
                }
            }

            // 3. Check Pending Platform Queries Awaiting Tenant Response
            $pendingQueries = TenantQuery::where('tenant_id', $tenant->id)
                ->where('status', TenantQuery::STATUS_WAITING_TENANT)
                ->where('last_responded_at', '<=', now()->subHours(24))
                ->get();

            foreach ($pendingQueries as $pq) {
                $alreadySent = TenantAlert::where('tenant_id', $tenant->id)
                    ->where('type', TenantAlert::TYPE_QUERY_RESPONSE)
                    ->where('created_at', '>=', now()->subHours(48))
                    ->exists();

                if (! $alreadySent) {
                    $this->dispatchAlert(
                        $tenant,
                        TenantAlert::TYPE_QUERY_RESPONSE,
                        "Response Requested: '{$pq->title}'",
                        "Platform administration has requested feedback or verification regarding query #QUERY-{$pq->id}. Please submit your response in the support hub.",
                        TenantAlert::SEVERITY_INFO,
                        route('tenant.queries.show', $pq),
                        'Open Query & Respond',
                        true
                    );
                    $dispatchedCount++;
                    $summary[] = "{$tenant->name}: Reminder for query #{$pq->id} sent";
                }
            }
        }

        return [
            'dispatched_count' => $dispatchedCount,
            'summary' => $summary,
        ];
    }
}
