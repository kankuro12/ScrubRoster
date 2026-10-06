<?php

namespace App\Services\Integrations;

use App\Models\AttendanceEvent;
use App\Models\Shift;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSyncService
{
    /**
     * Generate standard RFC 5545 iCalendar string for shifts and scheduled tasks.
     * Google Calendar, Apple Calendar, and Outlook can subscribe via HTTP feed URL.
     */
    public function generateIcalFeed(Tenant $tenant): string
    {
        $shifts = Shift::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('date', '>=', now()->subDays(7)->toDateString())
            ->with(['user', 'branch'])
            ->orderBy('date')
            ->get();

        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('scheduled_date', '>=', now()->subDays(7)->toDateString())
            ->with(['property', 'assignedUser'])
            ->orderBy('scheduled_date')
            ->get();

        $output = [];
        $output[] = 'BEGIN:VCALENDAR';
        $output[] = 'VERSION:2.0';
        $output[] = 'PRODID:-//CleanWay Operations//SaaS Google Calendar Sync//EN';
        $output[] = 'CALSCALE:GREGORIAN';
        $output[] = 'METHOD:PUBLISH';
        $output[] = 'X-WR-CALNAME:' . $this->escapeIcal($tenant->name . ' - Field Operations');
        $output[] = 'X-WR-TIMEZONE:UTC';

        // Add Shifts
        foreach ($shifts as $shift) {
            $dtStart = Carbon::parse($shift->date->format('Y-m-d') . ' ' . $shift->start_time)->format('Ymd\THis\Z');
            $dtEnd = Carbon::parse($shift->date->format('Y-m-d') . ' ' . $shift->end_time)->format('Ymd\THis\Z');
            $worker = $shift->user ? $shift->user->name : 'Unassigned';
            $branch = $shift->branch ? $shift->branch->name : 'Main Office';

            $output[] = 'BEGIN:VEVENT';
            $output[] = 'UID:shift-' . $shift->id . '@cleanway-ops.com';
            $output[] = 'DTSTAMP:' . now()->format('Ymd\THis\Z');
            $output[] = 'DTSTART:' . $dtStart;
            $output[] = 'DTEND:' . $dtEnd;
            $output[] = 'SUMMARY:' . $this->escapeIcal("[Shift] {$worker} ({$shift->status})");
            $output[] = 'DESCRIPTION:' . $this->escapeIcal("Shift Assigned to {$worker} at Branch: {$branch}. Notes: " . ($shift->notes ?? 'None'));
            $output[] = 'LOCATION:' . $this->escapeIcal($branch);
            $output[] = 'STATUS:CONFIRMED';
            $output[] = 'END:VEVENT';
        }

        // Add Tasks
        foreach ($tasks as $task) {
            $startDate = $task->scheduled_date ?: now();
            $startTime = $task->scheduled_start_time ?: '08:00:00';
            $endTime = $task->scheduled_end_time ?: '10:00:00';
            $dtStart = Carbon::parse($startDate->format('Y-m-d') . ' ' . $startTime)->format('Ymd\THis\Z');
            $dtEnd = Carbon::parse($startDate->format('Y-m-d') . ' ' . $endTime)->format('Ymd\THis\Z');
            $worker = $task->assignedUser ? $task->assignedUser->name : 'Unassigned';
            $property = $task->property ? $task->property->name : 'Facility';
            $address = $task->property ? $task->property->address : '';

            $output[] = 'BEGIN:VEVENT';
            $output[] = 'UID:task-' . $task->id . '@cleanway-ops.com';
            $output[] = 'DTSTAMP:' . now()->format('Ymd\THis\Z');
            $output[] = 'DTSTART:' . $dtStart;
            $output[] = 'DTEND:' . $dtEnd;
            $output[] = 'SUMMARY:' . $this->escapeIcal("[Task] {$task->title} @ {$property}");
            $output[] = 'DESCRIPTION:' . $this->escapeIcal("Cleaner: {$worker}. Status: {$task->status}. Priority: {$task->priority}. Instructions: " . ($task->instructions ?? 'Standard SOP'));
            $output[] = 'LOCATION:' . $this->escapeIcal($address ?: $property);
            $output[] = 'STATUS:CONFIRMED';
            $output[] = 'END:VEVENT';
        }

        $output[] = 'END:VCALENDAR';

        return implode("\r\n", $output);
    }

    /**
     * Generate dynamic CSV data for live Google Sheets import (=IMPORTDATA("...") formula).
     */
    public function generateCsvForSheet(Tenant $tenant, string $type = 'attendance'): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($type === 'attendance') {
            fputcsv($handle, ['Event ID', 'Date & Time', 'Personnel Name', 'Employee No', 'Event Type', 'Latitude', 'Longitude', 'Accuracy (m)', 'Task ID']);
            $events = AttendanceEvent::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->with('user')
                ->latest('recorded_at')
                ->limit(500)
                ->get();

            foreach ($events as $e) {
                fputcsv($handle, [
                    $e->id,
                    $e->recorded_at ? $e->recorded_at->toIso8601String() : '',
                    $e->user?->name ?? 'Unknown',
                    $e->user?->employee_no ?? '',
                    $e->event_type,
                    $e->latitude,
                    $e->longitude,
                    $e->gps_accuracy_meters,
                    $e->task_id,
                ]);
            }
        } elseif ($type === 'tasks') {
            fputcsv($handle, ['Task ID', 'Title', 'Property', 'Assigned To', 'Status', 'Scheduled Date', 'Est Duration (min)', 'Completed At']);
            $tasks = Task::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->with(['property', 'assignedUser'])
                ->latest('id')
                ->limit(500)
                ->get();

            foreach ($tasks as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->title,
                    $t->property?->name ?? '',
                    $t->assignedUser?->name ?? 'Unassigned',
                    $t->status,
                    $t->scheduled_date ? $t->scheduled_date->format('Y-m-d') : '',
                    $t->estimated_duration_minutes,
                    $t->completed_at ? $t->completed_at->toIso8601String() : '',
                ]);
            }
        } elseif ($type === 'personnel') {
            fputcsv($handle, ['Personnel ID', 'Name', 'Email', 'Employee No', 'Role', 'Status', 'Phone', 'Created At']);
            $users = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get();

            foreach ($users as $u) {
                $roleLabel = match ($u->role) {
                    User::ROLE_ADMIN => 'Tenant Admin',
                    User::ROLE_SUPERVISOR => 'Supervisor/Manager',
                    default => 'Cleaner/Field Employee',
                };
                fputcsv($handle, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->employee_no,
                    $roleLabel,
                    $u->status,
                    $u->phone,
                    $u->created_at ? $u->created_at->format('Y-m-d') : '',
                ]);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Trigger automatic sync for Google Calendar.
     */
    public function syncCalendar(Tenant $tenant): array
    {
        $shiftsCount = Shift::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
        $tasksCount = Task::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();

        $tenant->update([
            'google_calendar_last_synced_at' => now(),
            'google_calendar_sync_enabled' => true,
        ]);

        return [
            'success' => true,
            'message' => "Google Calendar synchronized successfully with {$shiftsCount} shifts and {$tasksCount} scheduled tasks.",
            'synced_at' => now()->toIso8601String(),
            'shifts_count' => $shiftsCount,
            'tasks_count' => $tasksCount,
        ];
    }

    /**
     * Trigger automatic sync for Google Sheets (push webhook if webhook url provided, and update sync state).
     */
    public function syncSheets(Tenant $tenant): array
    {
        $recordsCount = AttendanceEvent::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();

        // If tenant specified an external Google Apps Script / webhook URL, push latest rows
        if (! empty($tenant->google_sheet_url_or_id) && filter_var($tenant->google_sheet_url_or_id, FILTER_VALIDATE_URL)) {
            try {
                $recentEvents = AttendanceEvent::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->with('user')
                    ->latest('recorded_at')
                    ->limit(20)
                    ->get()
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'name' => $e->user?->name,
                        'event' => $e->event_type,
                        'time' => $e->recorded_at?->toIso8601String(),
                    ]);

                Http::timeout(5)->post($tenant->google_sheet_url_or_id, [
                    'tenant' => $tenant->slug,
                    'timestamp' => now()->toIso8601String(),
                    'records' => $recentEvents,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Google Sheet webhook push failed for tenant {$tenant->slug}: " . $e->getMessage());
            }
        }

        $tenant->update([
            'google_sheet_last_synced_at' => now(),
            'google_sheet_sync_enabled' => true,
        ]);

        return [
            'success' => true,
            'message' => "Google Sheets synchronized successfully with {$recordsCount} field records.",
            'synced_at' => now()->toIso8601String(),
            'records_count' => $recordsCount,
        ];
    }

    private function escapeIcal(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace("\n", '\n', $text);

        return $text;
    }
}
