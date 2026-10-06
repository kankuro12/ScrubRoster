<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Integrations\GoogleSyncService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class GoogleIntegrationController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;

        if (! $tenant) {
            abort(404, 'No active tenant workspace found.');
        }

        $token = $tenant->ensureGoogleSyncToken();

        $icalFeedUrl = route('integrations.google.calendar.feed', ['token' => $token]);
        $attendanceSheetUrl = route('integrations.google.sheets.feed', ['token' => $token, 'type' => 'attendance']);
        $tasksSheetUrl = route('integrations.google.sheets.feed', ['token' => $token, 'type' => 'tasks']);
        $personnelSheetUrl = route('integrations.google.sheets.feed', ['token' => $token, 'type' => 'personnel']);

        return view('pages.google-integrations', [
            'tenant' => $tenant,
            'token' => $token,
            'icalFeedUrl' => $icalFeedUrl,
            'attendanceSheetUrl' => $attendanceSheetUrl,
            'tasksSheetUrl' => $tasksSheetUrl,
            'personnelSheetUrl' => $personnelSheetUrl,
        ]);
    }

    public function update(Request $request, TenantContext $context): RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;
        if (! $tenant) {
            return redirect()->back()->withErrors(['sync' => 'Tenant missing.']);
        }

        $validated = $request->validate([
            'google_calendar_id' => ['nullable', 'string', 'max:255'],
            'google_sheet_url_or_id' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant->update([
            'google_calendar_id' => $validated['google_calendar_id'] ?? null,
            'google_calendar_sync_enabled' => $request->boolean('google_calendar_sync_enabled'),
            'google_calendar_auto_sync' => $request->boolean('google_calendar_auto_sync'),
            'google_sheet_url_or_id' => $validated['google_sheet_url_or_id'] ?? null,
            'google_sheet_sync_enabled' => $request->boolean('google_sheet_sync_enabled'),
            'google_sheet_auto_sync' => $request->boolean('google_sheet_auto_sync'),
        ]);

        return redirect()->route('integrations.google')->with('status', 'Google sync configurations saved successfully.');
    }

    public function syncCalendar(Request $request, TenantContext $context, GoogleSyncService $service): JsonResponse|RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;
        if (! $tenant) {
            return response()->json(['error' => 'Tenant missing'], 404);
        }

        $result = $service->syncCalendar($tenant);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('integrations.google')->with('status', $result['message']);
    }

    public function syncSheets(Request $request, TenantContext $context, GoogleSyncService $service): JsonResponse|RedirectResponse
    {
        $tenant = $context->getTenant() ?? $request->user()?->tenant;
        if (! $tenant) {
            return response()->json(['error' => 'Tenant missing'], 404);
        }

        $result = $service->syncSheets($tenant);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('integrations.google')->with('status', $result['message']);
    }

    /**
     * Public token-protected endpoint for Google Calendar / Apple Calendar subscription (.ics).
     */
    public function feedIcal(string $token, GoogleSyncService $service): Response
    {
        $tenant = Tenant::withoutGlobalScopes()->where('google_sync_token', $token)->first();

        if (! $tenant) {
            abort(404, 'Calendar feed not found or invalid token.');
        }

        $ical = $service->generateIcalFeed($tenant);

        return response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="cleanway-' . $tenant->slug . '-calendar.ics"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Public token-protected endpoint for Google Sheets live import (=IMPORTDATA("...")).
     */
    public function feedCsv(string $token, string $type, GoogleSyncService $service): Response
    {
        $tenant = Tenant::withoutGlobalScopes()->where('google_sync_token', $token)->first();

        if (! $tenant) {
            abort(404, 'Sheets feed not found or invalid token.');
        }

        $validTypes = ['attendance', 'tasks', 'personnel'];
        if (! in_array($type, $validTypes, true)) {
            $type = 'attendance';
        }

        $csv = $service->generateCsvForSheet($tenant, $type);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'inline; filename="cleanway-' . $tenant->slug . '-' . $type . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
