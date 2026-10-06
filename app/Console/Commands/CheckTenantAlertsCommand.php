<?php

namespace App\Console\Commands;

use App\Services\Tenants\TenantAlertService;
use Illuminate\Console\Command;

class CheckTenantAlertsCommand extends Command
{
    protected $signature = 'tenants:check-alerts {--tenant= : Specific tenant ID to check}';
    protected $description = 'Scan active tenants for expiring trials, quota limits, and pending queries, and dispatch alerts & emails';

    public function handle(TenantAlertService $alertService): int
    {
        $tenantId = $this->option('tenant');
        $tenant = $tenantId ? \App\Models\Tenant::find($tenantId) : null;

        $this->info('Scanning tenant subscriptions, trial statuses, cleaner quotas, and queries...');

        $result = $alertService->scanAndDispatchAutomatedAlerts($tenant);

        $this->info("Completed! Dispatched {$result['dispatched_count']} proactive alert(s).");
        foreach ($result['summary'] as $item) {
            $this->line(" - {$item}");
        }

        return self::SUCCESS;
    }
}
