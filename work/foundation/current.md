# Foundation — Current

## Done

- [x] RBAC core: `config/permissions.php` (roles 0/1/2, dotted keys `1`–`9`, wildcard `*`).
- [x] `permission` + `role` middleware (`EnsurePermission`, `EnsureRole`) registered in `bootstrap/app.php`.
- [x] `User` model: role column (tinyint, default 2), `hasPermission/hasAnyPermission/hasRole/hasAnyRole`.
- [x] Migration `2026_08_04_000000_add_role_to_users_table`.
- [x] `.env` + app key generated; sqlite db created + migrated.
- [x] Auth: login/logout (`LoginController`), login page, `guest` redirect for root.
- [x] Forgot/reset password: `ForgotPasswordController`, `ResetPasswordController`, views, routes, tests (PasswordResetTest).
- [x] Sanctum v4.3 installed, config + migration published, `personal_access_tokens` migrated.
- [x] Design system baseline: `layouts/app.blade.php` (Bootstrap 5 + jQuery + Axios CDN), `public/css/tokens.css`, `public/css/components.css`, dashboard + page stubs.
- [x] Routes converted to controller actions (AGENTS.md convention), demo protected groups preserved.
- [x] Audit: `audit_logs` migration, `AuditLog` model, `AuditLogger` service (context: actor/ip/device/source/request_id), `Auditable` trait, `config/audit.php`, login/logout entries.
- [x] Seeders: `AdminUserSeeder` (env creds), `DatabaseSeeder` (admin + supervisor + cleaner), seeded.
- [x] Docs: `docs/permission-matrix.md`, `docs/design-tokens/tokens.json`.
- [x] CI: `.github/workflows/ci.yml` (pint + migrate + test).
- [x] `.env.example` — `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `AUDIT_ENABLED`, `AUDIT_QUEUE`.
- [x] Pint clean; 27 tests green (`AuthTest`, `AuditTest`, `PasswordResetTest`, `PermissionMiddlewareTest`).
- [x] Multi-tenancy core: `tenants` and `qr_login_tokens` migrations, `TenantContext`, `TenantScope`, and `BelongsToTenant` trait applied across all domain models.
- [x] Multi-tenant routes and prefix routing: `/{prefix}` dashboard, `/{prefix}/tenant` login, `/{prefix}/join` employee self-signup, and root `/register` company self-signup.
- [x] Multi-guard setup: `tenant_admin`, `tenant_manager`, `employee`, and `web` guards configured in `config/auth.php`.
- [x] Quick QR scan login: depot QR code station (`quickLoginStation`), instant passwordless personnel QR authentication (`qrLogin`), personnel management QR token generation.
- [x] Tenant-scoped user uniqueness: composite unique indexes `['tenant_id', 'email']` and `['tenant_id', 'employee_no']`, allowing same email/employee_no across distinct tenants without conflict.

- [x] Front marketing website: dedicated high-conversion public landing page (`resources/views/front/index.blade.php`, `FrontController`), dynamic packages pricing grid, features showcase, and trial CTA.
- [x] Tenant Manager portal (`tenant-manager.index`): platform admin page to manage tenants (edit status, extend trials, employee overrides), subscription packages (`Package`), promotion coupons (`Coupon`), and global free trial / sync settings.
- [x] Tenant Admin Billing & Upgrades (`billing.index`): Stripe & PayPal dual payment gateway checkout, employee count quota tracking, real-time AJAX coupon validation (`CLEANWAY20`, `OPS50`), and automated invoice generation.
- [x] Google Calendar & Google Sheets Sync (`GoogleSyncService`, `GoogleIntegrationController`): automated and manual sync, private `.ics` subscribed feed, live `=IMPORTDATA(...)` CSV feeds for attendance, tasks, and cleaner roster.
- [x] Contextual Operations Manual: interactive quick-start guide docked directly onto the main operations dashboard and full standalone documentation manual (`manual.index`).

## In Progress

- Nothing.

## Next

1. Queue/scheduler: wire background recurring sync runners if needed.
2. Storage: S3 disk config when evidence/images module lands.
3. API stabilization: Sanctum token issuance and Ionic mobile client integration.

## Verified

- `php84 artisan test` — 178 passed (0 failed, 671 assertions).
- Multi-tenancy feature tests (`tests/Feature/MultiTenantTest.php`): 8 passed.
- SaaS & Billing feature tests (`tests/Feature/SaaSTenantManagerBillingAndSyncTest.php`): 8 passed.
- Front website publicly accessible and renders packages.
- Stripe & PayPal checkout flows verified with transactions persisted in database.