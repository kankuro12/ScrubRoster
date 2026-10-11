@extends('layouts.auth')

@section('title', 'Sign in')
@section('auth-title-id', 'login-title')

@section('auth-form')
<div class="auth-card-head">
    <span class="eyebrow">Your company workspace</span>
    <h1 id="login-title" class="auth-card-title">Sign in to ScrubRoster</h1>
    <p class="auth-card-intro">Company administrators, use your work account to continue.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">
        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <div class="auth-input">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@company.com" required autocomplete="email">
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="auth-input">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Your password" required autocomplete="current-password">
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
        <span>Sign in</span>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>

    <p class="auth-switch-link">New to ScrubRoster? <a href="{{ route('register') }}">Create your workspace</a></p>
</form>

<div class="auth-crew-note">
    <i class="bi bi-person-badge" aria-hidden="true"></i>
    <p><strong>Cleaner or supervisor?</strong> Use the company sign-in link provided by your administrator.</p>
</div>
@endsection
