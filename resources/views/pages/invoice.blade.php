@extends('layouts.app')

@section('title', 'Invoice ' . $invoice['invoice_number'])

@section('content')
<div class="container py-4">
    <!-- Action Bar / Print Controls -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary font-monospace btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Billing
            </a>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary font-monospace btn-sm">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
            </button>
            <form action="{{ route('billing.invoices.send', $payment->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary font-monospace btn-sm">
                    <i class="bi bi-envelope me-1"></i> Resend to Email
                </button>
            </form>
        </div>
    </div>

    <!-- Printable Invoice Sheet -->
    <div class="card border-0 shadow invoice-sheet" style="max-width: 850px; margin: 0 auto; background: var(--cw-surface, #ffffff); border: 1px solid var(--cw-border, #e2e8f0); border-radius: var(--cw-radius-md, 8px);">
        <!-- Invoice Header Banner -->
        <div class="p-4 border-bottom" style="background: #0f172a; color: #ffffff; border-top-left-radius: inherit; border-top-right-radius: inherit;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 fw-bold font-archivo mb-0 text-white">ScrubRoster</h2>
                    <span class="font-monospace small text-info">FIELD OPERATIONS & DISPATCH PLATFORM</span>
                </div>
                <div class="text-end font-monospace">
                    <div class="badge bg-success-subtle text-success fs-6 px-3 py-1 mb-1">PAID IN FULL</div>
                    <div class="text-secondary small">RECEIPT / TAX INVOICE</div>
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Metadata Grid -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <span class="text-secondary font-monospace small text-uppercase d-block mb-1">Billed To</span>
                    <h5 class="fw-bold font-archivo mb-1 text-dark">{{ $tenant->name }}</h5>
                    <div class="small text-secondary font-monospace">
                        Workspace Slug: <strong>/{{ $tenant->slug }}</strong><br>
                        Recipient: {{ $invoice['recipient_email'] ?? ($tenant->admins()->first()?->email ?? 'admin@' . $tenant->slug . '.com') }}
                    </div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <span class="text-secondary font-monospace small text-uppercase d-block mb-1">Invoice Details</span>
                    <h5 class="fw-bold font-monospace text-primary mb-1">#{{ $invoice['invoice_number'] }}</h5>
                    <div class="small text-secondary font-monospace">
                        Issued Date: {{ $invoice['date'] }}<br>
                        Gateway Ref: <code>{{ $payment->gateway_transaction_id }}</code><br>
                        Payment Method: {{ strtoupper($payment->payment_gateway) }}
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light font-monospace small text-uppercase">
                        <tr>
                            <th style="width: 70%;">Description & Service Items</th>
                            <th class="text-end" style="width: 30%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-bold font-archivo">{{ $invoice['package_name'] }} ({{ $invoice['billing_cycle'] }})</div>
                                <div class="small text-secondary">Personnel Quota: {{ $invoice['max_employees'] ? $invoice['max_employees'] . ' Cleaners' : 'Unlimited Fleet' }}</div>
                            </td>
                            <td class="text-end font-monospace fw-bold">
                                ${{ number_format($invoice['base_price'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="font-monospace">
                        <tr>
                            <td class="text-end text-secondary">Subtotal:</td>
                            <td class="text-end">${{ number_format($invoice['base_price'], 2) }}</td>
                        </tr>
                        @if(($invoice['discount'] ?? 0) > 0)
                            <tr class="text-success">
                                <td class="text-end">Promo Discount ({{ $invoice['coupon_code'] ?? 'Coupon' }}):</td>
                                <td class="text-end">-${{ number_format($invoice['discount'], 2) }}</td>
                            </tr>
                        @endif
                        <tr class="table-light">
                            <td class="text-end fw-bold font-archivo fs-5">Amount Paid ({{ $invoice['currency'] }}):</td>
                            <td class="text-end fw-bold font-archivo fs-5 text-primary">
                                ${{ number_format($invoice['total_amount'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notice & Footer -->
            <div class="p-3 bg-light rounded small text-secondary font-monospace mb-4">
                <i class="bi bi-shield-check text-success me-1"></i>
                This document serves as an official electronic receipt. Payments processed securely via {{ ucfirst($payment->payment_gateway) }}. All subscription charges are recurring per your selected {{ $payment->billing_cycle }} schedule until cancelled in workspace settings.
            </div>

            <div class="text-center text-secondary small font-monospace pt-3 border-top">
                ScrubRoster Inc. · Automated Dispatch & Facility Operations Cloud · support@scrubroster.com
            </div>
        </div>
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
