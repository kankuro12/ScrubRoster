<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Web\ApprovalController;
use App\Http\Controllers\Web\AttendanceController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\BedTypeController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\CalendarController;
use App\Http\Controllers\Web\ChecklistTemplateController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\EvidenceController;
use App\Http\Controllers\Web\FcmTestController;
use App\Http\Controllers\Web\IncidentController;
use App\Http\Controllers\Web\LinenTypeController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\PersonnelController;
use App\Http\Controllers\Web\PayrollController;
use App\Http\Controllers\Web\PlacesController;
use App\Http\Controllers\Web\PropertyAssignmentController;
use App\Http\Controllers\Web\PropertyCategoryController;
use App\Http\Controllers\Web\PropertyController;
use App\Http\Controllers\Web\PropertyTagController;
use App\Http\Controllers\Web\RecurrenceController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\ShiftController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Auth\TenantAuthController;
use App\Http\Controllers\Auth\TenantRegisterController;
use App\Http\Controllers\Web\TaskTypeController;
use App\Http\Controllers\Web\TeamController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Web\TenantManagerController;
use App\Http\Controllers\Web\TenantBillingController;
use App\Http\Controllers\Web\TenantQueryController;
use App\Http\Controllers\Web\TenantAlertController;
use App\Http\Controllers\Web\CustomerInvoiceController;
use App\Http\Controllers\Web\GoogleIntegrationController;
use App\Http\Controllers\Web\ManualController;
use Illuminate\Support\Facades\Route;

// Public routes — marketing front website & auth
Route::get('/', [FrontController::class, 'index'])->name('root');
Route::get('/sitemap.xml', [FrontController::class, 'sitemap'])->name('sitemap');

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Self-signup for tenants
Route::get('signup', [TenantRegisterController::class, 'showRegistrationForm'])->name('signup');
Route::post('signup', [TenantRegisterController::class, 'register'])->name('signup.submit');
Route::get('register', [TenantRegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [TenantRegisterController::class, 'register'])->name('register.submit');

// Forgot / reset password (guest only).
Route::middleware('guest')->group(function (): void {
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated + permission-protected routes.
Route::middleware('auth')->prefix('admin')->group(function (): void {
    Route::middleware('permission:4.1')->get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::middleware('permission:7.1')->group(function (): void {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/shifts', [ReportController::class, 'shiftsReport'])->name('reports.shifts');
        Route::get('/reports/payouts', [ReportController::class, 'payoutsReport'])->name('reports.payouts');
    });
    Route::middleware('permission:7.2')->group(function (): void {
        Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/exports/{job}/download', [ReportController::class, 'download'])->name('reports.download');
    });
    Route::post('/attendance/office-punch', [AttendanceController::class, 'officePunch'])->name('attendance.office-punch');

    Route::middleware('permission:9.1')->get('/audit', [AuditLogController::class, 'index'])->name('audit');

    Route::middleware('permission:3.1')->group(function (): void {
        Route::get('/properties', [PropertyController::class, 'index'])->name('properties');
        Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
        Route::get('/properties/mass-manage', [PropertyController::class, 'massManage'])->name('properties.mass-manage');
        Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
        Route::get('/places/autocomplete', [PlacesController::class, 'autocomplete'])->name('places.autocomplete');
        Route::get('/places/details', [PlacesController::class, 'details'])->name('places.details');
        Route::get('/checklists/{checklist}/items', [\App\Http\Controllers\Web\ChecklistTemplateController::class, 'items'])->name('checklists.items');
        Route::get('/properties/options', [PropertyController::class, 'options'])->name('properties.options');
        Route::get('/property-categories', [PropertyCategoryController::class, 'index'])->name('property-categories');
        Route::get('/property-tags', [PropertyTagController::class, 'index'])->name('property-tags');
        Route::get('/clients', [ClientController::class, 'index'])->name('clients');
        Route::get('/clients/options', [ClientController::class, 'options'])->name('clients.options');
        Route::get('/linen-types', [LinenTypeController::class, 'index'])->name('linen-types');
        Route::get('/linen-types/options', [LinenTypeController::class, 'options'])->name('linen-types.options');
        Route::get('/bed-types', [BedTypeController::class, 'index'])->name('bed-types');
        Route::get('/bed-types/options', [BedTypeController::class, 'options'])->name('bed-types.options');
    });

    Route::middleware('permission:3.2')->group(function (): void {
        Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::post('/linen-types', [LinenTypeController::class, 'store'])->name('linen-types.store');
        Route::post('/bed-types', [BedTypeController::class, 'store'])->name('bed-types.store');
    });

    Route::middleware('permission:3.3')->group(function (): void {
        Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::post('/properties/mass-save', [PropertyController::class, 'massSave'])->name('properties.mass-save');
        Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
        Route::post('/properties/{property}/retry-geocode', [PropertyController::class, 'retryGeocode'])->name('properties.retry-geocode');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        Route::put('/linen-types/{linenType}', [LinenTypeController::class, 'update'])->name('linen-types.update');
        Route::delete('/linen-types/{linenType}', [LinenTypeController::class, 'destroy'])->name('linen-types.destroy');
        Route::put('/bed-types/{bedType}', [BedTypeController::class, 'update'])->name('bed-types.update');
        Route::delete('/bed-types/{bedType}', [BedTypeController::class, 'destroy'])->name('bed-types.destroy');
    });

    Route::middleware('permission:3.4')->group(function (): void {
        Route::post('/property-categories', [PropertyCategoryController::class, 'store'])->name('property-categories.store');
        Route::put('/property-categories/{category}', [PropertyCategoryController::class, 'update'])->name('property-categories.update');
    });

    Route::middleware('permission:3.5')->group(function (): void {
        Route::post('/property-tags', [PropertyTagController::class, 'store'])->name('property-tags.store');
        Route::put('/property-tags/{tag}', [PropertyTagController::class, 'update'])->name('property-tags.update');
        Route::post('/property-tags/merge', [PropertyTagController::class, 'merge'])->name('property-tags.merge');
        Route::post('/properties/bulk/tags', [PropertyTagController::class, 'bulkAssign'])->name('property-tags.bulk-assign');
        Route::delete('/properties/bulk/tags', [PropertyTagController::class, 'bulkRemove'])->name('property-tags.bulk-remove');
    });

    Route::middleware('permission:3.6')->group(function (): void {
        Route::post('/properties/{property}/assignments', [PropertyAssignmentController::class, 'store'])->name('property-assignments.store');
        Route::delete('/property-assignments/{assignment}', [PropertyAssignmentController::class, 'destroy'])->name('property-assignments.destroy');
    });

    // Own notifications — any authenticated user.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/read', [NotificationController::class, 'readFeed'])->name('notifications.read-feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');

    // Ghost FCM test page — admin only, intentionally not linked anywhere.
    Route::middleware('role:0')->group(function (): void {
        Route::get('/_fcm-test', [FcmTestController::class, 'index'])->name('fcm-test');
        Route::post('/_fcm-test', [FcmTestController::class, 'send'])->name('fcm-test.send');
    });

    Route::middleware('permission:4.1')->group(function (): void {
        Route::get('/my-tasks', [TaskController::class, 'my'])->name('tasks.my');
        Route::get('/tasks/worksheet', [TaskController::class, 'worksheet'])->name('tasks.worksheet');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::get('/tasks/{task}/work', [TaskController::class, 'work'])->name('tasks.work');
        Route::post('/tasks/{task}/checklists/{checklist}/toggle', [TaskController::class, 'toggleChecklistRequirement'])->name('tasks.checklists.toggle');
        Route::post('/tasks/{task}/checklists/{checklist}/upload-photo', [TaskController::class, 'uploadChecklistPhoto'])->name('tasks.checklists.photo');
        Route::post('/tasks/{task}/checklists/{checklist}/delete-photo', [TaskController::class, 'deleteChecklistPhoto'])->name('tasks.checklists.photo-delete');
        Route::get('/tasks/{task}/checklists/{checklist}/photo', [TaskController::class, 'getChecklistPhoto'])->name('tasks.checklists.photo-get');
        Route::post('/tasks/{task}/checklists/{checklist}/comment', [TaskController::class, 'updateChecklistComment'])->name('tasks.checklists.comment');
        Route::post('/tasks/{task}/comments', [TaskController::class, 'storeComment'])->name('tasks.comments.store');
        Route::post('/tasks/{task}/due-time', [DashboardController::class, 'updateDueTime'])->name('tasks.due-time');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::get('/recurrences', [RecurrenceController::class, 'index'])->name('recurrences');
        Route::get('/task-types', [TaskTypeController::class, 'index'])->name('task-types');
        Route::get('/checklists', [ChecklistTemplateController::class, 'index'])->name('checklists');
    });

    // Task list (all users' tasks) — permitted users only.
    Route::middleware('permission:4.9')->get('/tasks', [TaskController::class, 'index'])->name('tasks');

    Route::middleware('permission:4.2')->group(function (): void {
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('/recurrences', [RecurrenceController::class, 'store'])->name('recurrences.store');
        Route::post('/recurrences/{recurrence}/generate', [RecurrenceController::class, 'generateNow'])->name('recurrences.generate-now');
    });

    Route::middleware('permission:4.3')->group(function (): void {
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
        Route::post('/tasks/{task}/quick-schedule', [TaskController::class, 'quickSchedule'])->name('tasks.quick-schedule');
        Route::post('/tasks/{task}/quick-assign', [TaskController::class, 'quickAssign'])->name('tasks.quick-assign');
        Route::delete('/tasks/{task}/assignments/{assignment}', [TaskController::class, 'unassign'])->name('tasks.unassign');
    });

    Route::middleware('permission:4.4')->post('/tasks/{task}/transition', [TaskController::class, 'transition'])->name('tasks.transition');

    Route::middleware('permission:4.6')->delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::middleware('permission:4.7')->group(function (): void {
        Route::post('/task-types', [TaskTypeController::class, 'store'])->name('task-types.store');
        Route::put('/task-types/{taskType}', [TaskTypeController::class, 'update'])->name('task-types.update');
    });

    Route::middleware('permission:4.8')->group(function (): void {
        Route::get('/checklists/create', [ChecklistTemplateController::class, 'create'])->name('checklists.create');
        Route::post('/checklists', [ChecklistTemplateController::class, 'store'])->name('checklists.store');
        Route::get('/checklists/{template}/edit', [ChecklistTemplateController::class, 'edit'])->name('checklists.edit');
        Route::put('/checklists/{template}', [ChecklistTemplateController::class, 'update'])->name('checklists.update');
        Route::delete('/checklists/{template}', [ChecklistTemplateController::class, 'destroy'])->name('checklists.destroy');
    });

    Route::middleware('permission:4.4')->group(function (): void {
        Route::post('/tasks/{task}/evidence', [TaskController::class, 'uploadEvidence'])->name('tasks.evidence');
        Route::delete('/tasks/{task}/evidence/{evidence}', [TaskController::class, 'deleteEvidence'])->name('tasks.evidence.delete');
        Route::get('/evidence/{evidence}', [EvidenceController::class, 'view'])->name('evidence.view')->middleware('permission:4.1');
        Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
        Route::post('/tasks/{task}/subtasks/{subtask}/toggle', [TaskController::class, 'toggleSubtask'])->name('tasks.subtasks.toggle');
        Route::post('/tasks/{task}/check-in', [TaskController::class, 'workCheckIn'])->name('tasks.work-checkin');
        Route::post('/tasks/{task}/complete', [TaskController::class, 'completeTask'])->name('tasks.complete');
    });

    Route::middleware('permission:4.5')->group(function (): void {
        Route::get('/approvals', [ApprovalController::class, 'queue'])->name('approvals');
        Route::post('/approvals/{task}/decide', [ApprovalController::class, 'decide'])->name('approvals.decide');
    });

    Route::middleware('permission:5.1')->group(function (): void {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    });

    Route::middleware('permission:5.2')->group(function (): void {
        Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    });

    Route::middleware('permission:6.1')->get('/attendance/corrections', [AttendanceController::class, 'corrections'])->name('attendance.corrections');
    Route::middleware('permission:6.2')->post('/attendance/corrections/{correction}/decide', [AttendanceController::class, 'decideCorrection'])->name('attendance.corrections.decide');

    Route::middleware('permission:8.1')->get('/incidents', [IncidentController::class, 'index'])->name('incidents');
    Route::middleware('permission:8.2')->group(function (): void {
        Route::get('/incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
        Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::post('/incidents/{incident}/transition', [IncidentController::class, 'transition'])->name('incidents.transition');
    });

    Route::middleware('role:1,2')->get('/cleaner-tools', [DashboardController::class, 'cleanerTools'])->name('cleaner-tools');

    Route::middleware('permission:2.1')->group(function (): void {
        Route::get('/personnel', [PersonnelController::class, 'index'])->name('personnel');
        Route::get('/personnel/create', [PersonnelController::class, 'create'])->name('personnel.create');
        Route::get('/personnel/{user}/edit', [PersonnelController::class, 'edit'])->name('personnel.edit');
        Route::get('/quick-login', [PersonnelController::class, 'quickLoginStation'])->name('quick-login');
        Route::post('/personnel/{user}/qr-token', [PersonnelController::class, 'qrToken'])->name('personnel.qr-token');
    });

    Route::middleware('permission:2.2')->post('/personnel', [PersonnelController::class, 'store'])->name('personnel.store');
    Route::middleware('permission:2.3')->group(function (): void {
        Route::put('/personnel/{user}', [PersonnelController::class, 'update'])->name('personnel.update');
        Route::post('/personnel/{user}/password', [PersonnelController::class, 'changePassword'])->name('personnel.password');
        Route::post('/personnel/{user}/toggle-active', [PersonnelController::class, 'toggleActive'])->name('personnel.toggle-active');
    });
    Route::middleware('permission:2.4')->delete('/personnel/{user}', [PersonnelController::class, 'destroy'])->name('personnel.destroy');

    Route::middleware('permission:2')->group(function (): void {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::get('/teams', [TeamController::class, 'index'])->name('teams');
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.store');
        Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');
    });

    Route::post('/user-preferences', [SettingsController::class, 'savePreference'])->name('user-preferences.store');

    Route::middleware('permission:1')->group(function (): void {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/users', [DashboardController::class, 'users'])->name('settings.users')->middleware('permission:1.1');
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions')->middleware('permission:1.2');
        Route::post('/permissions/{user}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('permission:1.2');
    });

    // Tenant Manager SaaS Platform Management (Admin / Super Admin)
    Route::middleware('role:0')->group(function (): void {
        Route::get('/tenant-manager', [TenantManagerController::class, 'index'])->name('tenant-manager.index');
        Route::post('/tenant-manager/tenants', [TenantManagerController::class, 'storeTenant'])->name('tenant-manager.tenants.store');
        Route::put('/tenant-manager/tenants/{tenant}', [TenantManagerController::class, 'updateTenant'])->name('tenant-manager.tenants.update');
        Route::post('/tenant-manager/tenants/{tenant}/toggle-status', [TenantManagerController::class, 'toggleStatus'])->name('tenant-manager.tenants.toggle-status');
        Route::post('/tenant-manager/packages', [TenantManagerController::class, 'storePackage'])->name('tenant-manager.packages.store');
        Route::put('/tenant-manager/packages/{package}', [TenantManagerController::class, 'updatePackage'])->name('tenant-manager.packages.update');
        Route::delete('/tenant-manager/packages/{package}', [TenantManagerController::class, 'destroyPackage'])->name('tenant-manager.packages.destroy');
        Route::post('/tenant-manager/coupons', [TenantManagerController::class, 'storeCoupon'])->name('tenant-manager.coupons.store');
        Route::post('/tenant-manager/coupons/{coupon}/toggle', [TenantManagerController::class, 'toggleCoupon'])->name('tenant-manager.coupons.toggle');
        Route::delete('/tenant-manager/coupons/{coupon}', [TenantManagerController::class, 'destroyCoupon'])->name('tenant-manager.coupons.destroy');
        Route::put('/tenant-manager/settings', [TenantManagerController::class, 'updateSettings'])->name('tenant-manager.settings.update');
        Route::post('/tenant-manager/queries', [TenantManagerController::class, 'storeQuery'])->name('tenant-manager.queries.store');
        Route::post('/tenant-manager/queries/{query}/status', [TenantManagerController::class, 'updateQueryStatus'])->name('tenant-manager.queries.status');
        Route::post('/tenant-manager/alerts/dispatch', [TenantManagerController::class, 'dispatchAlert'])->name('tenant-manager.alerts.dispatch');
        Route::post('/tenant-manager/alerts/scan', [TenantManagerController::class, 'scanAlerts'])->name('tenant-manager.alerts.scan');
        Route::post('/tenant-manager/invoices/{payment}/resend', [TenantManagerController::class, 'resendInvoice'])->name('tenant-manager.invoices.resend');
    });

    // Tenant Admin Billing & Upgrades (Stripe & PayPal)
    Route::get('/billing', [TenantBillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/validate-coupon', [TenantBillingController::class, 'validateCoupon'])->name('billing.validate-coupon');
    Route::post('/billing/stripe', [TenantBillingController::class, 'processStripe'])->name('billing.stripe');
    Route::post('/billing/paypal', [TenantBillingController::class, 'processPaypal'])->name('billing.paypal');
    Route::get('/billing/invoices/{payment}', [TenantBillingController::class, 'viewInvoice'])->name('billing.invoices.show');
    Route::post('/billing/invoices/{payment}/send', [TenantBillingController::class, 'sendInvoice'])->name('billing.invoices.send');

    // Tenant Queries / Support Hub (Two-way communications)
    Route::get('/queries', [TenantQueryController::class, 'index'])->name('tenant.queries.index');
    Route::post('/queries', [TenantQueryController::class, 'store'])->name('tenant.queries.store');
    Route::get('/queries/{query}', [TenantQueryController::class, 'show'])->name('tenant.queries.show');
    Route::post('/queries/{query}/reply', [TenantQueryController::class, 'reply'])->name('tenant.queries.reply');
    Route::post('/queries/{query}/resolve', [TenantQueryController::class, 'resolve'])->name('tenant.queries.resolve');

    // Tenant Automated Alerts & Notifications Hub
    Route::get('/alerts', [TenantAlertController::class, 'index'])->name('tenant.alerts.index');
    Route::post('/alerts/{alert}/read', [TenantAlertController::class, 'markRead'])->name('tenant.alerts.read');
    Route::post('/alerts/dismiss-all', [TenantAlertController::class, 'dismissAll'])->name('tenant.alerts.dismiss-all');
    Route::post('/alerts/scan', [TenantAlertController::class, 'scan'])->name('tenant.alerts.scan');

    // Customer / Client Invoicing (Tenant Panel -> Client)
    Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index'])->name('customer-invoices.index');
    Route::get('/customer-invoices/create', [CustomerInvoiceController::class, 'create'])->name('customer-invoices.create');
    Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store'])->name('customer-invoices.store');
    Route::get('/customer-invoices/{customerInvoice}', [CustomerInvoiceController::class, 'show'])->name('customer-invoices.show');
    Route::post('/customer-invoices/{customerInvoice}/send', [CustomerInvoiceController::class, 'send'])->name('customer-invoices.send');
    Route::post('/customer-invoices/{customerInvoice}/mark-paid', [CustomerInvoiceController::class, 'markPaid'])->name('customer-invoices.mark-paid');
    Route::delete('/customer-invoices/{customerInvoice}', [CustomerInvoiceController::class, 'destroy'])->name('customer-invoices.destroy');

    // Google Calendar & Google Sheets Sync Management
    Route::get('/integrations/google', [GoogleIntegrationController::class, 'index'])->name('integrations.google');
    Route::put('/integrations/google', [GoogleIntegrationController::class, 'update'])->name('integrations.google.update');
    Route::post('/integrations/google/calendar/sync', [GoogleIntegrationController::class, 'syncCalendar'])->name('integrations.google.calendar.sync');
    Route::post('/integrations/google/sheets/sync', [GoogleIntegrationController::class, 'syncSheets'])->name('integrations.google.sheets.sync');

    // Operational Manual & Quick Start Guides
    Route::get('/manual', [ManualController::class, 'index'])->name('manual.index');
});

// Public / Tokenized Google Feeds (Subscribed iCal & Live Google Sheets CSV)
Route::get('/integrations/google-calendar/feed/{token}', [GoogleIntegrationController::class, 'feedIcal'])->name('integrations.google.calendar.feed');
Route::get('/integrations/google-sheets/{token}/{type}.csv', [GoogleIntegrationController::class, 'feedCsv'])->name('integrations.google.sheets.feed');

// Combo: permission AND role on one route (middleware test fixture).
Route::get('/supervisor-only-approvals', [DashboardController::class, 'approvals'])
    ->middleware(['auth', 'permission:4.5', 'role:1']);

// Tenant scoped routes: /{prefix}, /{prefix}/tenant, /{prefix}/qr-login/{token}, /{prefix}/signup
Route::prefix('{tenant}')->where(['tenant' => '[a-zA-Z0-9_\-]+'])->middleware('tenant')->group(function (): void {
    Route::get('/', [DashboardController::class, 'tenantRoot'])->name('tenant.root');
    Route::get('/tenant', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/tenant', [TenantAuthController::class, 'login'])->name('tenant.login.submit');
    Route::get('/qr-login/{token}', [TenantAuthController::class, 'qrLogin'])->name('tenant.qr-login');
    Route::get('/signup', [TenantAuthController::class, 'showSignupForm'])->name('tenant.signup');
    Route::post('/signup', [TenantAuthController::class, 'signup'])->name('tenant.signup.submit');
});
