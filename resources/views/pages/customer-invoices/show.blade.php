@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->invoice_number)

@section('content')
<div class="container py-4">

    <!-- Action Bar / Print & Email Controls -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
        <div>
            <a href="{{ route('customer-invoices.index') }}" class="btn btn-outline-secondary font-monospace btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Invoices
            </a>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($invoice->status !== 'paid')
                <form action="{{ route('customer-invoices.mark-paid', $invoice) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success font-monospace btn-sm" onclick="return confirm('Mark this invoice as PAID?')">
                        <i class="bi bi-check2-circle me-1"></i> Mark as Paid
                    </button>
                </form>
            @endif

            <button type="button" class="btn btn-primary font-monospace btn-sm" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                <i class="bi bi-envelope me-1"></i> Send / Resend Email
            </button>

            <button onclick="window.print()" class="btn btn-outline-secondary font-monospace btn-sm">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success font-monospace d-flex align-items-center mb-4 no-print" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <!-- Printable Invoice Sheet -->
    <div class="card border-0 shadow invoice-sheet" style="max-width: 850px; margin: 0 auto; background: var(--cw-surface, #ffffff); border: 1px solid var(--cw-border, #e2e8f0); border-radius: var(--cw-radius-md, 8px);">
        <!-- Invoice Header Banner -->
        <div class="p-4 border-bottom" style="background: #0f172a; color: #ffffff; border-top-left-radius: inherit; border-top-right-radius: inherit;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 fw-bold font-archivo mb-0 text-white">{{ $tenant->name }}</h2>
                    <span class="font-monospace small text-info">COMMERCIAL CLEANING & FACILITY DISPATCH</span>
                </div>
                <div class="text-end font-monospace">
                    @if($invoice->status === 'paid')
                        <div class="badge bg-success-subtle text-success fs-6 px-3 py-1 mb-1">PAID IN FULL</div>
                    @elseif($invoice->status === 'sent')
                        <div class="badge bg-primary-subtle text-primary fs-6 px-3 py-1 mb-1">INVOICE ISSUED</div>
                    @elseif($invoice->status === 'overdue')
                        <div class="badge bg-danger-subtle text-danger fs-6 px-3 py-1 mb-1">PAYMENT OVERDUE</div>
                    @else
                        <div class="badge bg-secondary text-white fs-6 px-3 py-1 mb-1">DRAFT</div>
                    @endif
                    <div class="text-secondary small">TAX INVOICE</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Metadata Grid -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <span class="text-secondary font-monospace small text-uppercase d-block mb-1">Billed To</span>
                    <h5 class="fw-bold font-archivo mb-1 text-dark">{{ $invoice->recipient_name ?: $client->name }}</h5>
                    <div class="small text-secondary font-monospace">
                        @if($client->company_name)
                            <strong>{{ $client->company_name }}</strong><br>
                        @endif
                        @if($invoice->billing_address)
                            {!! nl2br(e($invoice->billing_address)) !!}<br>
                        @elseif($client->billing_address)
                            {!! nl2br(e($client->billing_address)) !!}<br>
                        @endif
                        Email: {{ $invoice->recipient_email ?: $client->email }}
                    </div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <span class="text-secondary font-monospace small text-uppercase d-block mb-1">Invoice Details</span>
                    <h5 class="fw-bold font-monospace text-primary mb-1">#{{ $invoice->invoice_number }}</h5>
                    <div class="small text-secondary font-monospace">
                        Issue Date: <strong>{{ $invoice->invoice_date->format('M j, Y') }}</strong><br>
                        @if($invoice->due_date)
                            Due Date: <strong>{{ $invoice->due_date->format('M j, Y') }}</strong><br>
                        @endif
                        Status: <strong class="text-uppercase">{{ $invoice->status }}</strong>
                        @if($invoice->paid_at)
                            (Paid on {{ $invoice->paid_at->format('M j, Y') }})
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light font-monospace small text-uppercase">
                        <tr>
                            <th style="width: 50%;">Description / Service</th>
                            <th class="text-center" style="width: 15%;">Quantity</th>
                            <th class="text-end" style="width: 15%;">Rate</th>
                            <th class="text-end" style="width: 20%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold font-archivo">{{ $item->description }}</div>
                                    @if($item->property)
                                        <div class="small text-secondary font-monospace">Property: {{ $item->property->name }}</div>
                                    @endif
                                </td>
                                <td class="text-center font-monospace">{{ number_format($item->quantity, 2) }}</td>
                                <td class="text-end font-monospace">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end font-monospace fw-bold">${{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-monospace">
                        <tr>
                            <td colspan="3" class="text-end text-secondary">Subtotal:</td>
                            <td class="text-end fw-bold">${{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($invoice->discount_amount > 0)
                            <tr class="text-success">
                                <td colspan="3" class="text-end">Discount Applied:</td>
                                <td class="text-end fw-bold">-${{ number_format($invoice->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($invoice->tax_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end text-secondary">Tax ({{ $invoice->tax_rate }}%):</td>
                                <td class="text-end fw-bold">${{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold font-archivo fs-5">Total Due ({{ $invoice->currency }}):</td>
                            <td class="text-end fw-bold font-archivo fs-5 text-primary">
                                ${{ number_format($invoice->total_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes & Terms -->
            @if($invoice->notes)
                <div class="p-3 bg-light rounded small text-secondary font-sans mb-3">
                    <strong class="font-monospace text-dark d-block mb-1">Notes:</strong>
                    {!! nl2br(e($invoice->notes)) !!}
                </div>
            @endif

            @if($invoice->terms)
                <div class="small text-muted font-monospace mb-4">
                    <strong>Terms & Conditions:</strong> {!! nl2br(e($invoice->terms)) !!}
                </div>
            @endif

            <div class="text-center text-secondary small font-monospace pt-3 border-top">
                {{ $tenant->name }} · Invoiced via ScrubRoster Operations Platform
            </div>
        </div>
    </div>

</div>

<!-- Modal: Send Invoice via Email -->
<div class="modal fade" id="sendEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('customer-invoices.send', $invoice) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title font-archivo fw-bold"><i class="bi bi-envelope-paper me-1"></i> Email Invoice to Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label font-monospace small text-secondary">Recipient Email Address</label>
                    <input type="email" name="recipient_email" value="{{ $invoice->recipient_email ?: $client->email }}" class="form-control font-monospace" required>
                    <div class="form-text small">An official HTML invoice with service items and pricing will be emailed immediately.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary font-monospace" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary font-monospace fw-bold">
                    <i class="bi bi-send me-1"></i> Send Invoice Now
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media print {
    .no-print, nav, .sidebar, header, footer {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .invoice-sheet {
        box-shadow: none !important;
        border: none !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }
}
</style>
@endsection
