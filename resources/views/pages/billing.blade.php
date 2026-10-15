@extends('layouts.app')
@section('title', 'Subscription & Billing')

@section('content')
<div class="container-fluid billing-page py-3">
    <header class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 reveal">
        <div>
            <span class="eyebrow">Workspace · Billing</span>
            <h1 class="h2 mb-1">Subscription & billing</h1>
            <p class="text-secondary mb-0">{{ $tenant->name }} · USD pricing</p>
        </div>
        @if($tenant->stripe_customer_id)
            <form action="{{ route('billing.stripe.portal') }}" method="POST">
                @csrf
                <button class="btn btn-primary" type="submit">Manage subscription</button>
            </form>
        @endif
    </header>

    @if(session('status'))
        <div class="alert alert-info" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(!$stripeConfigured)
        <div class="alert alert-warning" role="status">Stripe billing is not available yet. Contact your administrator to enable card subscriptions.</div>
    @endif
    @if($tenant->stripe_status === 'past_due' || $tenant->stripe_status === 'unpaid')
        <div class="alert alert-warning" role="status">
            Your renewal needs attention. Update your payment method in Manage subscription.
            @if($tenant->subscription_ends_at)Paid access ends {{ $tenant->subscription_ends_at->format('M j, Y') }} unless payment is recovered.@endif
        </div>
    @endif

    @php($hasStripeSubscription = $tenant->stripe_subscription_id && !in_array($tenant->stripe_status, ['canceled', 'incomplete_expired'], true))
    <section class="row g-3 mb-4" aria-label="Current workspace subscription">
        <div class="col-md-4 reveal" style="--d: 40ms">
            <div class="card h-100">
                <div class="card-header">Current plan</div>
                <div class="card-body">
                    <h2 class="h4">{{ $currentPackage?->name ?? 'Free trial' }}</h2>
                    @if($tenant->isOnTrial())
                        <span class="status-badge status-pending">Trial · {{ $tenant->getRemainingTrialDays() }} days left</span>
                    @else
                        <span class="status-badge {{ $tenant->isSubscriptionActive() ? 'status-active' : 'status-danger' }}">{{ str_replace('_', ' ', $tenant->subscription_status) }}</span>
                    @endif
                    @if($hasStripeSubscription && $tenant->subscription_status === 'trial')
                        <p class="small mt-2 mb-0">Stripe payment confirmation is pending. Your current trial remains available.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 reveal" style="--d: 80ms">
            <div class="card h-100">
                <div class="card-header">Employee capacity</div>
                <div class="card-body">
                    <p class="h3 mb-2">{{ $activeEmployeesCount }} <span class="fs-6 text-secondary">/ {{ $maxEmployees > 0 ? $maxEmployees : 'Unlimited' }}</span></p>
                    <p class="small text-secondary mb-0">Active employees in your workspace</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 reveal" style="--d: 120ms">
            <div class="card h-100">
                <div class="card-header">{{ $tenant->stripe_cancel_at_period_end ? 'Cancellation scheduled' : 'Paid through' }}</div>
                <div class="card-body">
                    <p class="h4 mb-2">{{ $tenant->subscription_ends_at?->format('M j, Y') ?? 'No paid period yet' }}</p>
                    <p class="small text-secondary mb-0">
                        @if($tenant->stripe_cancel_at_period_end)Your subscription ends after the paid period.
                        @elseif($hasStripeSubscription)Your {{ $tenant->billing_cycle }} subscription renews automatically.
                        @elseChoose a monthly or yearly subscription below.@endif
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="card mb-4 reveal" style="--d: 160ms" aria-labelledby="plans-heading">
        <div class="card-header" id="plans-heading">Subscription plans</div>
        <div class="card-body">
            @if($hasStripeSubscription)
                <p class="mb-0">Use Manage subscription to update payment details or cancel renewal. A second subscription cannot be started while your current subscription is live.</p>
            @else
                <p class="text-secondary">Choose a plan that covers your active employee count. Each plan renews automatically until canceled.</p>
                <fieldset class="mb-3">
                    <legend class="form-label">Billing cycle</legend>
                    <div class="btn-group" role="group" aria-label="Billing cycle">
                        <input type="radio" class="btn-check" name="cycleSelect" id="cycleMonthly" value="monthly" checked>
                        <label class="btn btn-outline-secondary" for="cycleMonthly">Monthly</label>
                        <input type="radio" class="btn-check" name="cycleSelect" id="cycleYearly" value="yearly">
                        <label class="btn btn-outline-secondary" for="cycleYearly">Yearly</label>
                    </div>
                </fieldset>
                <div class="row g-3">
                    @forelse($packages as $pkg)
                        <div class="col-lg-4">
                            <article class="card h-100">
                                <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                                    <span>{{ $pkg->name }}</span>
                                    @if($pkg->is_popular)<span class="status-badge status-info">Popular</span>@endif
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="text-secondary">{{ $pkg->description }}</p>
                                    <p class="h3"><span class="price-display" data-monthly="{{ $pkg->price_monthly }}" data-yearly="{{ $pkg->price_yearly }}">${{ number_format($pkg->price_monthly, 2) }}</span><span class="fs-6 text-secondary cycle-label"> / month</span></p>
                                    <p>{{ $pkg->max_employees > 0 ? 'Up to '.$pkg->max_employees.' active employees' : 'Unlimited employees' }}</p>
                                    <ul class="small mb-3">@foreach($pkg->features ?? [] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                                    <button type="button" class="btn btn-primary mt-auto open-checkout-btn" @disabled(!$stripeConfigured)
                                        data-package-id="{{ $pkg->id }}" data-package-name="{{ $pkg->name }}"
                                        data-monthly="{{ $pkg->price_monthly }}" data-yearly="{{ $pkg->price_yearly }}">Choose {{ $pkg->name }}</button>
                                </div>
                            </article>
                        </div>
                    @empty
                        <p class="mb-0">No subscription plans are available. Contact your administrator.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </section>

    <section class="card reveal" style="--d: 200ms" aria-labelledby="payments-heading">
        <div class="card-header" id="payments-heading">Payments & invoices</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th scope="col">Date</th><th scope="col">Plan</th><th scope="col">Provider</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Invoice</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at?->format('M j, Y') }}</td>
                            <td>{{ $payment->metadata['package_name'] ?? $payment->package?->name ?? 'Plan' }}<br><span class="small text-secondary">{{ ucfirst($payment->billing_cycle) }}</span></td>
                            <td>{{ ucfirst($payment->payment_gateway) }}</td>
                            <td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td><span class="status-badge {{ $payment->status === 'completed' ? 'status-active' : 'status-pending' }}">{{ $payment->status }}</span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('billing.invoices.show', $payment) }}" target="_blank" rel="noopener">View</a>
                                    <form method="POST" action="{{ route('billing.invoices.send', $payment) }}">@csrf<button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Email invoice {{ $payment->getInvoiceNumber() }}">Email</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4">No payments yet. Confirmed Stripe invoices will appear here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())<div class="card-footer">{{ $payments->links() }}</div>@endif
    </section>

    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="checkoutTitle">Start subscription</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close checkout"></button>
                </div>
                <form method="POST" action="{{ route('billing.stripe') }}" id="stripeForm">
                    @csrf
                    <input type="hidden" name="package_id" id="stripePackageId">
                    <input type="hidden" name="billing_cycle" id="stripeBillingCycle">
                    <div class="modal-body">
                        <p class="eyebrow" id="modalPlanName"></p>
                        <dl class="row">
                            <dt class="col-7">Recurring price</dt><dd class="col-5 text-end" id="modalBasePrice"></dd>
                            <dt class="col-7">First invoice discount</dt><dd class="col-5 text-end" id="modalDiscount">USD 0.00</dd>
                            <dt class="col-7">Due today</dt><dd class="col-5 text-end fw-bold" id="modalFinalPrice"></dd>
                        </dl>
                        <div class="mb-3">
                            <label class="form-label" for="couponCodeInput">Coupon code (optional)</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" id="couponCodeInput" class="form-control text-uppercase" maxlength="50" aria-describedby="couponFeedback">
                                <button type="button" class="btn btn-outline-secondary" id="applyCouponBtn">Apply</button>
                            </div>
                            <p class="small mt-2 mb-0" id="couponFeedback" role="status" aria-live="polite"></p>
                        </div>
                        <p class="small mb-2" id="stripeRenewalTerms"></p>
                        <p class="small text-secondary mb-0">Discounts apply to the first invoice only. Enter payment details securely on Stripe. Your plan activates after payment confirmation.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100" id="stripeSubmit" data-ready="{{ $stripeConfigured ? '1' : '0' }}" @disabled(!$stripeConfigured)>Continue to Stripe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function ($) {
    let cycle = 'monthly', packageId = null, basePrice = 0, requestVersion = 0;
    const $submit = $('#stripeSubmit');
    function summary(discount, total) {
        $('#modalBasePrice').text('USD ' + basePrice.toFixed(2));
        $('#modalDiscount').text('USD ' + discount.toFixed(2));
        $('#modalFinalPrice').text('USD ' + total.toFixed(2));
        $('#stripeRenewalTerms').text('Renews automatically at USD ' + basePrice.toFixed(2) + ' per ' + (cycle === 'yearly' ? 'year' : 'month') + ' until canceled. Cancel in Manage subscription before your next renewal.');
    }
    function resetSubmit() {
        $submit.prop('disabled', $submit.attr('data-ready') !== '1').text('Continue to Stripe');
        $('#stripeForm').attr('aria-busy', 'false');
    }
    $(window).on('pageshow', resetSubmit);
    $('input[name="cycleSelect"]').on('change', function () {
        cycle = $(this).val();
        $('.price-display').each(function () { $(this).text('$' + Number($(this).data(cycle)).toFixed(2)); });
        $('.cycle-label').text(cycle === 'yearly' ? ' / year' : ' / month');
    });
    $('.open-checkout-btn').on('click', function () {
        requestVersion++;
        packageId = $(this).data('package-id');
        basePrice = Number($(this).data(cycle));
        $('#stripePackageId').val(packageId);
        $('#stripeBillingCycle').val(cycle);
        $('#modalPlanName').text($(this).data('package-name'));
        $('#couponCodeInput').val('');
        $('#couponFeedback').text('');
        summary(0, basePrice);
        resetSubmit();
        bootstrap.Modal.getOrCreateInstance($('#checkoutModal')[0]).show();
    });
    $('#couponCodeInput').on('input', function () {
        requestVersion++;
        $('#couponFeedback').text('');
        summary(0, basePrice);
        resetSubmit();
    });
    $('#applyCouponBtn').on('click', function () {
        const code = $('#couponCodeInput').val().trim();
        if (!code) return;
        const version = ++requestVersion;
        $('#couponFeedback').text('Checking coupon…');
        $submit.prop('disabled', true);
        axios.post(@json(route('billing.validate-coupon')), {code: code, package_id: packageId, billing_cycle: cycle})
            .then(function (response) {
                if (version !== requestVersion) return;
                $('#couponFeedback').text(response.data.message);
                summary(response.data.valid ? Number(response.data.discount) : 0, response.data.valid ? Number(response.data.final_amount) : basePrice);
            })
            .catch(function () { if (version === requestVersion) $('#couponFeedback').text('Could not check the coupon. Please try again.'); })
            .finally(function () { if (version === requestVersion) resetSubmit(); });
    });
    $('#stripeForm').on('submit', function () {
        $(this).attr('aria-busy', 'true');
        $submit.prop('disabled', true).text('Opening Stripe…');
    });
});
</script>
@endpush