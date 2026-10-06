@extends('layouts.app')

@section('title', 'Customer Invoices')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Action Row -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-receipt-cutoff me-1"></i> Client Billing · Customer Invoicing</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Customer Invoices</h1>
        </div>
        <div>
            <a href="{{ route('customer-invoices.create') }}" class="btn btn-warning font-monospace fw-bold">
                <i class="bi bi-plus-circle me-1"></i> Create Customer Invoice
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success font-monospace d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <!-- Metric Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #10b981 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Collected Revenue</span>
                <div class="h3 fw-bold font-archivo text-success mb-0 mt-1">${{ number_format($stats['paid_amount'], 2) }}</div>
                <span class="text-muted small font-monospace">From settled invoices</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #eab308 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Outstanding Receivables</span>
                <div class="h3 fw-bold font-archivo text-warning mb-0 mt-1">${{ number_format($stats['unpaid_amount'], 2) }}</div>
                <span class="text-muted small font-monospace">Awaiting customer payment</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #0284c7 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Total Invoices</span>
                <div class="h3 fw-bold font-archivo mb-0 mt-1" style="color: var(--cw-text-strong, #0F172A);">{{ $stats['total'] }}</div>
                <span class="text-muted small font-monospace">All generated invoices</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #64748b !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Draft Invoices</span>
                <div class="h3 fw-bold font-archivo mb-0 mt-1" style="color: var(--cw-text-strong, #0F172A);">{{ $stats['drafts_count'] }}</div>
                <span class="text-muted small font-monospace">Unsent invoice drafts</span>
            </div>
        </div>
    </div>

    <!-- Filters Rail -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('customer-invoices.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm font-monospace" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="client_id" class="form-select form-select-sm font-monospace" onchange="this.form.submit()">
                        <option value="">All Clients</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} {{ $c->company_name ? "({$c->company_name})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search invoice #, client, email..." class="form-control form-control-sm font-monospace">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary font-monospace w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-receipt me-1"></i> Invoices Register</h5>
            <span class="badge bg-secondary font-monospace">{{ $invoices->total() }} Total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="font-monospace text-secondary small text-uppercase bg-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Client / Customer</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Sent To</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('customer-invoices.show', $inv) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                    {{ $inv->invoice_number }}
                                </a>
                            </td>
                            <td>
                                <strong class="font-archivo text-dark">{{ $inv->recipient_name ?: $inv->client?->name }}</strong>
                                @if($inv->client?->company_name)
                                    <div class="small text-secondary font-sans">{{ $inv->client->company_name }}</div>
                                @endif
                            </td>
                            <td class="font-monospace small">{{ $inv->invoice_date->format('M j, Y') }}</td>
                            <td class="font-monospace small">
                                {{ $inv->due_date ? $inv->due_date->format('M j, Y') : '—' }}
                            </td>
                            <td>
                                <span class="fw-bold font-monospace fs-6 {{ $inv->status === 'paid' ? 'text-success' : 'text-dark' }}">
                                    ${{ number_format($inv->total_amount, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($inv->status === 'paid')
                                    <span class="badge bg-success-subtle text-success font-monospace">PAID</span>
                                @elseif($inv->status === 'sent')
                                    <span class="badge bg-primary-subtle text-primary font-monospace">SENT</span>
                                @elseif($inv->status === 'overdue')
                                    <span class="badge bg-danger-subtle text-danger font-monospace">OVERDUE</span>
                                @elseif($inv->status === 'cancelled')
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">CANCELLED</span>
                                @else
                                    <span class="badge bg-light text-secondary font-monospace">DRAFT</span>
                                @endif
                            </td>
                            <td class="font-monospace small text-muted">
                                @if($inv->sent_at)
                                    <span class="text-success"><i class="bi bi-envelope-check me-1"></i>{{ $inv->sent_at->format('M j') }}</span>
                                @else
                                    <span class="text-secondary">Not sent</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('customer-invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary font-monospace py-0 px-2" title="View & Print Invoice">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                                <form action="{{ route('customer-invoices.send', $inv) }}" method="POST" class="d-inline ms-1">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary font-monospace py-0 px-2" title="Send email to client">
                                        <i class="bi bi-envelope"></i> Send
                                    </button>
                                </form>
                                @if($inv->status !== 'paid')
                                    <form action="{{ route('customer-invoices.mark-paid', $inv) }}" method="POST" class="d-inline ms-1">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success font-monospace py-0 px-2" title="Mark Paid">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-receipt fs-1 d-block mb-2 text-muted"></i>
                                No customer invoices found. Click "Create Customer Invoice" to bill your first client.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer bg-transparent py-3">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
