@extends('layouts.app')

@section('title', 'Field Operations & Platform Manual')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Eyebrow -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-journal-bookmark me-1"></i> Documentation · Operational Guide</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Field Operations & Platform Manual</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('integrations.google') }}" class="btn btn-outline-primary btn-sm font-monospace">
                <i class="bi bi-cloud-arrow-up me-1"></i> Google Sync Settings
            </a>
            <a href="{{ route('quick-login') }}" class="btn btn-outline-secondary btn-sm font-monospace">
                <i class="bi bi-qr-code-scan me-1"></i> Launch Depot Kiosk
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Navigation for Manual -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm p-3 sticky-top" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); top: 80px; border-radius: var(--cw-radius-md, 8px);">
                <div class="font-monospace text-secondary small text-uppercase mb-2 fw-semibold">Manual Sections</div>
                <div class="list-group list-group-flush bg-transparent">
                    <a href="#section-google-calendar" class="list-group-item list-group-item-action bg-transparent text-body border-secondary border-opacity-10 px-2 py-2 small fw-medium">
                        <i class="bi bi-calendar3 text-warning me-2"></i> 1. Google Calendar Sync
                    </a>
                    <a href="#section-google-sheets" class="list-group-item list-group-item-action bg-transparent text-body border-secondary border-opacity-10 px-2 py-2 small fw-medium">
                        <i class="bi bi-table text-success me-2"></i> 2. Google Sheets Live Sync
                    </a>
                    <a href="#section-qr-login" class="list-group-item list-group-item-action bg-transparent text-body border-secondary border-opacity-10 px-2 py-2 small fw-medium">
                        <i class="bi bi-qr-code-scan text-info me-2"></i> 3. QR Badges & Depot Kiosk
                    </a>
                    <a href="#section-multi-tenant" class="list-group-item list-group-item-action bg-transparent text-body border-secondary border-opacity-10 px-2 py-2 small fw-medium">
                        <i class="bi bi-buildings text-warning me-2"></i> 4. Multi-Tenant Workspaces
                    </a>
                    <a href="#section-billing-payments" class="list-group-item list-group-item-action bg-transparent text-body border-secondary border-opacity-10 px-2 py-2 small fw-medium">
                        <i class="bi bi-credit-card-2-front text-primary me-2"></i> 5. Stripe, PayPal & Quotas
                    </a>
                </div>
            </div>
        </div>

        <!-- Manual Content -->
        <div class="col-lg-9">
            <!-- Section 1: Google Calendar -->
            <div class="card border-0 shadow-sm p-4 mb-4" id="section-google-calendar" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-warning text-dark font-monospace fs-6">01</span>
                    <h4 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">Google Calendar Automatic Sync</h4>
                </div>
                <p class="text-secondary">
                    ScrubRoster provides an automated, subscribed iCalendar feed (RFC 5545 <code>.ics</code>) for your organization. This feed contains real-time schedules for all cleaners, shifts, and assigned cleaning tasks.
                </p>

                <div class="card p-3 mb-3" style="background: var(--cw-surface-2, #F1F5F9); border: 1px solid var(--cw-border, #CBD5E1);">
                    <h6 class="text-dark font-monospace text-uppercase small mb-2 fw-bold"><i class="bi bi-arrow-right-circle text-warning me-1"></i> How to Subscribe:</h6>
                    <ol class="text-body small mb-0 ps-3">
                        <li class="mb-2">Go to <a href="{{ route('integrations.google') }}" class="text-primary fw-semibold">Google Integrations</a> in ScrubRoster.</li>
                        <li class="mb-2">Click <strong>"Subscribe in Google Calendar"</strong> or copy your organization's private iCal URL.</li>
                        <li class="mb-2">In Google Calendar, click the <code>+</code> next to <strong>"Other calendars"</strong> on the left sidebar &rarr; select <strong>"From URL"</strong>.</li>
                        <li class="mb-0">Paste the URL and click <strong>"Add calendar"</strong>.</li>
                    </ol>
                </div>
                <div class="text-secondary small">
                    <i class="bi bi-info-circle text-info me-1"></i> <strong>Automatic Sync:</strong> When auto-sync is enabled, shifts and tasks are synchronized directly to external calendars whenever a supervisor creates or reschedules tasks.
                </div>
            </div>

            <!-- Section 2: Google Sheets -->
            <div class="card border-0 shadow-sm p-4 mb-4" id="section-google-sheets" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-success text-white font-monospace fs-6">02</span>
                    <h4 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">Google Sheets Live Data Sync (=IMPORTDATA)</h4>
                </div>
                <p class="text-secondary">
                    Keep your operations spreadsheets, payroll logs, and attendance punches continuously up to date in Google Sheets without manual exports or CSV file uploads.
                </p>

                <div class="card p-3 mb-3" style="background: var(--cw-surface-2, #F1F5F9); border: 1px solid var(--cw-border, #CBD5E1);">
                    <h6 class="text-dark font-monospace text-uppercase small mb-2 fw-bold"><i class="bi bi-table text-success me-1"></i> 3 Available Live Feeds:</h6>
                    <ul class="text-body small mb-3 ps-3">
                        <li class="mb-1"><strong>Attendance & GPS Punches:</strong> Records cleaner clock-in, clock-out, GPS accuracy, and timestamps.</li>
                        <li class="mb-1"><strong>Tasks & Inspection Logs:</strong> Records property names, assigned cleaner, status, and completion times.</li>
                        <li class="mb-1"><strong>Cleaner Personnel Roster:</strong> Lists all active staff, roles, phone numbers, and employee IDs.</li>
                    </ul>

                    <h6 class="text-dark font-monospace text-uppercase small mb-2 fw-bold">Setup in Google Sheets:</h6>
                    <p class="text-secondary small mb-2">Simply paste the formula into cell <code>A1</code> of any Google Sheet:</p>
                    <code class="p-2 d-block bg-dark text-success rounded font-monospace small mb-2">=IMPORTDATA("{{ url('/api/integrations/google-sheets/attendance.csv?token=YOUR_TOKEN') }}")</code>
                    <p class="text-secondary small mb-0">Google Sheets refreshes the data automatically throughout the day!</p>
                </div>
            </div>

            <!-- Section 3: QR Badges & Depot Kiosk -->
            <div class="card border-0 shadow-sm p-4 mb-4" id="section-qr-login" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-info text-dark font-monospace fs-6">03</span>
                    <h4 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">Depot QR Scanner Kiosks & Instant Cleaner Login</h4>
                </div>
                <p class="text-secondary">
                    Field cleaners do not need to memorize passwords. Supervisors can issue physical badge cards or digital tokens that allow instant single-tap login.
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card p-3 h-100" style="background: var(--cw-surface-2, #F1F5F9); border: 1px solid var(--cw-border, #CBD5E1);">
                            <h6 class="font-archivo fw-bold mb-2" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-qr-code text-primary me-1"></i> 1. Generating Badges</h6>
                            <p class="text-secondary small mb-0">
                                Open the <a href="{{ route('personnel') }}" class="text-primary fw-semibold">Personnel Roster</a>. Click the <strong><i class="bi bi-qr-code"></i> QR</strong> button next to any cleaner to generate an active token with a downloadable printable badge image.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3 h-100" style="background: var(--cw-surface-2, #F1F5F9); border: 1px solid var(--cw-border, #CBD5E1);">
                            <h6 class="font-archivo fw-bold mb-2" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-display text-primary me-1"></i> 2. Depot Kiosk Station</h6>
                            <p class="text-secondary small mb-0">
                                Open <a href="{{ route('quick-login') }}" class="text-primary fw-semibold">Depot QR Station</a> on any tablet or office monitor. Cleaners simply present their badge to the camera or click their name to sign in immediately into field mode.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Multi-Tenant Workspaces -->
            <div class="card border-0 shadow-sm p-4 mb-4" id="section-multi-tenant" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-warning text-dark font-monospace fs-6">04</span>
                    <h4 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">Multi-Tenant Organization Isolation</h4>
                </div>
                <p class="text-secondary">
                    ScrubRoster guarantees complete database scoping and isolation between commercial organizations:
                </p>
                <ul class="text-body small ps-3 mb-0">
                    <li class="mb-2"><strong>Dedicated Slugs:</strong> Each organization accesses its workspace via <code>/slug</code> prefix.</li>
                    <li class="mb-2"><strong>Dual Login:</strong> Tenant administrators sign in via standard <code>/login</code>, while team members sign in via <code>/{prefix}/tenant</code>.</li>
                    <li class="mb-0"><strong>Shared Cleaner Roster:</strong> Field cleaners can belong to multiple cleaning companies simultaneously using the exact same email address without conflict.</li>
                </ul>
            </div>

            <!-- Section 5: Billing & Payments -->
            <div class="card border-0 shadow-sm p-4 mb-4" id="section-billing-payments" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-primary text-white font-monospace fs-6">05</span>
                    <h4 class="font-archivo fw-bold mb-0" style="color: var(--cw-text-strong, #0F172A);">Subscription Billing with Stripe & PayPal</h4>
                </div>
                <p class="text-secondary">
                    Organizations scale with plans priced by employee count capacity. Access the <a href="{{ route('billing.index') }}" class="text-primary fw-semibold">Subscription & Billing</a> page to upgrade, change billing cycles, or enter promo coupons.
                </p>
                <div class="p-3 rounded font-monospace small" style="background: var(--cw-surface-2, #F1F5F9); border: 1px solid var(--cw-border, #CBD5E1);">
                    <div class="text-dark fw-bold mb-1"><i class="bi bi-tag-fill text-warning me-1"></i> Available Promo Coupons:</div>
                    <div class="text-body">&bull; <code>SCRUBROSTER20</code>: 20% discount on any monthly or yearly plan.</div>
                    <div class="text-body">&bull; <code>OPS50</code>: $50 credit applied immediately at checkout.</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
