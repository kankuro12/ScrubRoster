@extends('layouts.auth')

@section('title', 'Reset password')
@section('auth-title-id', 'forgot-title')

@section('auth-form')
<div class="auth-card-head">
    <span class="eyebrow">Get back to your workspace</span>
    <h1 id="forgot-title" class="auth-card-title">Reset your password</h1>
    <p class="auth-card-intro">Enter your work email and we'll send you a link to choose a new password.</p>
</div>

@if (session('status'))
    <div class="alert alert-success auth-alert py-2" role="alert">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Work email address</label>
        <div class="auth-input">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@company.com" required autocomplete="email">
        </div>
    </div>
    <button type="submit" class="btn w-100 auth-submit">Send reset link<i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>

<p class="auth-switch-link"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
