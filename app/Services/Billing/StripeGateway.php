<?php

namespace App\Services\Billing;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

class StripeGateway
{
    protected function client(): StripeClient
    {
        $secret = config('services.stripe.secret');
        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Stripe payments are not configured. Please contact your administrator.');
        }

        $http = new CurlClient;
        $http->setConnectTimeout(5);
        $http->setTimeout(20);

        ApiRequestor::setHttpClient($http);

        return new StripeClient(['api_key' => $secret, 'max_network_retries' => 1]);
    }

    public function customer(Tenant $tenant): string
    {
        if ($tenant->stripe_customer_id) {
            return $tenant->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'metadata' => ['tenant_id' => (string) $tenant->id],
        ], ['idempotency_key' => 'scrubroster-customer-'.$tenant->id]);

        DB::transaction(function () use ($tenant, $customer): void {
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $locked->update(['stripe_customer_id' => $customer->id]);
        });

        return $customer->id;
    }

    public function checkout(array $parameters, string $key): array
    {
        return $this->client()->checkout->sessions->create($parameters, ['idempotency_key' => $key])->toArray();
    }

    public function subscription(string $id): array
    {
        return $this->client()->subscriptions->retrieve($id, ['expand' => ['latest_invoice']])->toArray();
    }

    public function session(string $id): array
    {
        return $this->client()->checkout->sessions->retrieve($id)->toArray();
    }

    public function invoice(string $id): array
    {
        return $this->client()->invoices->retrieve($id)->toArray();
    }

    public function coupon(array $parameters, string $key): string
    {
        return $this->client()->coupons->create($parameters, ['idempotency_key' => $key])->id;
    }

    public function portal(string $customerId, string $returnUrl): string
    {
        $parameters = ['customer' => $customerId, 'return_url' => $returnUrl];
        if ($configuration = config('services.stripe.portal_configuration')) {
            $parameters['configuration'] = $configuration;
        }

        return $this->client()->billingPortal->sessions->create($parameters)->url;
    }
}
