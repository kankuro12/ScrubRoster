<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Company Workspace — ScrubRoster Ops</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon-192x192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ versioned_asset('css/tokens.css') }}" rel="stylesheet">
    <link href="{{ versioned_asset('css/components.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell">
        {{-- Branded operations panel (lg+) --}}
        <aside class="auth-panel d-none d-lg-flex" aria-label="Product information">
            <div class="auth-brand">
                <span class="auth-brand-mark" aria-hidden="true">
                    <img src="{{ asset('logo.jpg') }}" alt="ScrubRoster logo">
                </span>
                <span class="auth-brand-text">
                    <span class="auth-brand-name">ScrubRoster</span>
                    <span class="auth-brand-tag">Multi-Tenant Ops</span>
                </span>
            </div>

            <div class="auth-panel-sub">ScrubRoster Ops · enterprise onboarding</div>

            <h1 class="auth-panel-title">
                Deploy your crew.<br>
                Instant workspace.<br>
                <span class="accent">Zero friction.</span>
            </h1>

            <ul class="auth-panel-points">
                <li><i class="bi bi-diagram-3" aria-hidden="true"></i><span>Dedicated tenant prefix URL</span></li>
                <li><i class="bi bi-qr-code-scan" aria-hidden="true"></i><span>No-nonsense crew QR login</span></li>
                <li><i class="bi bi-shield-lock" aria-hidden="true"></i><span>Isolated tenant data & multi-guard auth</span></li>
            </ul>
        </aside>

        {{-- Registration form --}}
        <main class="auth-form-side">
            <div class="auth-card" style="max-width: 520px;">
                <section class="auth-form-panel" aria-labelledby="register-title">
                    <div class="auth-brand d-lg-none mb-4">
                        <span class="auth-brand-mark" aria-hidden="true">
                            <img src="{{ asset('logo.jpg') }}" alt="">
                        </span>
                        <span class="auth-brand-text">
                            <span class="auth-brand-name">ScrubRoster</span>
                            <span class="auth-brand-tag">Ops</span>
                        </span>
                    </div>

                    <div class="auth-card-head">
                        <span class="eyebrow">Self Signup · Instant Setup</span>
                        <h2 id="register-title" class="auth-card-title">Register Company</h2>
                        <p class="auth-card-intro">Create your company's dedicated ScrubRoster workspace.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('signup.submit') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company / Organization Name</label>
                            <div class="auth-input">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <input type="text" id="company_name" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="Acme Cleaning Services" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Workspace URL Prefix</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted font-monospace small" id="url-prefix-addon">/</span>
                                <input type="text" id="slug" name="slug" class="form-control font-monospace @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="acme" required pattern="[a-zA-Z0-9_\-]+" aria-describedby="slug-help">
                            </div>
                            <div id="slug-help" class="form-text small">
                                Your crew will sign in at: <code id="slug-preview" class="text-primary font-monospace">/your-prefix/tenant</code>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label for="name" class="form-label">Administrator Full Name</label>
                                <div class="auth-input">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Sarah Connor" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Admin Email Address</label>
                            <div class="auth-input">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="sarah@acmepms.com" required autocomplete="email">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="auth-input">
                                    <i class="bi bi-lock" aria-hidden="true"></i>
                                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Min. 8 characters" required autocomplete="new-password">
                                    <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="auth-input">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 auth-submit mt-2">
                            <span>Create Workspace & Sign In</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>

                        <div class="text-center mt-3 pt-2">
                            <span class="small text-muted">Already have a workspace?</span>
                            <a href="{{ route('login') }}" class="small ms-1 text-decoration-none"><strong>Sign in here</strong></a>
                        </div>
                    </form>
                </section>

                <p class="auth-footer eyebrow">ScrubRoster Ops · self-serve enterprise tenancy</p>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        (function ($) {
            $('[data-toggle-password]').on('click', function () {
                var $button = $(this);
                var $input = $button.closest('.auth-input').find('input');
                var show = $input.attr('type') === 'password';

                $input.attr('type', show ? 'text' : 'password');
                $button.attr('aria-label', show ? 'Hide password' : 'Show password');
                $button.find('i')
                    .toggleClass('bi-eye', !show)
                    .toggleClass('bi-eye-slash', show);
            });

            // Auto-slugify company name
            $('#company_name').on('input', function () {
                if (!$('#slug').data('customized')) {
                    var slug = $(this).val().toLowerCase()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    $('#slug').val(slug);
                    updatePreview(slug);
                }
            });

            $('#slug').on('input', function () {
                $(this).data('customized', true);
                var slug = $(this).val().toLowerCase().replace(/[^a-z0-9_-]/g, '');
                $(this).val(slug);
                updatePreview(slug);
            });

            function updatePreview(slug) {
                $('#slug-preview').text(slug ? '/' + slug + '/tenant' : '/your-prefix/tenant');
            }
        })(jQuery);
    </script>
</body>
</html>
