@extends('layouts.app')

@section('title', 'Tenant Manager & SaaS Platform')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Eyebrow -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-shield-lock me-1"></i> Platform SaaS · Multi-Tenant Administration</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Tenant Manager</h1>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning fw-bold text-dark font-monospace" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="bi bi-building-add me-1"></i> New Tenant Workspace
            </button>
            <button type="button" class="btn btn-outline-secondary font-monospace" data-bs-toggle="modal" data-bs-target="#createPackageModal">
                <i class="bi bi-box-seam me-1"></i> New Package
            </button>
            <button type="button" class="btn btn-outline-secondary font-monospace" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                <i class="bi bi-tag me-1"></i> New Coupon
            </button>
        </div>
    </div>

    <!-- Platform Stats 4-Up -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid var(--cw-primary, #0284C7) !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Total Organizations</span>
                <div class="h3 fw-bold mb-0 mt-1 font-archivo" style="color: var(--cw-text-strong, #0F172A);">{{ $stats['total_tenants'] }}</div>
                <span class="text-success small font-monospace"><i class="bi bi-check-circle me-1"></i>{{ $stats['active_tenants'] }} active workspaces</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #3b82f6 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Free Trial Workspaces</span>
                <div class="h3 fw-bold mb-0 mt-1 font-archivo" style="color: var(--cw-text-strong, #0F172A);">{{ $stats['trial_tenants'] }}</div>
                <span class="text-info small font-monospace"><i class="bi bi-clock-history me-1"></i>In 14/30-day evaluation</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #10b981 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Total Field Cleaners</span>
                <div class="h3 fw-bold mb-0 mt-1 font-archivo" style="color: var(--cw-text-strong, #0F172A);">{{ $stats['total_employees'] }}</div>
                <span class="text-success small font-monospace"><i class="bi bi-people-fill me-1"></i>Across all tenant fleets</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #eab308 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Default Free Trial</span>
                <div class="h3 fw-bold mb-0 mt-1 font-archivo" style="color: var(--cw-text-strong, #0F172A);">{{ $platformSettings['default_trial_days'] }} Days</div>
                <span class="text-warning small font-monospace"><i class="bi bi-sliders me-1"></i>Global trial setting</span>
            </div>
        </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="tenantManagerTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link font-monospace text-uppercase {{ $currentTab === 'tenants' ? 'active' : '' }}" href="?tab=tenants">
                <i class="bi bi-buildings me-1"></i> Tenants ({{ $tenants->total() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-monospace text-uppercase {{ $currentTab === 'packages' ? 'active' : '' }}" href="?tab=packages">
                <i class="bi bi-box-seam me-1"></i> Packages & Plans ({{ $packages->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-monospace text-uppercase {{ $currentTab === 'coupons' ? 'active' : '' }}" href="?tab=coupons">
                <i class="bi bi-tag me-1"></i> Promo Coupons ({{ $coupons->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-monospace text-uppercase {{ $currentTab === 'queries' ? 'active' : '' }}" href="?tab=queries">
                <i class="bi bi-chat-left-dots me-1"></i> Tenant Queries ({{ $queries->total() }})
                @if($queryStats['tenant_responded'] > 0)
                    <span class="badge bg-danger ms-1">{{ $queryStats['tenant_responded'] }} new</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-monospace text-uppercase {{ $currentTab === 'alerts' ? 'active' : '' }}" href="?tab=alerts">
                <i class="bi bi-bell me-1"></i> Alerts & Invoices
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-monospace text-uppercase {{ $currentTab === 'settings' ? 'active' : '' }}" href="?tab=settings">
                <i class="bi bi-sliders me-1"></i> Global Settings & Sync
            </a>
        </li>
    </ul>

    <!-- Tab 1: Tenants List -->
    @if($currentTab === 'tenants')
        <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-10">
                <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">All Tenant Workspaces</h5>
                <form method="GET" action="{{ route('tenant-manager.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="tenants">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name or slug..." class="form-control form-control-sm bg-light text-dark border-secondary border-opacity-25">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
                    @if(request('q'))
                        <a href="{{ route('tenant-manager.index', ['tab' => 'tenants']) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="font-monospace text-secondary small text-uppercase bg-light">
                        <tr>
                            <th>Tenant / Workspace</th>
                            <th>Dedicated URL</th>
                            <th>Package</th>
                            <th>Active Personnel / Quota</th>
                            <th>Status & Trial</th>
                            <th>Google Sync</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $t)
                            @php
                                $maxEmp = $t->getMaxEmployees();
                                $activeEmp = $t->employees_count;
                                $quotaPct = $maxEmp > 0 ? min(100, round(($activeEmp / $maxEmp) * 100)) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $t->name }}</div>
                                    <div class="text-secondary small font-monospace">Created {{ $t->created_at?->format('M j, Y') }}</div>
                                </td>
                                <td>
                                    <a href="/{{ $t->slug }}" target="_blank" class="font-monospace text-primary fw-semibold text-decoration-none">
                                        /{{ $t->slug }} <i class="bi bi-box-arrow-up-right small"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">
                                        {{ $t->package?->name ?? 'No Plan' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="font-monospace small fw-bold {{ $activeEmp >= $maxEmp && $maxEmp > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $activeEmp }} / {{ $maxEmp ?: '∞' }}
                                        </span>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px; width: 110px; background: rgba(0,0,0,0.08);">
                                        <div class="progress-bar {{ $quotaPct >= 90 ? 'bg-danger' : ($quotaPct >= 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $quotaPct }}%;"></div>
                                    </div>
                                </td>
                                <td>
                                    @if($t->status === 'inactive')
                                        <span class="badge bg-danger-subtle text-danger font-monospace">SUSPENDED</span>
                                    @elseif($t->isOnTrial())
                                        <span class="badge bg-warning-subtle text-warning font-monospace">
                                            TRIAL ({{ $t->getRemainingTrialDays() }}d left)
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success font-monospace">
                                            ACTIVE {{ strtoupper($t->subscription_status) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 font-monospace small">
                                        <span class="badge {{ $t->google_calendar_sync_enabled ? 'bg-success' : 'bg-secondary' }}" title="Google Calendar">
                                            <i class="bi bi-calendar"></i>
                                        </span>
                                        <span class="badge {{ $t->google_sheet_sync_enabled ? 'bg-success' : 'bg-secondary' }}" title="Google Sheets">
                                            <i class="bi bi-file-spreadsheet"></i>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editTenantModal{{ $t->id }}">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <form method="POST" action="{{ route('tenant-manager.tenants.toggle-status', $t) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $t->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}" onclick="return confirm('Change status for {{ $t->name }}?')">
                                                {{ $t->status === 'active' ? 'Suspend' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Edit Tenant Modal -->
                                    <div class="modal fade text-start" id="editTenantModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
                                                <form method="POST" action="{{ route('tenant-manager.tenants.update', $t) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header border-secondary border-opacity-25">
                                                        <h5 class="modal-title text-white font-archivo">Edit Tenant: {{ $t->name }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label text-secondary small">Organization Name</label>
                                                            <input type="text" name="name" value="{{ $t->name }}" required class="form-control bg-dark text-white border-secondary">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label text-secondary small">Package / Subscription Tier</label>
                                                            <select name="package_id" class="form-select bg-dark text-white border-secondary">
                                                                @foreach($packages as $p)
                                                                    <option value="{{ $p->id }}" {{ $t->package_id == $p->id ? 'selected' : '' }}>
                                                                        {{ $p->name }} (${{ $p->price_monthly }}/mo, {{ $p->max_employees }} cleaners)
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label text-secondary small">Subscription Status</label>
                                                            <select name="subscription_status" class="form-select bg-dark text-white border-secondary">
                                                                <option value="trial" {{ $t->subscription_status === 'trial' ? 'selected' : '' }}>Trial</option>
                                                                <option value="active" {{ $t->subscription_status === 'active' ? 'selected' : '' }}>Active Paid</option>
                                                                <option value="past_due" {{ $t->subscription_status === 'past_due' ? 'selected' : '' }}>Past Due</option>
                                                                <option value="canceled" {{ $t->subscription_status === 'canceled' ? 'selected' : '' }}>Canceled</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label text-secondary small">Extend Trial Days (+Days)</label>
                                                            <input type="number" name="trial_days_add" placeholder="e.g. 14" min="0" class="form-control bg-dark text-white border-secondary">
                                                            <span class="text-secondary small">Current trial ends: {{ $t->trial_ends_at?->format('M j, Y') ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label text-secondary small">Employee Limit Override (Leave blank to use package default)</label>
                                                            <input type="number" name="max_employees_override" value="{{ $t->max_employees_override }}" min="0" placeholder="e.g. 50" class="form-control bg-dark text-white border-secondary">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label text-secondary small">Account State</label>
                                                            <select name="status" class="form-select bg-dark text-white border-secondary">
                                                                <option value="active" {{ $t->status === 'active' ? 'selected' : '' }}>Active</option>
                                                                <option value="inactive" {{ $t->status === 'inactive' ? 'selected' : '' }}>Suspended</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary border-opacity-25">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning text-dark fw-bold">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent py-3">
                {{ $tenants->links() }}
            </div>
        </div>
    @endif

    <!-- Tab 2: Packages -->
    @if($currentTab === 'packages')
        <div class="row g-4 mb-4">
            @foreach($packages as $pkg)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm p-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-top: 4px solid {{ $pkg->is_popular ? 'var(--cw-primary, #0284C7)' : '#3b82f6' }} !important; border-radius: var(--cw-radius-md, 8px);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">{{ $pkg->name }}</h4>
                            @if($pkg->is_popular)
                                <span class="badge bg-primary font-monospace">POPULAR</span>
                            @endif
                        </div>
                        <p class="text-secondary small mb-3">{{ $pkg->description }}</p>

                        <div class="h2 font-archivo fw-bold mb-1" style="color: var(--cw-text-strong, #0F172A);">
                            ${{ number_format($pkg->price_monthly, 2) }} <span class="fs-6 text-secondary font-monospace fw-normal">/ mo</span>
                        </div>
                        <div class="text-secondary small font-monospace mb-3">
                            ${{ number_format($pkg->price_yearly, 2) }} / year
                        </div>

                        <div class="p-2 rounded mb-3 font-monospace small" style="background: var(--cw-surface-2, #F1F5F9); border: 1px solid var(--cw-border, #CBD5E1); color: var(--cw-text-strong, #0F172A);">
                            <i class="bi bi-people-fill text-warning me-1"></i> Max Employees: <strong>{{ $pkg->max_employees ?: 'Unlimited' }}</strong>
                            &bull; Trial: <strong>{{ $pkg->trial_days }} Days</strong>
                        </div>

                        <ul class="list-unstyled small text-secondary mb-4 flex-grow-1">
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Google Calendar Feed: {{ $pkg->has_google_calendar ? 'Yes' : 'No' }}</li>
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Google Sheets Live Sync: {{ $pkg->has_google_sheets ? 'Yes' : 'No' }}</li>
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Depot QR Scan Kiosk: {{ $pkg->has_qr_login ? 'Yes' : 'No' }}</li>
                            @if(is_array($pkg->features))
                                @foreach($pkg->features as $f)
                                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> {{ $f }}</li>
                                @endforeach
                            @endif
                        </ul>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-10">
                            <button type="button" class="btn btn-outline-secondary btn-sm font-monospace" data-bs-toggle="modal" data-bs-target="#editPackageModal{{ $pkg->id }}">
                                <i class="bi bi-pencil me-1"></i> Edit Plan
                            </button>
                            <form method="POST" action="{{ route('tenant-manager.packages.destroy', $pkg) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete package {{ $pkg->name }}?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Edit Package Modal -->
                    <div class="modal fade" id="editPackageModal{{ $pkg->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
                                <form method="POST" action="{{ route('tenant-manager.packages.update', $pkg) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-secondary border-opacity-25">
                                        <h5 class="modal-title text-white font-archivo">Edit Package: {{ $pkg->name }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small">Package Name</label>
                                            <input type="text" name="name" value="{{ $pkg->name }}" required class="form-control bg-dark text-white border-secondary">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small">Description</label>
                                            <textarea name="description" rows="2" class="form-control bg-dark text-white border-secondary">{{ $pkg->description }}</textarea>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary small">Monthly Price ($)</label>
                                                <input type="number" step="0.01" name="price_monthly" value="{{ $pkg->price_monthly }}" required class="form-control bg-dark text-white border-secondary">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary small">Yearly Price ($)</label>
                                                <input type="number" step="0.01" name="price_yearly" value="{{ $pkg->price_yearly }}" required class="form-control bg-dark text-white border-secondary">
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary small">Max Employees Quota</label>
                                                <input type="number" name="max_employees" value="{{ $pkg->max_employees }}" required min="0" class="form-control bg-dark text-white border-secondary">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary small">Trial Days</label>
                                                <input type="number" name="trial_days" value="{{ $pkg->trial_days }}" required min="0" class="form-control bg-dark text-white border-secondary">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="has_google_calendar" value="1" id="cal{{ $pkg->id }}" {{ $pkg->has_google_calendar ? 'checked' : '' }}>
                                                <label class="form-check-label text-white small" for="cal{{ $pkg->id }}">Google Calendar Sync Enabled</label>
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="has_google_sheets" value="1" id="sheet{{ $pkg->id }}" {{ $pkg->has_google_sheets ? 'checked' : '' }}>
                                                <label class="form-check-label text-white small" for="sheet{{ $pkg->id }}">Google Sheets Live Sync Enabled</label>
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="has_qr_login" value="1" id="qr{{ $pkg->id }}" {{ $pkg->has_qr_login ? 'checked' : '' }}>
                                                <label class="form-check-label text-white small" for="qr{{ $pkg->id }}">Depot QR Scan Kiosks</label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_popular" value="1" id="pop{{ $pkg->id }}" {{ $pkg->is_popular ? 'checked' : '' }}>
                                                <label class="form-check-label text-white small" for="pop{{ $pkg->id }}">Mark as Popular / Featured</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary border-opacity-25">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning text-dark fw-bold">Update Package</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Tab 3: Coupons -->
    @if($currentTab === 'coupons')
        <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-10">
                <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Promotion & Discount Coupons</h5>
                <button type="button" class="btn btn-sm btn-primary font-monospace fw-bold" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Coupon Code
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="font-monospace text-secondary small text-uppercase bg-light">
                        <tr>
                            <th>Coupon Code</th>
                            <th>Campaign Name</th>
                            <th>Discount</th>
                            <th>Redemptions</th>
                            <th>Expiration</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $c)
                            <tr>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning font-monospace fs-6 px-2 py-1">
                                        {{ $c->code }}
                                    </span>
                                </td>
                                <td>{{ $c->name }}</td>
                                <td>
                                    <span class="fw-bold font-monospace text-dark">
                                        {{ $c->discount_type === 'percentage' ? $c->discount_value . '%' : '$' . number_format($c->discount_value, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary small">
                                        {{ $c->redeemed_count }} / {{ $c->max_redemptions ?: '∞' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary small">
                                        {{ $c->expires_at ? $c->expires_at->format('M j, Y') : 'No Expiry' }}
                                    </span>
                                </td>
                                <td>
                                    @if($c->isValid())
                                        <span class="badge bg-success-subtle text-success font-monospace">ACTIVE</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger font-monospace">INACTIVE</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('tenant-manager.coupons.toggle', $c) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            {{ $c->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('tenant-manager.coupons.destroy', $c) }}" class="d-inline ms-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete coupon {{ $c->code }}?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">No discount coupons created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Tab: Tenant Queries & Support Hub -->
    @if($currentTab === 'queries')
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #ef4444 !important; border-radius: var(--cw-radius-md, 8px);">
                    <span class="text-secondary font-monospace small text-uppercase">Tenant Responded</span>
                    <div class="h3 fw-bold font-archivo text-danger mb-0">{{ $queryStats['tenant_responded'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #eab308 !important; border-radius: var(--cw-radius-md, 8px);">
                    <span class="text-secondary font-monospace small text-uppercase">Waiting Tenant</span>
                    <div class="h3 fw-bold font-archivo text-warning mb-0">{{ $queryStats['waiting_tenant'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #10b981 !important; border-radius: var(--cw-radius-md, 8px);">
                    <span class="text-secondary font-monospace small text-uppercase">Resolved Inquiries</span>
                    <div class="h3 fw-bold font-archivo text-success mb-0">{{ $queryStats['resolved'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #0284c7 !important; border-radius: var(--cw-radius-md, 8px);">
                    <span class="text-secondary font-monospace small text-uppercase">Total Inquiries</span>
                    <div class="h3 fw-bold font-archivo mb-0">{{ $queryStats['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-10">
                <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-chat-left-dots me-1"></i> Cross-Tenant Support Queries</h5>
                <button type="button" class="btn btn-sm btn-primary font-monospace" data-bs-toggle="modal" data-bs-target="#newSuperAdminQueryModal">
                    <i class="bi bi-envelope-plus me-1"></i> Open Query to Tenant
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="font-monospace text-secondary small text-uppercase bg-light">
                        <tr>
                            <th>Ref #</th>
                            <th>Tenant Workspace</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Last Message</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($queries as $q)
                            <tr>
                                <td><strong class="font-monospace text-primary">#{{ $q->id }}</strong></td>
                                <td>
                                    <strong>{{ $q->tenant?->name ?? 'Unknown' }}</strong>
                                    <div class="small text-secondary font-monospace">/{{ $q->tenant?->slug }}</div>
                                </td>
                                <td>
                                    <span class="fw-bold font-archivo text-dark d-block">{{ $q->subject }}</span>
                                    <span class="small text-muted font-monospace">{{ Str::limit($q->latestMessage?->message ?? '', 60) }}</span>
                                </td>
                                <td><span class="badge bg-secondary font-monospace text-uppercase">{{ $q->category }}</span></td>
                                <td>
                                    @if($q->priority === 'urgent')
                                        <span class="badge bg-danger text-white font-monospace">URGENT</span>
                                    @elseif($q->priority === 'high')
                                        <span class="badge bg-warning text-dark font-monospace">HIGH</span>
                                    @else
                                        <span class="badge bg-secondary font-monospace text-uppercase">{{ $q->priority }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($q->status === 'tenant_responded')
                                        <span class="badge bg-danger text-white font-monospace">TENANT RESPONDED</span>
                                    @elseif($q->status === 'waiting_tenant')
                                        <span class="badge bg-warning text-dark font-monospace">WAITING TENANT</span>
                                    @elseif($q->status === 'resolved')
                                        <span class="badge bg-success text-white font-monospace">RESOLVED</span>
                                    @else
                                        <span class="badge bg-secondary font-monospace text-uppercase">{{ $q->status }}</span>
                                    @endif
                                </td>
                                <td class="font-monospace small text-secondary">
                                    {{ $q->last_message_at ? $q->last_message_at->diffForHumans() : $q->created_at->diffForHumans() }}
                                </td>
                                <td class="text-end">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-secondary font-monospace dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end font-monospace small">
                                            <li>
                                                <form action="{{ route('tenant-manager.queries.status', $q->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <button type="submit" class="dropdown-item">Mark In Progress</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('tenant-manager.queries.status', $q->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="waiting_tenant">
                                                    <button type="submit" class="dropdown-item">Set Waiting Tenant</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('tenant-manager.queries.status', $q->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="resolved">
                                                    <button type="submit" class="dropdown-item text-success">Mark Resolved</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('tenant-manager.queries.status', $q->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="closed">
                                                    <button type="submit" class="dropdown-item text-secondary">Close Inquiry</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="{{ route('tenant.queries.show', $q->id) }}" class="btn btn-sm btn-primary font-monospace ms-1">
                                        Reply / View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary">No tenant inquiries recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($queries->hasPages())
                <div class="card-footer bg-transparent py-3">
                    {{ $queries->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Tab: Automated Alerts & Invoices -->
    @if($currentTab === 'alerts')
        <div class="row g-4 mb-4">
            <!-- Left: Alerts Dispatch & Management -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-10">
                        <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-bell-fill text-warning me-1"></i> Dispatched Tenant Alerts</h5>
                        <div class="d-flex gap-2">
                            <form action="{{ route('tenant-manager.alerts.scan') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary font-monospace">
                                    <i class="bi bi-lightning-charge me-1"></i> Run Auto-Scan
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-warning font-monospace" data-bs-toggle="modal" data-bs-target="#dispatchAlertModal">
                                <i class="bi bi-send-plus me-1"></i> Dispatch Alert
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="font-monospace text-secondary small text-uppercase bg-light">
                                <tr>
                                    <th>Tenant</th>
                                    <th>Alert Title</th>
                                    <th>Type / Severity</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alerts as $al)
                                    <tr>
                                        <td>
                                            <strong>{{ $al->tenant?->name ?? 'Unknown' }}</strong>
                                        </td>
                                        <td>
                                            <div class="fw-bold font-archivo">{{ $al->title }}</div>
                                            <div class="small text-secondary font-monospace">{{ Str::limit($al->message, 50) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $al->severity === 'danger' ? 'bg-danger' : ($al->severity === 'warning' ? 'bg-warning text-dark' : 'bg-info') }} font-monospace text-uppercase">
                                                {{ $al->severity }}
                                            </span>
                                            <div class="small text-muted font-monospace">{{ $al->alert_type }}</div>
                                        </td>
                                        <td class="font-monospace small text-secondary">
                                            {{ $al->created_at->format('M j, Y') }}
                                        </td>
                                        <td>
                                            @if($al->is_read)
                                                <span class="badge bg-success-subtle text-success font-monospace">READ</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger font-monospace">UNREAD</span>
                                            @endif
                                            @if($al->email_sent)
                                                <div class="text-success small font-monospace"><i class="bi bi-envelope-check"></i> EMAILED</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">No alerts dispatched yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($alerts->hasPages())
                        <div class="card-footer bg-transparent py-3">
                            {{ $alerts->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right: Cross-Tenant Payments & Invoices -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10">
                        <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-receipt me-1"></i> Tenant Payments & Generated Invoices</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="font-monospace text-secondary small text-uppercase bg-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Tenant</th>
                                    <th>Amount</th>
                                    <th>Gateway</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $p)
                                    <tr>
                                        <td>
                                            <strong class="font-monospace text-primary">{{ $p->getInvoiceNumber() }}</strong>
                                        </td>
                                        <td>{{ $p->tenant?->name ?? 'Unknown' }}</td>
                                        <td class="fw-bold font-monospace text-success">${{ number_format($p->amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark font-monospace text-uppercase">{{ $p->payment_gateway }}</span>
                                        </td>
                                        <td class="font-monospace small text-secondary">
                                            {{ $p->created_at?->format('M j, Y') }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('billing.invoices.show', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-primary font-monospace py-0 px-2">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>
                                            <form action="{{ route('tenant-manager.invoices.resend', $p->id) }}" method="POST" class="d-inline ms-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary font-monospace py-0 px-2" title="Resend invoice email to tenant">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-secondary">No billing payments found.</td>
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
        </div>
    @endif

    <!-- Tab 4: Platform Settings & Sync Options -->
    @if($currentTab === 'settings')
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <h5 class="font-archivo fw-bold mb-4" style="color: var(--cw-text-strong, #0F172A);">Platform SaaS & Gateway Settings</h5>
                    <form method="POST" action="{{ route('tenant-manager.settings.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- 1. Free Trial Settings -->
                        <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                            <h6 class="text-dark font-monospace text-uppercase small mb-2 fw-bold"><i class="bi bi-clock-history text-warning me-1"></i> Free Trial Policy</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small">Default Free Trial Duration (Days)</label>
                                    <input type="number" name="default_trial_days" value="{{ $platformSettings['default_trial_days'] }}" required min="0" max="180" class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace">
                                    <span class="text-secondary small">Applied automatically to new organizations on self-signup.</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Stripe Gateway -->
                        <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-dark font-monospace text-uppercase small mb-0 fw-bold"><i class="bi bi-stripe text-primary me-1"></i> Stripe Payment Gateway</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="stripe_enabled" value="1" id="stripeToggle" {{ $platformSettings['stripe_enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label text-body small fw-medium" for="stripeToggle">Enabled</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small">Stripe Mode</label>
                                    <select name="stripe_mode" class="form-select bg-light text-dark border-secondary border-opacity-25">
                                        <option value="test" {{ $platformSettings['stripe_mode'] === 'test' ? 'selected' : '' }}>Test (Sandbox)</option>
                                        <option value="live" {{ $platformSettings['stripe_mode'] === 'live' ? 'selected' : '' }}>Live (Production)</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label text-secondary small">Stripe Publishable Key</label>
                                    <input type="text" name="stripe_publishable_key" value="{{ $platformSettings['stripe_publishable_key'] }}" placeholder="pk_test_..." class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace">
                                </div>
                            </div>
                        </div>

                        <!-- 3. PayPal Gateway -->
                        <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-dark font-monospace text-uppercase small mb-0 fw-bold"><i class="bi bi-paypal text-info me-1"></i> PayPal Payment Gateway</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="paypal_enabled" value="1" id="paypalToggle" {{ $platformSettings['paypal_enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label text-body small fw-medium" for="paypalToggle">Enabled</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small">PayPal Mode</label>
                                    <select name="paypal_mode" class="form-select bg-light text-dark border-secondary border-opacity-25">
                                        <option value="sandbox" {{ $platformSettings['paypal_mode'] === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                        <option value="live" {{ $platformSettings['paypal_mode'] === 'live' ? 'selected' : '' }}>Live</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label text-secondary small">PayPal Client ID</label>
                                    <input type="text" name="paypal_client_id" value="{{ $platformSettings['paypal_client_id'] }}" placeholder="sb_client_id_..." class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace">
                                </div>
                            </div>
                        </div>

                        <!-- 4. Google Automations -->
                        <div class="mb-4">
                            <h6 class="text-dark font-monospace text-uppercase small mb-2 fw-bold"><i class="bi bi-google text-danger me-1"></i> Google Sync Automation</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="google_calendar_auto_sync" value="1" id="calAutoSync" {{ $platformSettings['google_calendar_auto_sync'] ? 'checked' : '' }}>
                                <label class="form-check-label text-body small fw-medium" for="calAutoSync">Enable Automated Google Calendar Sync by default</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="google_sheet_auto_sync" value="1" id="sheetAutoSync" {{ $platformSettings['google_sheet_auto_sync'] ? 'checked' : '' }}>
                                <label class="form-check-label text-body small fw-medium" for="sheetAutoSync">Enable Automated Google Sheets Live Sync by default</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Platform Configurations
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                    <h6 class="font-archivo fw-bold mb-3" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-info-circle me-1 text-primary"></i> Architecture Notes</h6>
                    <p class="text-secondary small">
                        <strong>Multi-Tenant Isolation:</strong> ScrubRoster assigns every tenant an isolated global scope. Cleaners can belong to multiple tenants using the same email address without conflicting accounts.
                    </p>
                    <p class="text-secondary small">
                        <strong>Billing Flow:</strong> Tenant admins upgrade directly through Stripe or PayPal with coupon codes. Quotas cap active cleaner count across tasks, shifts, and mobile access.
                    </p>
                    <p class="text-secondary small">
                        <strong>Depot Kiosks:</strong> Cleaners use one-tap QR badges generated via the Personnel roster. Single-use tokens expire in 15 minutes for security.
                    </p>
                </div>
            </div>
        </div>
    @endif

</div>

<!-- Create Tenant Modal -->
<div class="modal fade" id="createTenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
            <form method="POST" action="{{ route('tenant-manager.tenants.store') }}">
                @csrf
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title text-white font-archivo">Create New Tenant Organization</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Company / Organization Name</label>
                        <input type="text" name="name" required placeholder="e.g. Apex Facility Services" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Workspace Slug (Optional)</label>
                        <input type="text" name="slug" placeholder="e.g. apex-services" class="form-control bg-dark text-white border-secondary font-monospace">
                        <span class="text-secondary small">Creates URL: <code>cleanway.test/{slug}</code></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Subscription Plan</label>
                        <select name="package_id" class="form-select bg-dark text-white border-secondary">
                            @foreach($packages as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (${{ $p->price_monthly }}/mo, {{ $p->max_employees }} cleaners)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Trial Period (Days)</label>
                        <input type="number" name="trial_days" value="{{ $platformSettings['default_trial_days'] }}" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <hr class="border-secondary border-opacity-25">
                    <h6 class="text-warning font-monospace text-uppercase small mb-2">Initial Tenant Administrator</h6>
                    <div class="mb-2">
                        <label class="form-label text-secondary small">Admin Name</label>
                        <input type="text" name="admin_name" required placeholder="John Doe" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-secondary small">Admin Email</label>
                        <input type="email" name="admin_email" required placeholder="admin@apex.com" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Admin Password</label>
                        <input type="password" name="admin_password" required minlength="8" class="form-control bg-dark text-white border-secondary">
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">Provision Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Package Modal -->
<div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
            <form method="POST" action="{{ route('tenant-manager.packages.store') }}">
                @csrf
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title text-white font-archivo">Create Subscription Package</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Package Name</label>
                        <input type="text" name="name" required placeholder="e.g. Enterprise Tier" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Description</label>
                        <textarea name="description" rows="2" class="form-control bg-dark text-white border-secondary" placeholder="Brief plan summary..."></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small">Monthly Price ($)</label>
                            <input type="number" step="0.01" name="price_monthly" required placeholder="49.00" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">Yearly Price ($)</label>
                            <input type="number" step="0.01" name="price_yearly" required placeholder="490.00" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small">Max Employees Quota</label>
                            <input type="number" name="max_employees" required placeholder="15" min="0" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">Free Trial Days</label>
                            <input type="number" name="trial_days" value="14" required min="0" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Bullet Features (One per line)</label>
                        <textarea name="features" rows="3" placeholder="Up to 15 cleaners&#10;Google Calendar sync&#10;Live Sheets export" class="form-control bg-dark text-white border-secondary"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="has_google_calendar" value="1" id="newCal" checked>
                            <label class="form-check-label text-white small" for="newCal">Google Calendar Sync</label>
                        </div>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="has_google_sheets" value="1" id="newSheet" checked>
                            <label class="form-check-label text-white small" for="newSheet">Google Sheets Live Sync</label>
                        </div>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="has_qr_login" value="1" id="newQr" checked>
                            <label class="form-check-label text-white small" for="newQr">Depot QR Scanner Kiosk</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_popular" value="1" id="newPop">
                            <label class="form-check-label text-white small" for="newPop">Mark as Popular</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">Create Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Coupon Modal -->
<div class="modal fade" id="createCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
            <form method="POST" action="{{ route('tenant-manager.coupons.store') }}">
                @csrf
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title text-white font-archivo">Create Promo Coupon</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Coupon Code</label>
                        <input type="text" name="code" required placeholder="e.g. SUMMER25" class="form-control bg-dark text-white border-secondary font-monospace text-uppercase">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Campaign Name</label>
                        <input type="text" name="name" required placeholder="e.g. Summer Special 25%" class="form-control bg-dark text-white border-secondary">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small">Discount Type</label>
                            <select name="discount_type" class="form-select bg-dark text-white border-secondary">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">Discount Value</label>
                            <input type="number" step="0.01" name="discount_value" required placeholder="25" min="0.01" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small">Max Redemptions</label>
                            <input type="number" name="max_redemptions" placeholder="Unlimited" min="1" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">Expiration Date</label>
                            <input type="date" name="expires_at" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Open Query to Tenant (Super Admin) -->
<div class="modal fade" id="newSuperAdminQueryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('tenant-manager.queries.store') }}" class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
            @csrf
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title text-white font-archivo">Open Administrative Inquiry to Tenant</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-secondary small">Target Workspace Tenant</label>
                    <select name="tenant_id" class="form-select bg-dark text-white border-secondary font-monospace" required>
                        <option value="">Select Tenant Organization...</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} (/{{ $t->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small">Subject / Focus</label>
                    <input type="text" name="subject" required placeholder="e.g. Action Required: Verification of Subscription Quota" class="form-control bg-dark text-white border-secondary">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-secondary small">Category</label>
                        <select name="category" class="form-select bg-dark text-white border-secondary font-monospace">
                            <option value="compliance">Compliance</option>
                            <option value="billing">Billing & Limits</option>
                            <option value="technical">Technical Notice</option>
                            <option value="general">General Support</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-secondary small">Priority</label>
                        <select name="priority" class="form-select bg-dark text-white border-secondary font-monospace">
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small">Inquiry Message</label>
                    <textarea name="message" rows="4" required placeholder="Detail the query or request for the tenant administrator..." class="form-control bg-dark text-white border-secondary"></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary border-opacity-25">
                <button type="button" class="btn btn-secondary font-monospace" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary font-monospace fw-bold">Dispatch Inquiry</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Dispatch Alert (Super Admin) -->
<div class="modal fade" id="dispatchAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('tenant-manager.alerts.dispatch') }}" class="modal-content" style="background: #111a30; border: 1px solid rgba(255,255,255,0.15);">
            @csrf
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title text-white font-archivo">Dispatch Operational Alert</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-secondary small">Target Workspace Tenant</label>
                    <select name="tenant_id" class="form-select bg-dark text-white border-secondary font-monospace" required>
                        <option value="">Select Tenant Organization...</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} (/{{ $t->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small">Alert Title</label>
                    <input type="text" name="title" required placeholder="e.g. Scheduled Maintenance Tonight at 02:00 UTC" class="form-control bg-dark text-white border-secondary">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-secondary small">Severity</label>
                        <select name="severity" class="form-select bg-dark text-white border-secondary font-monospace">
                            <option value="info">Info (Blue)</option>
                            <option value="warning" selected>Warning (Orange)</option>
                            <option value="danger">Critical (Red)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-secondary small">Alert Type</label>
                        <select name="alert_type" class="form-select bg-dark text-white border-secondary font-monospace">
                            <option value="custom">Custom Notice</option>
                            <option value="trial_expiring">Trial Expiring</option>
                            <option value="quota_warning">Quota Limit</option>
                            <option value="system_update">System Update</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small">Alert Message Details</label>
                    <textarea name="message" rows="3" required placeholder="Describe the operational notice or call to action..." class="form-control bg-dark text-white border-secondary"></textarea>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="send_email" value="1" id="alertEmailCheck" checked>
                    <label class="form-check-label text-white small" for="alertEmailCheck">Also send immediate email notification to tenant admin</label>
                </div>
            </div>
            <div class="modal-footer border-secondary border-opacity-25">
                <button type="button" class="btn btn-secondary font-monospace" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning font-monospace text-dark fw-bold">Dispatch Alert Now</button>
            </div>
        </form>
    </div>
</div>
@endsection
