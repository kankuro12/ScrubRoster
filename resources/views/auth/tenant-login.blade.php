@extends('layouts.auth')

@section('title', $tenant->name . ' sign in')
@section('auth-title-id', 'tenant-login-title')

@section('auth-form')
<div class="auth-card-head">
    <div class="auth-company-context"><span class="eyebrow">Your company portal</span><span class="auth-workspace-badge">/{{ $tenant->slug }}</span></div>
    <h1 id="tenant-login-title" class="auth-card-title">Sign in to {{ $tenant->name }}</h1>
    <p class="auth-card-intro">Your team's schedule, site check-ins, and completed work, together.</p>
</div>

<div class="auth-qr-prompt">
    <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
    <div><strong>Have a crew badge?</strong><p>Scan it with your camera or enter your code.</p></div>
    <button type="button" class="btn front-btn-outline" data-bs-toggle="modal" data-bs-target="#qrInputModal">Enter code</button>
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
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ 'crew@'.$tenant->slug.'.com' }}" required autocomplete="email">
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
            <input type="checkbox" id="remember" name="remember" class="form-check-input" @checked(old('remember'))>
            <label for="remember" class="form-check-label small">Remember me</label>
        </div>
        <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
    </div>

    <button type="submit" class="btn w-100 auth-submit">
        <span>Sign in to {{ $tenant->name }}</span>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>

    <p class="auth-switch-link">New to this team? <a href="{{ route('tenant.signup', ['tenant' => $tenant->slug]) }}">Join the crew</a></p>
</form>
@endsection

@push('auth-overlays')
<div class="modal fade" id="qrInputModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-monospace" id="qrModalLabel"><i class="bi bi-qr-code me-2" aria-hidden="true"></i>Sign in with your badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">If you scanned your QR code or received a quick login token from your supervisor, enter the code below:</p>
                <div class="mb-3">
                    <label for="tokenInput" class="form-label small fw-bold">Badge code</label>
                    <input type="text" id="tokenInput" class="form-control font-monospace" placeholder="Paste or type code...">
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn auth-submit" id="btnGoToken">Sign in</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('auth-scripts')
<script>
    (function ($) {
        $('#btnGoToken').on('click', function () {
            var token = $('#tokenInput').val().trim();
            if (token) {
                window.location.href = "{{ url($tenant->slug . '/qr-login') }}/" + encodeURIComponent(token);
            }
        });
    })(jQuery);
</script>
@endpush
