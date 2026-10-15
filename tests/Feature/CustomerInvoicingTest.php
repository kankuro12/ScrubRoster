<?php

namespace Tests\Feature;

use App\Mail\CustomerInvoiceMail;
use App\Models\Client;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Invoices\CustomerInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerInvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $tenantAdmin;
    protected Client $client;
    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Apex Facility Cleaners',
            'slug' => 'apex',
            'status' => 'active',
            'subscription_status' => 'active',
        ]);

        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Apex Admin',
            'email' => 'admin@apex.test',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Metro Commercial Plaza',
            'company_name' => 'Metro Real Estate Group',
            'email' => 'accounts@metroplaza.test',
            'phone' => '555-0199',
            'address' => '100 Metro Blvd, Suite 200',
            'billing_address' => 'PO Box 888, Metroville',
            'active' => true,
        ]);

        $this->property = Property::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'name' => 'Metro Tower B',
            'address' => '102 Metro Blvd',
            'property_code' => 'PROP-MTB',
            'active' => true,
        ]);
    }

    public function test_tenant_admin_can_view_customer_invoices_list_and_create_page(): void
    {
        $response = $this->actingAs($this->tenantAdmin)->get(route('customer-invoices.index'));
        $response->assertOk()
            ->assertSee('Customer Invoices')
            ->assertSee('Create Customer Invoice');

        $createResponse = $this->actingAs($this->tenantAdmin)->get(route('customer-invoices.create'));
        $createResponse->assertOk()
            ->assertSee('Metro Commercial Plaza')
            ->assertSee('Invoice Line Items');
    }

    public function test_tenant_admin_can_create_customer_invoice_with_line_items_and_recalculate_totals(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->tenantAdmin)->post(route('customer-invoices.store'), [
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-2026-9001',
            'invoice_date' => '2026-10-06',
            'due_date' => '2026-10-20',
            'tax_rate' => 10.0, // 10%
            'discount_amount' => 50.00,
            'recipient_name' => 'Metro Accounts Dept',
            'recipient_email' => 'accounts@metroplaza.test',
            'items' => [
                [
                    'description' => 'Commercial Lobby Deep Sanitization',
                    'property_id' => $this->property->id,
                    'quantity' => 2.0,
                    'unit_price' => 200.00, // $400 subtotal
                ],
                [
                    'description' => 'Window Cleaning Service',
                    'quantity' => 1.0,
                    'unit_price' => 150.00, // $150 subtotal
                ],
            ],
            'send_now' => 1,
        ]);

        $invoice = CustomerInvoice::where('invoice_number', 'INV-2026-9001')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('customer-invoices.show', $invoice));

        // Subtotal = $400 + $150 = $550.00
        // Tax 10% = $55.00
        // Discount = $50.00
        // Total = $550 + $55 - $50 = $555.00
        $this->assertEquals(550.00, (float) $invoice->subtotal);
        $this->assertEquals(55.00, (float) $invoice->tax_amount);
        $this->assertEquals(555.00, (float) $invoice->total_amount);
        $this->assertEquals(CustomerInvoice::STATUS_SENT, $invoice->status);
        $this->assertNotNull($invoice->sent_at);

        $this->assertCount(2, $invoice->items);
        Mail::assertQueued(CustomerInvoiceMail::class);
    }

    public function test_tenant_admin_can_view_printable_invoice_and_send_email(): void
    {
        Mail::fake();

        $invoiceService = app(CustomerInvoiceService::class);
        $invoice = $invoiceService->createInvoice($this->tenant, [
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-2026-9002',
            'invoice_date' => '2026-10-06',
            'items' => [
                [
                    'description' => 'Nightly Office Cleaning',
                    'quantity' => 5.0,
                    'unit_price' => 80.00,
                ],
            ],
        ], $this->tenantAdmin);

        $response = $this->actingAs($this->tenantAdmin)->get(route('customer-invoices.show', $invoice));
        $response->assertOk()
            ->assertSee('INV-2026-9002')
            ->assertSee('Metro Commercial Plaza')
            ->assertSee('Nightly Office Cleaning')
            ->assertSee('$400.00');

        // Test manual send action
        $sendResponse = $this->actingAs($this->tenantAdmin)->post(route('customer-invoices.send', $invoice), [
            'recipient_email' => 'finance@metroplaza.test',
        ]);

        $sendResponse->assertRedirect();
        Mail::assertQueued(CustomerInvoiceMail::class, function ($mail) {
            return $mail->hasTo('finance@metroplaza.test');
        });

        $invoice->refresh();
        $this->assertEquals(CustomerInvoice::STATUS_SENT, $invoice->status);
    }

    public function test_tenant_admin_can_mark_customer_invoice_as_paid(): void
    {
        $invoiceService = app(CustomerInvoiceService::class);
        $invoice = $invoiceService->createInvoice($this->tenant, [
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-2026-9003',
            'invoice_date' => '2026-10-06',
            'items' => [
                [
                    'description' => 'Carpet Steam Wash',
                    'quantity' => 1.0,
                    'unit_price' => 300.00,
                ],
            ],
        ], $this->tenantAdmin);

        $this->actingAs($this->tenantAdmin)->post(route('customer-invoices.mark-paid', $invoice));

        $invoice->refresh();
        $this->assertEquals(CustomerInvoice::STATUS_PAID, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }
}
