@extends('layouts.auth')

@section('title', 'Create your workspace')
@section('auth-title-id', 'register-title')

@section('auth-form')
<div class="auth-card-head">
    <span class="eyebrow">Your company workspace</span>
    <h1 id="register-title" class="auth-card-title">Create your workspace</h1>
    <p class="auth-card-intro">Bring your cleaning team, sites, and schedule together.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">
        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('register.submit') }}">
    @csrf

    <div class="mb-3">
        <label for="company_name" class="form-label">Company name</label>
        <div class="auth-input">
            <i class="bi bi-building" aria-hidden="true"></i>
            <input type="text" id="company_name" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="Acme Cleaning Services" required autocomplete="organization">
        </div>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Workspace address</label>
        <div class="input-group auth-workspace-address">
            <span class="input-group-text" aria-hidden="true">/</span>
            <input type="text" id="slug" name="slug" class="form-control mono @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="acme" required pattern="[a-zA-Z0-9_\-]+" aria-describedby="slug-help">
        </div>
        <div id="slug-help" class="form-text small">
            Your team's sign-in link: <code id="slug-preview" class="mono">/your-prefix/tenant</code>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-12">
            <label for="name" class="form-label">Your full name</label>
            <div class="auth-input">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Your name" required autocomplete="name">
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Work email address</label>
        <div class="auth-input">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@company.com" required autocomplete="email">
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-sm-6">
            <label for="password" class="form-label">Password</label>
            <div class="auth-input">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="8+ characters" required autocomplete="new-password" aria-describedby="password-help">
                <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="col-sm-6">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <div class="auth-input">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
                <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>

    <p id="password-help" class="form-text">Use at least 8 characters for your password.</p>

    <button type="submit" class="btn w-100 auth-submit">
        <span>Create workspace &amp; sign in</span>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>

    <p class="auth-switch-link">Already have a workspace? <a href="{{ route('login') }}">Sign in</a></p>
</form>
@endsection

@push('auth-scripts')
    <script>
        (function ($) {
            $('#slug').data('customized', Boolean($('#slug').val()));
            updatePreview($('#slug').val());

            $(window).on('pageshow', function () {
                // History can restore form values after the initial script runs.
                window.setTimeout(function () {
                    $('#slug').data('customized', Boolean($('#slug').val()));
                    updatePreview($('#slug').val());
                }, 0);
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
@endpush
