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
- [x] Public homepage revamp (2026-10-10): login-matched navy blueprint / blue identity, clearer cleaning-team positioning, interactive workflow preview, benefits, setup steps, integrations, active-catalog pricing, shared FAQ content, and workspace signup CTAs. Styles stay scoped to the public page in `components.css` and reuse `tokens.css`.
- [x] Public pricing metadata now follows active packages through `FrontPageContent`, preserving decimal prices and omitting offers for an empty catalog. Sample product records are explicitly labeled; signup copy reflects workspace creation followed by plan selection in billing.
- [x] Homepage responsive follow-up (2026-10-10): compact hero spacing and overview cards on short desktop viewports, natural headline wrapping, fluid tablet/mobile heading sizes, and a scoped document font size that prevents the shared admin mobile rule from shrinking homepage text and labels.
- [x] Homepage fluid-width follow-up (2026-10-10): removed the 1140px page cap and tablet preview cap. All public-page containers fill available width with a shared 16–64px responsive gutter; paragraph line lengths remain readable on wide screens.
- [x] Website/auth continuity (2026-10-11): shared public header and standalone layout for login, workspace registration, password recovery/reset, and company crew sign-in/signup. All six views now reuse the website hero grid, navy canvas, blue stage, typography, cyan actions, and 520px form containers. Administrator/crew guidance, website return links, canonical `/register` with `/signup` compatibility, and ScrubRoster branding are consistent. Fields, validation, remembered input, password controls, optional branches, badge-code sign-in, and dashboard redirects are preserved. Long button labels wrap on narrow screens; autofocus no longer scrolls headings away; registration restores the workspace URL preview after browser Back.
- [x] Homepage/auth simplification (2026-10-11): removed product screenshots, workflow demo tabs, screenshot modal, and their styles/handlers. The homepage now uses a centered headline, direct workspace/sign-in actions, and three concise feature cards. Shared public tokens use the field-operations navy/orange direction with dark text on orange actions, Archivo/IBM Plex Mono, blueprint panels, and near-sharp corners. All six auth screens share a navy brand panel and a plain white form side, with a focused header and home return link; mobile shows the form in a single column. Long registration forms keep the desktop brand copy visible near the top. Fixed the crew email placeholder's escaped Blade expression.
- [x] Full-screen desktop hero (2026-10-11): at 992px and above, the homepage hero fills the viewport below the navigation header. Flexible vertical spacing centers the headline/actions and places the workflow rail at the bottom; short-screen spacing remains compact and smaller layouts retain their natural content height.
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

- Full-screen hero: 16 viewport sizes from 320×568 to 3440×1440, including 992×600, 1440×600, 1536×730, and 1920×1080. All desktop hero bottoms align with the viewport within one pixel, both workspace actions remain visible, and no horizontal overflow occurs. `git diff --check` passed.
- Homepage/auth simplification: eight routes (homepage, six auth screens, and `/signup` alias) checked at 320×568, 375×812, 768×1024, 992×600, 1440×900, and 1920×1080 — all 48 combinations passed with no horizontal overflow, offscreen fields/actions, broken section anchors, or initial heading/header overlap. One h1 and real labels confirmed. Company pages used a real temporary tenant and optional branch in an isolated SQLite preview database on `127.0.0.1:18080`. Homepage-to-auth links, mobile section navigation, browser Back with custom slug preservation, password visibility, and badge-dialog Escape handling checked. Preview-only router/data stay outside the repository.
- Simplification regression checks: `FrontPageTest`, `AuthTest`, `MultiTenantTest`, and `PasswordResetTest` — 22 passed, 104 assertions. Blade compilation and `git diff --check` passed. The command-local `php84` wrapper uses this host's available PHP 8.5.11 and a temporary SQLite INI scan directory.
- Website/auth continuity: homepage plus six auth views reviewed across 320px, 375px, 768px, 992px, and 1440px widths, including 992×600 and 1440×600 short desktops. Final auth matrix: 42 page/viewport combinations passed with no horizontal overflow, clipped fields/button content, or initial header overlap; matching logo positions and login/register card dimensions; one visible h1 and labels on every input. Mobile navigation, homepage-section return links, browser Back, password visibility, custom slug preservation/preview, and server validation checked. Keyboard focus clears the sticky header; badge dialog keyboard controls and Escape work; sampled text/button/placeholder contrast is at least 4.76:1. Reduced-motion rules for animation, scrolling, and form transitions were checked in the loaded CSS. Company screens used temporary rendered Blade fixtures with an optional branch; live company routes were not exercised in the browser. No preview fixtures are retained in the repository.
- Auth regression checks: `FrontPageTest`, `AuthTest`, `MultiTenantTest`, and `PasswordResetTest` — 22 passed, 104 assertions. Blade compilation, controller Pint check, and `git diff --check` passed. This Windows host exposes PHP 8.5.11; checks used a command-local `php84` wrapper with SQLite enabled through a temporary INI scan directory for the test process, without changing the system PHP configuration.
- Homepage revamp: full suite — 187 passed, 1 skipped, 732 assertions (Firebase Admin SDK credential unavailable for the skipped test); targeted `FrontPageTest` — 2 passed, 13 assertions. Blade compilation and Pint passed.
- Homepage browser QA: 320px, 375px, 768px, and 1440px widths without horizontal overflow; mobile navigation closes after anchor selection and clears the sticky header, workflow tabs support keyboard selection, FAQ expands, and focus / preview-header contrast corrections verified. Local preview uses an isolated SQLite database; production has not been deployed.
- Responsive follow-up verified on `localhost:8000`: 17 viewport sizes from 320×568 to 2560×1440, including both sides of 576px, 768px, and 992px breakpoints. No horizontal overflow; the overview card and hero actions fit all tested desktop viewports, including 992×600 and the screenshot reproduction at 1536×694. Mobile menu and keyboard tab selection passed. Homepage feature tests: 2 passed, 13 assertions; Blade compilation passed.
- Fluid-width follow-up verified on `localhost:8000`: 15 viewport sizes from 320×568 to 2560×1440 with no horizontal overflow and preserved short-desktop preview fit. Container width follows available viewport width (1905px at a 1920px viewport), rather than remaining at 1140px. `git diff --check` passed.
- `php84 artisan test` — 178 passed (0 failed, 671 assertions).
- Multi-tenancy feature tests (`tests/Feature/MultiTenantTest.php`): 8 passed.
- SaaS & Billing feature tests (`tests/Feature/SaaSTenantManagerBillingAndSyncTest.php`): 8 passed.
- Front website publicly accessible and renders packages.
- Stripe & PayPal checkout flows verified with transactions persisted in database.
