@extends('layouts.app')

@section('title', 'Google Calendar & Google Sheets Sync')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Eyebrow -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-cloud-arrow-up me-1"></i> Cloud Integrations · Automated Sync Engine</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Google Calendar & Google Sheets Sync</h1>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm font-monospace" id="syncCalendarBtn">
                <i class="bi bi-calendar2-range me-1"></i> Sync Calendar Now
            </button>
            <button type="button" class="btn btn-outline-success btn-sm font-monospace" id="syncSheetsBtn">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Sync Sheets Now
            </button>
        </div>
    </div>

    <!-- Live Sync Status Banner 2-Up -->
    <div class="row g-3 mb-4">
        <!-- Google Calendar Card -->
        <div class="col-lg-6">
            <div class="card p-3 border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #f59e0b !important; border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary font-monospace small text-uppercase">Google Calendar Feed Sync</span>
                    <span class="badge {{ $tenant->google_calendar_sync_enabled ? 'bg-success' : 'bg-secondary' }} font-monospace">
                        {{ $tenant->google_calendar_sync_enabled ? 'ACTIVE' : 'IDLE' }}
                    </span>
                </div>
                <h5 class="font-archivo fw-bold mb-1" style="color: var(--cw-text-strong, #0F172A);">Subscribed Shift & Task iCal Feed</h5>
                <p class="text-secondary small mb-3">
                    Syncs all upcoming cleaning shifts and scheduled turnover tasks automatically to Google Calendar, Apple Calendar, and Microsoft Outlook.
                </p>
                <div class="text-secondary small font-monospace mt-auto">
                    <i class="bi bi-clock-history me-1"></i> Last Synced: 
                    <strong class="text-body fw-bold">{{ $tenant->google_calendar_last_synced_at ? $tenant->google_calendar_last_synced_at->diffForHumans() : 'Never' }}</strong>
                </div>
            </div>
        </div>

        <!-- Google Sheets Card -->
        <div class="col-lg-6">
            <div class="card p-3 border-0 shadow-sm h-100" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #10b981 !important; border-radius: var(--cw-radius-md, 8px);">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary font-monospace small text-uppercase">Google Sheets Live Sync</span>
                    <span class="badge {{ $tenant->google_sheet_sync_enabled ? 'bg-success' : 'bg-secondary' }} font-monospace">
                        {{ $tenant->google_sheet_sync_enabled ? 'ACTIVE' : 'IDLE' }}
                    </span>
                </div>
                <h5 class="font-archivo fw-bold mb-1" style="color: var(--cw-text-strong, #0F172A);">Live Formula & Webhook Stream</h5>
                <p class="text-secondary small mb-3">
                    Continuously pipes GPS attendance punches, completed inspection checklists, and cleaner rosters into Google Sheets spreadsheets.
                </p>
                <div class="text-secondary small font-monospace mt-auto">
                    <i class="bi bi-clock-history me-1"></i> Last Synced: 
                    <strong class="text-body fw-bold">{{ $tenant->google_sheet_last_synced_at ? $tenant->google_sheet_last_synced_at->diffForHumans() : 'Never' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Configuration Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <h5 class="font-archivo fw-bold mb-3" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-sliders me-1 text-primary"></i> Automated Sync Preferences</h5>
                <form method="POST" action="{{ route('integrations.google.update') }}">
                    @csrf
                    @method('PUT')

                    <!-- Google Calendar Section -->
                    <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-dark font-monospace text-uppercase small mb-0 fw-bold"><i class="bi bi-calendar3 text-warning me-1"></i> Google Calendar Settings</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="google_calendar_sync_enabled" value="1" id="calEnabled" {{ $tenant->google_calendar_sync_enabled ? 'checked' : '' }}>
                                <label class="form-check-label text-body small fw-medium" for="calEnabled">Enable Calendar Feed</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small">Your Private Subscribed iCal URL</label>
                            <div class="input-group">
                                <input type="text" readonly value="{{ $icalFeedUrl }}" id="icalUrlInput" class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace small">
                                <button type="button" class="btn btn-outline-secondary btn-sm copy-btn" data-target="#icalUrlInput">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                                <a href="https://calendar.google.com/calendar/r/settings/addbyurl?cid={{ urlencode($icalFeedUrl) }}" target="_blank" class="btn btn-warning btn-sm text-dark fw-bold">
                                    <i class="bi bi-google"></i> Subscribe in Google Calendar
                                </a>
                            </div>
                            <span class="text-secondary small">Any calendar app subscribing to this link updates automatically whenever shifts or tasks change.</span>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="google_calendar_auto_sync" value="1" id="calAuto" {{ $tenant->google_calendar_auto_sync ? 'checked' : '' }}>
                            <label class="form-check-label text-body small fw-medium" for="calAuto">Automatically refresh shifts in real-time when assigned or rescheduled</label>
                        </div>
                    </div>

                    <!-- Google Sheets Section -->
                    <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-dark font-monospace text-uppercase small mb-0 fw-bold"><i class="bi bi-table text-success me-1"></i> Google Sheets Live Sync Formulas</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="google_sheet_sync_enabled" value="1" id="sheetEnabled" {{ $tenant->google_sheet_sync_enabled ? 'checked' : '' }}>
                                <label class="form-check-label text-body small fw-medium" for="sheetEnabled">Enable Sheets Sync</label>
                            </div>
                        </div>

                        <!-- 1. Attendance Sheet -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Formula 1: Live Attendance & Clock-In Log</label>
                            <div class="input-group">
                                <input type="text" readonly value='=IMPORTDATA("{{ $attendanceSheetUrl }}")' id="attendanceFormula" class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace small">
                                <button type="button" class="btn btn-outline-success btn-sm copy-btn" data-target="#attendanceFormula">
                                    <i class="bi bi-clipboard"></i> Copy Formula
                                </button>
                            </div>
                        </div>

                        <!-- 2. Tasks Sheet -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Formula 2: Live Tasks & Inspections Log</label>
                            <div class="input-group">
                                <input type="text" readonly value='=IMPORTDATA("{{ $tasksSheetUrl }}")' id="tasksFormula" class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace small">
                                <button type="button" class="btn btn-outline-success btn-sm copy-btn" data-target="#tasksFormula">
                                    <i class="bi bi-clipboard"></i> Copy Formula
                                </button>
                            </div>
                        </div>

                        <!-- 3. Personnel Sheet -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Formula 3: Live Cleaner Personnel Roster</label>
                            <div class="input-group">
                                <input type="text" readonly value='=IMPORTDATA("{{ $personnelSheetUrl }}")' id="personnelFormula" class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace small">
                                <button type="button" class="btn btn-outline-success btn-sm copy-btn" data-target="#personnelFormula">
                                    <i class="bi bi-clipboard"></i> Copy Formula
                                </button>
                            </div>
                        </div>

                        <!-- Optional Webhook Push -->
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Optional: Google Apps Script Webhook URL (For Direct Row Appends)</label>
                            <input type="url" name="google_sheet_url_or_id" value="{{ $tenant->google_sheet_url_or_id }}" placeholder="https://script.google.com/macros/s/.../exec" class="form-control bg-light text-dark border-secondary border-opacity-25 font-monospace small">
                            <span class="text-secondary small">If configured, ScrubRoster will send a POST webhook on every shift and attendance punch.</span>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="google_sheet_auto_sync" value="1" id="sheetAuto" {{ $tenant->google_sheet_auto_sync ? 'checked' : '' }}>
                            <label class="form-check-label text-body small fw-medium" for="sheetAuto">Automated background sheet sync enabled</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-check2-circle me-1"></i> Save Sync Configurations
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Setup Manual & Walkthrough -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <h5 class="font-archivo fw-bold mb-3" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-book me-1 text-primary"></i> Fast Setup Manual</h5>

                <div class="accordion accordion-flush" id="manualAccordion">
                    <!-- Step 1: Calendar -->
                    <div class="accordion-item bg-transparent border-secondary border-opacity-10">
                        <h2 class="accordion-header" id="headingCal">
                            <button class="accordion-button bg-transparent text-dark font-archivo fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCal">
                                <i class="bi bi-1-circle text-warning me-2"></i> Adding to Google Calendar in 30 Seconds
                            </button>
                        </h2>
                        <div id="collapseCal" class="accordion-collapse collapse show" data-bs-parent="#manualAccordion">
                            <div class="accordion-body text-secondary small">
                                <ol class="ps-3 mb-2">
                                    <li class="mb-1">Click the <strong>Subscribe in Google Calendar</strong> button, or copy the iCal URL.</li>
                                    <li class="mb-1">In Google Calendar, navigate to <em>Other calendars &rarr; From URL</em>.</li>
                                    <li class="mb-1">Paste the ScrubRoster URL and click <em>Add calendar</em>.</li>
                                </ol>
                                <p class="mb-0 text-success fw-medium">Done! Shifts will appear in Google Calendar alongside cleaner assignments and property addresses.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Sheets -->
                    <div class="accordion-item bg-transparent border-secondary border-opacity-10">
                        <h2 class="accordion-header" id="headingSheet">
                            <button class="accordion-button bg-transparent text-dark font-archivo fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSheet">
                                <i class="bi bi-2-circle text-success me-2"></i> Linking Live Google Sheets (=IMPORTDATA)
                            </button>
                        </h2>
                        <div id="collapseSheet" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                            <div class="accordion-body text-secondary small">
                                <ol class="ps-3 mb-2">
                                    <li class="mb-1">Create a new spreadsheet in Google Sheets.</li>
                                    <li class="mb-1">Click on cell <strong>A1</strong>.</li>
                                    <li class="mb-1">Copy and paste the formula: <code>=IMPORTDATA("...")</code> from above.</li>
                                    <li class="mb-1">Press <kbd>Enter</kbd>.</li>
                                </ol>
                                <p class="mb-0 text-success fw-medium">Google Sheets will instantly populate table headers and all live attendance data!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Fast QR Login -->
                    <div class="accordion-item bg-transparent border-secondary border-opacity-10">
                        <h2 class="accordion-header" id="headingQr">
                            <button class="accordion-button bg-transparent text-dark font-archivo fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQr">
                                <i class="bi bi-3-circle text-primary me-2"></i> Depot QR Scan Kiosk & Cleaner Login
                            </button>
                        </h2>
                        <div id="collapseQr" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                            <div class="accordion-body text-secondary small">
                                <p>Supervisors can launch the full-screen depot kiosk at <a href="{{ route('quick-login') }}" class="text-primary fw-semibold">Depot QR Station</a>. Field workers simply flash their physical or mobile QR badge to sign in instantaneously without needing passwords.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 text-center">
                    <a href="{{ route('manual.index') }}" class="btn btn-outline-secondary btn-sm w-100 font-monospace">
                        <i class="bi bi-journal-text me-1"></i> Open Full Field Ops Manual
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Copy buttons
    $('.copy-btn').on('click', function() {
        const target = $($(this).data('target'));
        navigator.clipboard.writeText(target.val()).then(() => {
            const original = $(this).html();
            $(this).html('<i class="bi bi-check2"></i> Copied!');
            setTimeout(() => $(this).html(original), 2000);
        });
    });

    // Instant Sync Calendar button
    $('#syncCalendarBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Syncing...');

        axios.post('{{ route("integrations.google.calendar.sync") }}')
            .then(function(res) {
                alert(res.data.message);
                location.reload();
            })
            .catch(function(err) {
                alert('Sync failed: ' + (err.response?.data?.message || err.message));
                btn.prop('disabled', false).html('<i class="bi bi-calendar2-range me-1"></i> Sync Calendar Now');
            });
    });

    // Instant Sync Sheets button
    $('#syncSheetsBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Syncing...');

        axios.post('{{ route("integrations.google.sheets.sync") }}')
            .then(function(res) {
                alert(res.data.message);
                location.reload();
            })
            .catch(function(err) {
                alert('Sync failed: ' + (err.response?.data?.message || err.message));
                btn.prop('disabled', false).html('<i class="bi bi-file-earmark-spreadsheet me-1"></i> Sync Sheets Now');
            });
    });
});
</script>
@endpush
