<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->name }} — ScrubRoster Ops Portal</title>
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
                    <span class="auth-brand-name">{{ $tenant->name }}</span>
                    <span class="auth-brand-tag">Workspace Portal</span>
                </span>
            </div>

            <div class="auth-panel-sub">ScrubRoster Ops · /{{ $tenant->slug }}</div>

            <h1 class="auth-panel-title">
                {{ $tenant->name }}<br>
                Field Crews.<br>
                <span class="accent">Ready for dispatch.</span>
            </h1>

            <ul class="auth-panel-points">
                <li><i class="bi bi-person-badge" aria-hidden="true"></i><span>Supervisors, cleaners & dispatch</span></li>
                <li><i class="bi bi-qr-code-scan" aria-hidden="true"></i><span>Instant QR badge sign-in</span></li>
                <li><i class="bi bi-clock-history" aria-hidden="true"></i><span>Real-time shift & task attendance</span></li>
            </ul>

            <div class="mt-auto pt-4 border-top border-secondary border-opacity-25 small text-muted">
                <span class="font-monospace text-uppercase small text-light">Company Prefix: /{{ $tenant->slug }}</span>
            </div>
        </aside>

        {{-- Sign-in form --}}
        <main class="auth-form-side">
            <div class="auth-card">
                <section class="auth-form-panel" aria-labelledby="login-title">
                    <div class="auth-brand d-lg-none mb-3">
                        <span class="auth-brand-mark" aria-hidden="true">
                            <img src="{{ asset('logo.jpg') }}" alt="">
                        </span>
                        <span class="auth-brand-text">
                            <span class="auth-brand-name">{{ $tenant->name }}</span>
                            <span class="auth-brand-tag">Ops Portal</span>
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="eyebrow">{{ $tenant->name }}</span>
                        <span class="badge bg-dark border text-warning font-monospace" style="font-size: 11px;">/{{ $tenant->slug }}</span>
                    </div>

                    <div class="auth-card-head">
                        <h2 id="login-title" class="auth-card-title">Staff Sign In</h2>
                        <p class="auth-card-intro">Crew members, managers, and administrators.</p>
                    </div>

                    {{-- Fast QR Scan Option Banner --}}
                    <div class="alert alert-secondary d-flex align-items-center justify-content-between p-2 mb-3 border" style="background: rgba(255,255,255,0.03);">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-qr-code-scan fs-4 text-warning me-2"></i>
                            <div>
                                <div class="fw-bold small">Have a Crew QR Badge?</div>
                                <div class="text-muted" style="font-size: 12px;">Scan with your camera or enter code</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#qrInputModal">
                            Quick Code
                        </button>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.login.submit', ['tenant' => $tenant->slug]) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <div class="auth-input">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="crew@{{ $tenant->slug }}.com" required autofocus autocomplete="email">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="auth-input">
                                <i class="bi bi-lock" aria-hidden="true"></i>
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required autocomplete="current-password">
                                <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="auth-form-meta">
                            <div class="form-check">
                                <input type="checkbox" id="remember" name="remember" class="form-check-input">
                                <label for="remember" class="form-check-label small">Remember me</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn w-100 auth-submit">
                            <span>Sign in to {{ $tenant->name }}</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>

                        <div class="text-center mt-3 pt-2">
                            <span class="small text-muted">New cleaner on this team?</span>
                            <a href="{{ route('tenant.signup', ['tenant' => $tenant->slug]) }}" class="small ms-1 text-decoration-none">
                                <strong>Join crew</strong>
                            </a>
                        </div>
                    </form>
                </section>

                <p class="auth-footer eyebrow">Workspace: /{{ $tenant->slug }} · ScrubRoster Ops</p>
            </div>
        </main>
    </div>

    {{-- Quick QR Token Modal --}}
    <div class="modal fade" id="qrInputModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-monospace" id="qrModalLabel"><i class="bi bi-qr-code me-2"></i>Quick Login via Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">If you scanned your QR code or received a quick login token from your supervisor, enter the code below:</p>
                    <div class="mb-3">
                        <label for="tokenInput" class="form-label small fw-bold">Token / Badge Code</label>
                        <input type="text" id="tokenInput" class="form-control font-monospace" placeholder="Paste or type code...">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning btn-sm" id="btnGoToken">Instant Sign In</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

            $('#btnGoToken').on('click', function() {
                var token = $('#tokenInput').val().trim();
                if (token) {
                    window.location.href = "{{ url($tenant->slug . '/qr-login') }}/" + encodeURIComponent(token);
                }
            });
        })(jQuery);
    </script>
</body>
</html>
