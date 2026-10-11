@extends('layouts.auth')

@section('title', 'Join ' . $tenant->name)
@section('auth-title-id', 'join-title')

@section('auth-form')
<div class="auth-card-head">
    <div class="auth-company-context"><span class="eyebrow">Join your cleaning team</span><span class="auth-workspace-badge">/{{ $tenant->slug }}</span></div>
    <h1 id="join-title" class="auth-card-title">Join {{ $tenant->name }}</h1>
    <p class="auth-card-intro">Create your crew account to view your shifts and keep track of your work.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">
        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('tenant.signup.submit', ['tenant' => $tenant->slug]) }}">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Full name</label>
        <div class="auth-input">
            <i class="bi bi-person" aria-hidden="true"></i>
            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Alex Smith" required autocomplete="name">
        </div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <div class="auth-input">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="alex@example.com" required autocomplete="email">
        </div>
    </div>

    <div class="mb-3">
        <label for="phone" class="form-label">Phone number (optional)</label>
        <div class="auth-input">
            <i class="bi bi-telephone" aria-hidden="true"></i>
            <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+64 21 000 0000" autocomplete="tel">
        </div>
    </div>

    @if ($branches->isNotEmpty())
        <div class="mb-3">
            <label for="branch_id" class="form-label">Branch / depot</label>
            <select id="branch_id" name="branch_id" class="form-select font-monospace small">
                <option value="">Select branch (optional)</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-sm-6">
            <label for="password" class="form-label">Password</label>
            <div class="auth-input">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Min. 8 chars" required autocomplete="new-password">
                <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="col-sm-6">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <div class="auth-input">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat" required autocomplete="new-password">
                <button type="button" class="auth-input-toggle" data-toggle-password aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>

    <button type="submit" class="btn w-100 auth-submit mt-2">
        <span>Join the crew</span>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>

    <p class="auth-switch-link">Already on the team? <a href="{{ route('tenant.login', ['tenant' => $tenant->slug]) }}">Sign in</a></p>
</form>
@endsection
