<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join {{ $tenant->name }} — CleanWay Ops</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.jpg') }}">
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
        <aside class="auth-panel d-none d-lg-flex" aria-label="Product information">
            <div class="auth-brand">
                <span class="auth-brand-mark" aria-hidden="true">
                    <img src="{{ asset('logo.jpg') }}" alt="CleanWay logo">
                </span>
                <span class="auth-brand-text">
                    <span class="auth-brand-name">{{ $tenant->name }}</span>
                    <span class="auth-brand-tag">Field Ops</span>
                </span>
            </div>

            <div class="auth-panel-sub">CleanWay Ops · crew onboarding</div>

            <h1 class="auth-panel-title">
                Join the crew at<br>
                {{ $tenant->name }}.<br>
                <span class="accent">Start your shifts.</span>
            </h1>

            <ul class="auth-panel-points">
                <li><i class="bi bi-calendar-check" aria-hidden="true"></i><span>View daily schedules & assignments</span></li>
                <li><i class="bi bi-geo-alt" aria-hidden="true"></i><span>GPS geofenced site check-in</span></li>
                <li><i class="bi bi-camera" aria-hidden="true"></i><span>Photo checklist & evidence upload</span></li>
            </ul>
        </aside>

        <main class="auth-form-side">
            <div class="auth-card" style="max-width: 500px;">
                <section class="auth-form-panel" aria-labelledby="join-title">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="eyebrow">Crew Onboarding</span>
                        <span class="badge bg-dark border text-warning font-monospace" style="font-size: 11px;">/{{ $tenant->slug }}</span>
                    </div>

                    <div class="auth-card-head">
                        <h2 id="join-title" class="auth-card-title">Join {{ $tenant->name }}</h2>
                        <p class="auth-card-intro">Register as an employee/cleaner for this company.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger auth-alert py-2" role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.signup.submit', ['tenant' => $tenant->slug]) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="auth-input">
                                <i class="bi bi-person" aria-hidden="true"></i>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Alex Smith" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="auth-input">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="alex@example.com" required autocomplete="email">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="auth-input">
                                <i class="bi bi-telephone" aria-hidden="true"></i>
                                <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+64 21 000 0000">
                            </div>
                        </div>

                        @if ($branches->isNotEmpty())
                            <div class="mb-3">
                                <label for="branch_id" class="form-label">Branch / Depot</label>
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
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="auth-input">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 auth-submit mt-2">
                            <span>Register & Join Crew</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>

                        <div class="text-center mt-3 pt-2">
                            <span class="small text-muted">Already registered?</span>
                            <a href="{{ route('tenant.login', ['tenant' => $tenant->slug]) }}" class="small ms-1 text-decoration-none"><strong>Sign in here</strong></a>
                        </div>
                    </form>
                </section>

                <p class="auth-footer eyebrow">Workspace: /{{ $tenant->slug }} · CleanWay Ops</p>
            </div>
        </main>
    </div>
</body>
</html>
