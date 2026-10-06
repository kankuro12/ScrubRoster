@extends('layouts.app')

@section('title', 'Subscription & Billing')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Eyebrow -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-credit-card-2-front me-1"></i> Tenant Workspace · Subscription & Billing</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Workspace Plan & Upgrades</h1>
        </div>
        <div class="font-monospace text-secondary small">
            Workspace: <strong class="text-body fw-bold">{{ $tenant->name }}</strong> (<code>/{{ $tenant->slug }}</code>)
        </div>
    </div>

    <!-- Current Subscription Status 3-Up -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Active Tier -->
        <div class="col-md-4">
            <div class="card p-3 border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid var(--cw-primary, #0284C7) !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Current Plan Tier</span>
                <div class="h3 fw-bold mb-1 font-archivo" style="color: var(--cw-text-strong, #0F172A);">{{ $currentPackage?->name ?? 'Free Trial' }}</div>
                @if($tenant->isOnTrial())
                    <span class="badge bg-warning-subtle text-warning font-monospace align-self-start">
                        <i class="bi bi-clock-history me-1"></i> EVALUATION TRIAL ({{ $tenant->getRemainingTrialDays() }} days remaining)
                    </span>
                @elseif($tenant->subscription_status === 'active')
                    <span class="badge bg-success-subtle text-success font-monospace align-self-start">
                        <i class="bi bi-check-circle-fill me-1"></i> ACTIVE SUBSCRIPTION ({{ strtoupper($tenant->billing_cycle) }})
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger font-monospace align-self-start">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ strtoupper($tenant->subscription_status) }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Card 2: Cleaner Quota -->
        <div class="col-md-4">
            <div class="card p-3 border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #10b981 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Active Personnel Quota</span>
                <div class="h3 fw-bold mb-1 font-archivo" style="color: var(--cw-text-strong, #0F172A);">
                    {{ $activeEmployeesCount }} <span class="fs-6 text-secondary fw-normal font-monospace">/ {{ $maxEmployees ?: 'Unlimited' }} Cleaners</span>
                </div>
                <div class="progress mt-2" style="height: 6px; background: rgba(0,0,0,0.08);">
                    <div class="progress-bar {{ $quotaPercentage >= 90 ? 'bg-danger' : ($quotaPercentage >= 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $quotaPercentage }}%;"></div>
                </div>
                <span class="text-secondary small font-monospace mt-1">
                    {{ max(0, $maxEmployees - $activeEmployeesCount) }} employee slots available
                </span>
            </div>
        </div>

        <!-- Card 3: Cloud Integrations Included -->
        <div class="col-md-4">
            <div class="card p-3 border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #3b82f6 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Connected Integrations</span>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge {{ $tenant->google_calendar_sync_enabled ? 'bg-success' : 'bg-secondary' }} p-2 font-monospace">
                        <i class="bi bi-calendar-check me-1"></i> Google Calendar
                    </span>
                    <span class="badge {{ $tenant->google_sheet_sync_enabled ? 'bg-success' : 'bg-secondary' }} p-2 font-monospace">
                        <i class="bi bi-file-spreadsheet me-1"></i> Google Sheets
                    </span>
                </div>
                <span class="text-secondary small font-monospace mt-2">
                    <a href="{{ route('integrations.google') }}" class="text-primary text-decoration-none fw-semibold">Manage Google Sync Feeds &rarr;</a>
                </span>
            </div>
        </div>
    </div>

    <!-- Available Packages & Upgrades Grid -->
    <div class="card border-0 shadow-sm p-4 mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="font-archivo fw-bold mb-1" style="color: var(--cw-text-strong, #0F172A);">Select / Upgrade Subscription Package</h4>
                <p class="text-secondary small mb-0">Choose the employee quota capacity matching your commercial fleet.</p>
            </div>
            <!-- Billing Cycle Toggle -->
            <div class="btn-group font-monospace" role="group">
                <input type="radio" class="btn-check" name="cycleSelect" id="cycleMonthly" value="monthly" checked autocomplete="off">
                <label class="btn btn-outline-primary btn-sm" for="cycleMonthly">Monthly Billing</label>

                <input type="radio" class="btn-check" name="cycleSelect" id="cycleYearly" value="yearly" autocomplete="off">
                <label class="btn btn-outline-primary btn-sm" for="cycleYearly">Yearly (Save 20%)</label>
            </div>
        </div>

        <div class="row g-4">
            @foreach($packages as $pkg)
                <div class="col-lg-4">
                    <div class="card h-100 p-3" style="background: var(--cw-surface-2, #F8FAFC); border: 1px solid {{ $pkg->id == $tenant->package_id ? 'var(--cw-primary, #0284C7)' : 'var(--cw-border, #CBD5E1)' }} !important; border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">{{ $pkg->name }}</h5>
                            @if($pkg->id == $tenant->package_id)
                                <span class="badge bg-primary font-monospace">CURRENT PLAN</span>
                            @elseif($pkg->is_popular)
                                <span class="badge bg-success font-monospace">POPULAR</span>
                            @endif
                        </div>
                        <p class="text-secondary small mb-3">{{ $pkg->description }}</p>

                        <div class="h3 font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">
                            <span class="price-display" data-monthly="{{ $pkg->price_monthly }}" data-yearly="{{ $pkg->price_yearly }}">
                                ${{ number_format($pkg->price_monthly, 0) }}
                            </span>
                            <span class="fs-6 text-secondary fw-normal font-monospace cycle-label">/ month</span>
                        </div>

                        <div class="p-2 rounded my-3 font-monospace small" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); color: var(--cw-text-strong, #0F172A);">
                            <i class="bi bi-people-fill text-warning me-1"></i> Up to <strong>{{ $pkg->max_employees }}</strong> Active Cleaners
                        </div>

                        <ul class="list-unstyled small text-secondary mb-4 flex-grow-1">
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Mobile & Kiosk QR Badge Login</li>
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Google Calendar Feed Sync</li>
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Google Sheets Live Sync</li>
                            @if(is_array($pkg->features))
                                @foreach($pkg->features as $feat)
                                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> {{ $feat }}</li>
                                @endforeach
                            @endif
                        </ul>

                        <button type="button" class="btn btn-warning text-dark fw-bold w-100 open-checkout-btn"
                                data-package-id="{{ $pkg->id }}"
                                data-package-name="{{ $pkg->name }}"
                                data-monthly="{{ $pkg->price_monthly }}"
                                data-yearly="{{ $pkg->price_yearly }}"
                                data-max-emp="{{ $pkg->max_employees }}">
                            <i class="bi bi-shield-check me-1"></i> Select & Checkout
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10">
            <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-receipt me-1"></i> Payment Invoices & Receipts</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="font-monospace text-secondary small text-uppercase bg-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Plan Tier</th>
                        <th>Payment Gateway</th>
                        <th>Transaction ID</th>
                        <th>Billing Interval</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pmt)
                        <tr>
                            <td>
                                <strong class="font-monospace text-primary">{{ $pmt->getInvoiceNumber() }}</strong>
                            </td>
                            <td>{{ $pmt->created_at?->format('M j, Y H:i') }}</td>
                            <td>{{ $pmt->package?->name ?? 'Standard Plan' }}</td>
                            <td>
                                @if($pmt->payment_gateway === 'stripe')
                                    <span class="badge bg-primary-subtle text-primary font-monospace"><i class="bi bi-stripe"></i> STRIPE</span>
                                @else
                                    <span class="badge bg-info-subtle text-info font-monospace"><i class="bi bi-paypal"></i> PAYPAL</span>
                                @endif
                            </td>
                            <td><code>{{ $pmt->gateway_transaction_id }}</code></td>
                            <td><span class="badge bg-secondary font-monospace text-uppercase">{{ $pmt->billing_cycle }}</span></td>
                            <td class="fw-bold font-monospace text-success">${{ number_format($pmt->amount, 2) }}</td>
                            <td><span class="badge bg-success-subtle text-success font-monospace">{{ strtoupper($pmt->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('billing.invoices.show', $pmt->id) }}" target="_blank" class="btn btn-sm btn-outline-primary font-monospace py-0 px-2 me-1">
                                    <i class="bi bi-file-earmark-text me-1"></i> Invoice
                                </a>
                                <form action="{{ route('billing.invoices.send', $pmt->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary font-monospace py-0 px-2" title="Email invoice to billing email">
                                        <i class="bi bi-envelope"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-secondary">No previous payment invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="card-footer bg-transparent py-3">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Unified Checkout Modal (Stripe & PayPal) -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: #0d172e; border: 1px solid rgba(255,90,31,0.4);">
            <div class="modal-header border-secondary border-opacity-25">
                <div>
                    <h5 class="modal-title text-white font-archivo fw-bold" id="checkoutTitle">Upgrade Workspace Subscription</h5>
                    <span class="text-secondary small font-monospace" id="checkoutSub">Secure 256-Bit SSL Checkout</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Left: Order Summary & Coupon -->
                    <div class="col-md-5">
                        <div class="p-3 rounded mb-3" style="background: #060b17; border: 1px solid rgba(255,255,255,0.08);">
                            <h6 class="text-warning font-monospace text-uppercase small mb-3">Order Summary</h6>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Selected Plan:</span>
                                <strong class="text-white" id="modalPlanName">Growth Fleet</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Billing Interval:</span>
                                <strong class="text-white text-uppercase" id="modalCycle">Monthly</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Cleaner Limit:</span>
                                <strong class="text-white" id="modalMaxEmp">25 Cleaners</strong>
                            </div>
                            <hr class="border-secondary border-opacity-25 my-2">
                            <div class="d-flex justify-content-between mb-2 small text-secondary">
                                <span>Base Price:</span>
                                <span class="text-white font-monospace" id="modalBasePrice">$79.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-success d-none" id="discountRow">
                                <span>Promo Discount:</span>
                                <span class="font-monospace" id="modalDiscount">-$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top border-secondary border-opacity-25">
                                <span class="fw-bold text-white">Total Due Today:</span>
                                <span class="h4 fw-bold text-warning font-archivo mb-0" id="modalFinalPrice">$79.00</span>
                            </div>
                        </div>

                        <!-- Coupon Code Input -->
                        <div class="p-3 rounded" style="background: #060b17; border: 1px solid rgba(255,255,255,0.08);">
                            <label class="form-label text-secondary small font-monospace"><i class="bi bi-tag-fill text-warning me-1"></i> Coupon Code</label>
                            <div class="input-group">
                                <input type="text" id="couponCodeInput" placeholder="e.g. CLEANWAY20" class="form-control form-control-sm bg-dark text-white border-secondary font-monospace text-uppercase">
                                <button type="button" class="btn btn-sm btn-outline-warning" id="applyCouponBtn">Apply</button>
                            </div>
                            <div class="small font-monospace mt-1" id="couponFeedback"></div>
                        </div>
                    </div>

                    <!-- Right: Dual Payment Options -->
                    <div class="col-md-7">
                        <ul class="nav nav-tabs border-secondary border-opacity-25 mb-3" id="paymentTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active font-monospace text-white small" id="stripe-tab" data-bs-toggle="tab" data-bs-target="#stripePane" type="button">
                                    <i class="bi bi-credit-card-2-front text-primary me-1"></i> Credit Card (Stripe)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link font-monospace text-white small" id="paypal-tab" data-bs-toggle="tab" data-bs-target="#paypalPane" type="button">
                                    <i class="bi bi-paypal text-info me-1"></i> PayPal Express
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="paymentTabContent">
                            <!-- Stripe Card Tab -->
                            <div class="tab-pane fade show active" id="stripePane" role="tabpanel">
                                <form method="POST" action="{{ route('billing.stripe') }}" id="stripeForm">
                                    @csrf
                                    <input type="hidden" name="package_id" id="stripePackageId">
                                    <input type="hidden" name="billing_cycle" id="stripeBillingCycle">
                                    <input type="hidden" name="coupon_code" id="stripeCouponCode">

                                    <div class="mb-3">
                                        <label class="form-label text-secondary small">Cardholder Name</label>
                                        <input type="text" name="card_name" required value="{{ auth()->user()->name }}" class="form-control bg-dark text-white border-secondary">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-secondary small">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" name="card_number" required placeholder="4242 •••• •••• 4242" class="form-control bg-dark text-white border-secondary font-monospace">
                                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-credit-card"></i></span>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label text-secondary small">Expiration (MM/YY)</label>
                                            <input type="text" name="card_expiry" required placeholder="12/28" class="form-control bg-dark text-white border-secondary font-monospace">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-secondary small">CVC Security Code</label>
                                            <input type="password" name="card_cvc" required placeholder="123" maxlength="4" class="form-control bg-dark text-white border-secondary font-monospace">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2">
                                        <i class="bi bi-shield-lock me-1"></i> Pay Now with Stripe
                                    </button>
                                </form>
                            </div>

                            <!-- PayPal Tab -->
                            <div class="tab-pane fade" id="paypalPane" role="tabpanel">
                                <form method="POST" action="{{ route('billing.paypal') }}" id="paypalForm">
                                    @csrf
                                    <input type="hidden" name="package_id" id="paypalPackageId">
                                    <input type="hidden" name="billing_cycle" id="paypalBillingCycle">
                                    <input type="hidden" name="coupon_code" id="paypalCouponCode">

                                    <div class="p-3 mb-3 rounded" style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.2);">
                                        <div class="text-white small fw-bold mb-1"><i class="bi bi-paypal text-info me-1"></i> PayPal Instant Checkout</div>
                                        <p class="text-secondary small mb-0">You will authorize the recurring subscription securely through PayPal Sandbox/Live.</p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label text-secondary small">PayPal Account Email</label>
                                        <input type="email" name="paypal_payer_email" value="{{ auth()->user()->email }}" class="form-control bg-dark text-white border-secondary">
                                    </div>

                                    <button type="submit" class="btn btn-info text-dark fw-bold w-100 py-2">
                                        <i class="bi bi-paypal me-1"></i> Proceed with PayPal
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentCycle = 'monthly';
    let currentPkgId = null;
    let currentBasePrice = 0;
    let appliedCoupon = null;

    // Cycle selector toggle
    $('input[name="cycleSelect"]').on('change', function() {
        currentCycle = $(this).val();
        $('.price-display').each(function() {
            const price = currentCycle === 'yearly' ? $(this).data('yearly') : $(this).data('monthly');
            $(this).text('$' + Math.round(price));
        });
        $('.cycle-label').text(currentCycle === 'yearly' ? '/ year' : '/ month');
    });

    // Open Checkout Modal
    $('.open-checkout-btn').on('click', function() {
        currentPkgId = $(this).data('package-id');
        const pkgName = $(this).data('package-name');
        const monthly = parseFloat($(this).data('monthly'));
        const yearly = parseFloat($(this).data('yearly'));
        const maxEmp = $(this).data('max-emp');

        currentBasePrice = currentCycle === 'yearly' ? yearly : monthly;
        appliedCoupon = null;
        $('#couponCodeInput').val('');
        $('#couponFeedback').text('');
        $('#discountRow').addClass('d-none');

        updateModalPrice();

        $('#modalPlanName').text(pkgName);
        $('#modalCycle').text(currentCycle);
        $('#modalMaxEmp').text(maxEmp ? maxEmp + ' Cleaners' : 'Unlimited');

        $('#stripePackageId, #paypalPackageId').val(currentPkgId);
        $('#stripeBillingCycle, #paypalBillingCycle').val(currentCycle);
        $('#stripeCouponCode, #paypalCouponCode').val('');

        const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        modal.show();
    });

    function updateModalPrice() {
        $('#modalBasePrice').text('$' + currentBasePrice.toFixed(2));
        let finalPrice = currentBasePrice;

        if (appliedCoupon && appliedCoupon.valid) {
            finalPrice = appliedCoupon.final_amount;
            $('#discountRow').removeClass('d-none');
            $('#modalDiscount').text('-$' + appliedCoupon.discount.toFixed(2));
        } else {
            $('#discountRow').addClass('d-none');
        }

        $('#modalFinalPrice').text('$' + finalPrice.toFixed(2));
    }

    // Apply Coupon Code via AJAX
    $('#applyCouponBtn').on('click', function() {
        const code = $('#couponCodeInput').val().trim();
        if (!code) return;

        $('#couponFeedback').text('Validating...').attr('class', 'small font-monospace text-secondary mt-1');

        axios.post('{{ route("billing.validate-coupon") }}', {
            code: code,
            amount: currentBasePrice
        })
        .then(function(res) {
            if (res.data.valid) {
                appliedCoupon = res.data;
                $('#couponFeedback').text(res.data.message).attr('class', 'small font-monospace text-success mt-1');
                $('#stripeCouponCode, #paypalCouponCode').val(code);
                updateModalPrice();
            } else {
                appliedCoupon = null;
                $('#couponFeedback').text(res.data.message).attr('class', 'small font-monospace text-danger mt-1');
                $('#stripeCouponCode, #paypalCouponCode').val('');
                updateModalPrice();
            }
        })
        .catch(function(err) {
            $('#couponFeedback').text('Failed to validate coupon').attr('class', 'small font-monospace text-danger mt-1');
        });
    });
});
</script>
@endpush
