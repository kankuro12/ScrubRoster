<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email — ScrubRoster Ops</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ versioned_asset('css/tokens.css') }}" rel="stylesheet">
    <link href="{{ versioned_asset('css/components.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell">
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
            <div class="auth-panel-sub">ScrubRoster Ops · account security</div>
            <h1 class="auth-panel-title">
                Verify your email.<br>
                Secure your<br>
                <span class="accent">workspace.</span>
            </h1>
        </aside>

        <main class="auth-form-side">
            <div class="auth-card">
                <section class="auth-form-panel" aria-labelledby="verify-title">
                    <div class="auth-card-head">
                        <span class="eyebrow">Account · Email verification</span>
                        <h2 id="verify-title" class="auth-card-title">Verify your email</h2>
                        <p class="auth-card-intro">
                            We sent a verification link to
                            <strong>{{ auth()->user()?->email }}</strong>.
                            Click the link in that email to confirm your address.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success auth-alert py-2" role="status">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger auth-alert py-2" role="alert">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <p class="small text-muted mb-3">
                        Didn't get it? Check spam, or request a new link below.
                    </p>

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn w-100 auth-submit">
                            <span>Resend verification email</span>
                            <i class="bi bi-envelope-arrow-up" aria-hidden="true"></i>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-link w-100 small text-decoration-none">
                            Sign out
                        </button>
                    </form>
                </section>
                <p class="auth-footer eyebrow">ScrubRoster Ops · self-serve enterprise tenancy</p>
            </div>
        </main>
    </div>
</body>
</html>
