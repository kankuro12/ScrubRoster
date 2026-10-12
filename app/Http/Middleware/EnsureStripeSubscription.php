<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStripeSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('billing.*', 'tenant-manager.*', 'tenant.queries.*', 'tenant.alerts.*', 'notifications*', 'profile*', 'manual.*')
            || $request->is('api/v1/me', 'api/v1/auth/logout')) {
            return $next($request);
        }
        $tenant = $request->user()?->tenant;
        if ($tenant?->stripe_subscription_id && ! $tenant->isSubscriptionActive()) {
            abort(403, 'This workspace requires a paid subscription. A tenant administrator can manage billing.');
        }

        return $next($request);
    }
}
