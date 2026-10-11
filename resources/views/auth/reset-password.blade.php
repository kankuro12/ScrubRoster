@extends('layouts.auth')

@section('title', 'Choose a new password')
@section('auth-title-id', 'reset-title')

@section('auth-form')
<div class="auth-card-head">
    <span class="eyebrow">Get back to your workspace</span>
    <h1 id="reset-title" class="auth-card-title">Choose a new password</h1>
    <p class="auth-card-intro">Use at least 8 characters to keep your workspace secure.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label for="email" class="form-label">Work email address</label>
        <div class="auth-input">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control" value="{{ $email }}" required readonly autocomplete="email">
        </div>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">New password</label>
        <div class="auth-input">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="8+ characters" required autocomplete="new-password">
            <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
        </div>
    </div>
    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <div class="auth-input">
            <i class="bi bi-lock-fill" aria-hidden="true"></i>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
            <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
        </div>
    </div>
    <button type="submit" class="btn w-100 auth-submit">Reset password<i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>

<p class="auth-switch-link"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
