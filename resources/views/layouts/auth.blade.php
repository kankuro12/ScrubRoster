<!DOCTYPE html>
<html lang="en" class="front-document public-auth-document">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — ScrubRoster</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-192x192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ versioned_asset('css/tokens.css') }}" rel="stylesheet">
    <link href="{{ versioned_asset('css/components.css') }}" rel="stylesheet">
</head>
<body class="front-body public-auth-body">
    <a class="front-skip-link" href="#main-content">Skip to content</a>
    @include('partials.public-header')

    <main id="main-content" class="public-auth-main">
        <aside class="public-auth-copy d-none d-lg-flex" aria-label="About ScrubRoster">
            <div class="public-auth-copy-content reveal">
                <span class="eyebrow front-eyebrow-on-dark">For commercial cleaning teams</span>
                <p class="public-auth-brand-title">Cleaning teams,<span>on the same page.</span></p>
                <p class="front-hero-lead">Your people, your sites, and the day’s work. One place to keep it all moving.</p>
                <ul class="public-auth-points">
                    <li><i class="bi bi-calendar3" aria-hidden="true"></i><span>A clear plan for every shift</span></li>
                    <li><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Know who has arrived</span></li>
                    <li><i class="bi bi-check2-square" aria-hidden="true"></i><span>Keep a record of the work</span></li>
                </ul>
                <p class="public-auth-signoff"><span>Built for the work in the field.</span><span>ScrubRoster / Field operations</span></p>
            </div>
        </aside>

        <div class="public-auth-form-stage">
            <div class="auth-card reveal">
                <a class="auth-back-link" href="{{ route('root') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to home</a>
                <section class="auth-form-panel" aria-labelledby="@yield('auth-title-id')">
                    @yield('auth-form')
                </section>
                <footer class="auth-footer">Scheduling, attendance, and proof of work.</footer>
            </div>
        </div>
    </main>
    @stack('auth-overlays')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
    <script>
        (function ($) {
            $('[data-toggle-password]').on('click', function () {
                var $button = $(this);
                var $input = $button.closest('.auth-input').find('input');
                var show = $input.attr('type') === 'password';

                $input.attr('type', show ? 'text' : 'password');
                $button.attr('aria-label', show ? 'Hide password' : 'Show password');
                $button.find('i').toggleClass('bi-eye', !show).toggleClass('bi-eye-slash', show);
            });
        })(jQuery);
    </script>
    @stack('auth-scripts')
</body>
</html>
