<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_prices_and_search_offers_follow_the_active_catalog(): void
    {
        Package::query()->update(['is_active' => false]);
        Package::createWithSlug([
            'name' => 'Small Team', 'price_monthly' => 123.45, 'price_yearly' => 1234.50,
            'max_employees' => 8, 'trial_days' => 7, 'is_active' => true,
        ]);
        Package::createWithSlug([
            'name' => 'Large Team', 'price_monthly' => 987.65, 'price_yearly' => 9876.50,
            'max_employees' => 80, 'trial_days' => 21, 'is_active' => true,
        ]);

        $response = $this->get(route('root'))->assertOk();
        $application = collect($response->viewData('schemaJsonLd')['@graph'])
            ->firstWhere('@type', 'SoftwareApplication');

        $this->assertSame('123.45', $application['offers']['lowPrice']);
        $this->assertSame('987.65', $application['offers']['highPrice']);
        $this->assertSame(2, $application['offers']['offerCount']);
        $response->assertSee('$123.45')->assertSee('$987.65')
            ->assertSee('7-day free trial')->assertSee('21-day free trial')
            ->assertDontSee('Starter Tier');
    }

    public function test_an_empty_catalog_does_not_advertise_nonexistent_offers(): void
    {
        Package::query()->update(['is_active' => false]);

        $response = $this->get(route('root'))->assertOk();
        $application = collect($response->viewData('schemaJsonLd')['@graph'])
            ->firstWhere('@type', 'SoftwareApplication');

        $this->assertArrayNotHasKey('offers', $application);
        $response->assertSee('Plans are being updated.')->assertDontSee('Growth Fleet');
    }
}
