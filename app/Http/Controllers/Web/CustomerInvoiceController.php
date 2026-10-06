<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CustomerInvoice;
use App\Models\Property;
use App\Models\Task;
use App\Services\Invoices\CustomerInvoiceService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerInvoiceController extends Controller
{
    /**
     * List all customer invoices for the current tenant.
     */
    public function index(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $query = CustomerInvoice::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['client', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->query('client_id'));
        }

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_email', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        $clients = Client::where('active', true)->orderBy('name')->get();

        $stats = [
            'total' => CustomerInvoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
            'paid_amount' => (float) CustomerInvoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('status', CustomerInvoice::STATUS_PAID)->sum('total_amount'),
            'unpaid_amount' => (float) CustomerInvoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->whereIn('status', [CustomerInvoice::STATUS_SENT, CustomerInvoice::STATUS_OVERDUE])->sum('total_amount'),
            'drafts_count' => CustomerInvoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('status', CustomerInvoice::STATUS_DRAFT)->count(),
        ];

        return view('pages.customer-invoices.index', [
            'tenant' => $tenant,
            'invoices' => $invoices,
            'clients' => $clients,
            'stats' => $stats,
        ]);
    }

    /**
     * Show form to create a new customer invoice.
     */
    public function create(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $clients = Client::where('active', true)->with('properties')->orderBy('name')->get();
        $properties = Property::where('active', true)->orderBy('name')->get();

        // Optional preselection of client
        $selectedClientId = $request->query('client_id');
        $selectedPropertyId = $request->query('property_id');

        $nextInvoiceNumber = CustomerInvoice::generateNextInvoiceNumber($tenant->id);

        return view('pages.customer-invoices.create', [
            'tenant' => $tenant,
            'clients' => $clients,
            'properties' => $properties,
            'selectedClientId' => $selectedClientId,
            'selectedPropertyId' => $selectedPropertyId,
            'nextInvoiceNumber' => $nextInvoiceNumber,
        ]);
    }

    /**
     * Store new customer invoice.
     */
    public function store(Request $request, TenantContext $context, CustomerInvoiceService $invoiceService): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.property_id' => ['nullable', 'exists:properties,id'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $invoice = $invoiceService->createInvoice($tenant, $validated, $request->user());

        if ($request->boolean('send_now')) {
            $invoiceService->sendInvoice($invoice);
            return redirect()->route('customer-invoices.show', $invoice)
                ->with('status', "Invoice #{$invoice->invoice_number} generated and emailed to {$invoice->recipient_email}!");
        }

        return redirect()->route('customer-invoices.show', $invoice)
            ->with('status', "Invoice #{$invoice->invoice_number} created successfully.");
    }

    /**
     * Show/print customer invoice.
     */
    public function show(CustomerInvoice $customerInvoice, Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if ($customerInvoice->tenant_id !== $tenant?->id && $request->user()?->role !== \App\Models\User::ROLE_ADMIN && $request->user()?->tenant_id !== null) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        $customerInvoice->load(['client', 'items.property']);

        return view('pages.customer-invoices.show', [
            'tenant' => $tenant ?? $customerInvoice->tenant,
            'invoice' => $customerInvoice,
            'client' => $customerInvoice->client,
        ]);
    }

    /**
     * Email customer invoice to client.
     */
    public function send(CustomerInvoice $customerInvoice, Request $request, CustomerInvoiceService $invoiceService): RedirectResponse
    {
        $recipient = $request->input('recipient_email') ?: null;
        $sent = $invoiceService->sendInvoice($customerInvoice, $recipient);

        if ($sent) {
            return redirect()->back()->with('status', "Invoice #{$customerInvoice->invoice_number} sent successfully to {$customerInvoice->recipient_email}!");
        }

        return redirect()->back()->withErrors(['invoice' => 'Could not send invoice email. Please ensure client has a valid email address.']);
    }

    /**
     * Mark customer invoice as paid.
     */
    public function markPaid(CustomerInvoice $customerInvoice, CustomerInvoiceService $invoiceService): RedirectResponse
    {
        $invoiceService->markAsPaid($customerInvoice);

        return redirect()->back()->with('status', "Invoice #{$customerInvoice->invoice_number} marked as PAID.");
    }

    /**
     * Delete/cancel invoice.
     */
    public function destroy(CustomerInvoice $customerInvoice): RedirectResponse
    {
        $customerInvoice->delete();

        return redirect()->route('customer-invoices.index')
            ->with('status', "Invoice #{$customerInvoice->invoice_number} removed.");
    }
}
