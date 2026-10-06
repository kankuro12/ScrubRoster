@extends('layouts.app')

@section('title', 'Create Customer Invoice')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Eyebrow -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <a href="{{ route('customer-invoices.index') }}" class="btn btn-outline-secondary btn-sm font-monospace mb-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Invoices
            </a>
            <span class="eyebrow d-block"><i class="bi bi-receipt-cutoff me-1"></i> Client Billing · Customer Invoicing</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Create Customer Invoice</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger font-monospace mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customer-invoices.store') }}" id="invoiceForm">
        @csrf

        <div class="row g-4">
            <!-- Left Main Column: Client & Line Items -->
            <div class="col-lg-8">
                <!-- Client Details Card -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10">
                        <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-person-badge me-1"></i> Client & Recipient Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-monospace small text-secondary">Select Customer / Client <span class="text-danger">*</span></label>
                                <select name="client_id" id="clientSelect" class="form-select font-monospace" required>
                                    <option value="">Choose Client...</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}"
                                                data-name="{{ $c->name }}"
                                                data-email="{{ $c->email }}"
                                                data-address="{{ $c->billing_address ?: $c->address }}"
                                                {{ (old('client_id', $selectedClientId) == $c->id) ? 'selected' : '' }}>
                                            {{ $c->name }} {{ $c->company_name ? "({$c->company_name})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-monospace small text-secondary">Recipient Email (for sending)</label>
                                <input type="email" name="recipient_email" id="recipientEmail" value="{{ old('recipient_email') }}" placeholder="billing@client.com" class="form-control font-monospace">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-monospace small text-secondary">Recipient Name / Attention</label>
                                <input type="text" name="recipient_name" id="recipientName" value="{{ old('recipient_name') }}" placeholder="Accounts Payable" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-monospace small text-secondary">Billing Address</label>
                                <input type="text" name="billing_address" id="billingAddress" value="{{ old('billing_address') }}" placeholder="123 Commercial Way, Suite 400" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items Card -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-list-check me-1"></i> Invoice Line Items</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary font-monospace" id="addItemBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Service Item
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="itemsTable">
                            <thead class="bg-light font-monospace small text-uppercase text-secondary">
                                <tr>
                                    <th style="width: 45%;">Description / Cleaning Service</th>
                                    <th style="width: 20%;">Property</th>
                                    <th style="width: 12%;">Qty / Hours</th>
                                    <th style="width: 13%;">Rate ($)</th>
                                    <th style="width: 10%;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTbody">
                                <tr class="item-row">
                                    <td>
                                        <input type="text" name="items[0][description]" class="form-control form-control-sm" required placeholder="e.g. Commercial Office Deep Clean">
                                    </td>
                                    <td>
                                        <select name="items[0][property_id]" class="form-select form-select-sm font-monospace">
                                            <option value="">All / None</option>
                                            @foreach($properties as $p)
                                                <option value="{{ $p->id }}" {{ $selectedPropertyId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.5" min="0.5" name="items[0][quantity]" class="form-control form-control-sm font-monospace item-qty" value="1.0" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][unit_price]" class="form-control form-control-sm font-monospace item-rate" value="150.00" required>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes & Terms -->
                <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-monospace small text-secondary">Client Notes (Printed on invoice)</label>
                                <textarea name="notes" rows="3" class="form-control" placeholder="Thank you for your business! Cleaned per service agreement.">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-monospace small text-secondary">Terms & Conditions</label>
                                <textarea name="terms" rows="3" class="form-control font-monospace small">{{ old('terms', 'Payment due within 14 days of invoice issue date. Bank transfer or credit card accepted.') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Date & Totals -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 20px; background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10">
                        <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-calculator me-1"></i> Invoice Summary</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label font-monospace small text-secondary">Invoice Number</label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $nextInvoiceNumber) }}" class="form-control font-monospace fw-bold text-primary" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label font-monospace small text-secondary">Issue Date</label>
                                <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" class="form-control font-monospace" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label font-monospace small text-secondary">Due Date</label>
                                <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+14 days'))) }}" class="form-control font-monospace">
                            </div>
                        </div>

                        <hr class="border-secondary border-opacity-10 my-3">

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label font-monospace small text-secondary">Tax Rate (%)</label>
                                <input type="number" step="0.1" min="0" max="100" name="tax_rate" id="taxRateInput" value="{{ old('tax_rate', '0.00') }}" class="form-control font-monospace">
                            </div>
                            <div class="col-6">
                                <label class="form-label font-monospace small text-secondary">Discount ($)</label>
                                <input type="number" step="0.01" min="0" name="discount_amount" id="discountInput" value="{{ old('discount_amount', '0.00') }}" class="form-control font-monospace">
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded mb-4 font-monospace small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Subtotal:</span>
                                <span class="fw-bold" id="displaySubtotal">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Tax:</span>
                                <span id="displayTax">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount:</span>
                                <span id="displayDiscount">-$0.00</span>
                            </div>
                            <hr class="my-2 border-secondary border-opacity-25">
                            <div class="d-flex justify-content-between fs-5 fw-bold text-dark font-archivo">
                                <span>Total Due:</span>
                                <span class="text-primary" id="displayTotal">$0.00</span>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="send_now" value="1" id="sendNowCheck" checked>
                            <label class="form-check-label font-sans small fw-bold text-dark" for="sendNowCheck">
                                Send invoice email to client immediately upon creation
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning font-monospace fw-bold py-2">
                                <i class="bi bi-file-earmark-check me-1"></i> Generate & Issue Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
$(document).ready(function() {
    let rowIndex = 1;

    // Fill client defaults when selected
    $('#clientSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            $('#recipientEmail').val(selected.data('email') || '');
            $('#recipientName').val(selected.data('name') || '');
            $('#billingAddress').val(selected.data('address') || '');
        }
    });

    // Add item row
    $('#addItemBtn').on('click', function() {
        const propertiesOptions = $('#itemsTbody select:first').html();
        const newRow = `
            <tr class="item-row">
                <td>
                    <input type="text" name="items[${rowIndex}][description]" class="form-control form-control-sm" required placeholder="Service description...">
                </td>
                <td>
                    <select name="items[${rowIndex}][property_id]" class="form-select form-select-sm font-monospace">
                        ${propertiesOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.5" min="0.5" name="items[${rowIndex}][quantity]" class="form-control form-control-sm font-monospace item-qty" value="1.0" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" class="form-control form-control-sm font-monospace item-rate" value="100.00" required>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#itemsTbody').append(newRow);
        rowIndex++;
        updateRowRemoveButtons();
        recalcTotals();
    });

    // Remove item row
    $(document).on('click', '.remove-item-btn', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            updateRowRemoveButtons();
            recalcTotals();
        }
    });

    function updateRowRemoveButtons() {
        if ($('.item-row').length <= 1) {
            $('.remove-item-btn').prop('disabled', true);
        } else {
            $('.remove-item-btn').prop('disabled', false);
        }
    }

    // Dynamic calculations
    $(document).on('input', '.item-qty, .item-rate, #taxRateInput, #discountInput', function() {
        recalcTotals();
    });

    function recalcTotals() {
        let subtotal = 0;
        $('.item-row').each(function() {
            const qty = parseFloat($(this).find('.item-qty').val()) || 0;
            const rate = parseFloat($(this).find('.item-rate').val()) || 0;
            subtotal += (qty * rate);
        });

        const taxRate = parseFloat($('#taxRateInput').val()) || 0;
        const discount = parseFloat($('#discountInput').val()) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        const total = Math.max(0, subtotal + taxAmount - discount);

        $('#displaySubtotal').text('$' + subtotal.toFixed(2));
        $('#displayTax').text('$' + taxAmount.toFixed(2));
        $('#displayDiscount').text('-$' + discount.toFixed(2));
        $('#displayTotal').text('$' + total.toFixed(2));
    }

    recalcTotals();
    updateRowRemoveButtons();
});
</script>
@endpush
@endsection
