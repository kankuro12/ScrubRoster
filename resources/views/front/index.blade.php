<!DOCTYPE html>
<html lang="en" class="front-document">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ScrubRoster — Scheduling &amp; Attendance for Cleaning Teams</title>
    <meta name="description" content="Schedule cleaning teams, verify attendance with GPS and QR check-in, and review completed work. One workspace for your commercial cleaning business.">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0F172A">
    <link rel="canonical" href="{{ route('root') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="ScrubRoster — Cleaning teams, on the same page.">
    <meta property="og:description" content="Scheduling, location-aware attendance, and proof of work in one company workspace.">
    <meta property="og:url" content="{{ route('root') }}">
    <meta property="og:site_name" content="ScrubRoster">
    <meta property="og:image" content="{{ asset('favicon-512x512.png') }}">
    <meta property="og:image:alt" content="ScrubRoster logo">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="ScrubRoster — Cleaning team operations">
    <meta name="twitter:description" content="Schedule crews, verify attendance, and review completed work in one workspace.">
    <meta name="twitter:image" content="{{ asset('favicon-512x512.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-192x192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ versioned_asset('css/tokens.css') }}" rel="stylesheet">
    <link href="{{ versioned_asset('css/components.css') }}" rel="stylesheet">
    <script type="application/ld+json">{!! json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</head>
<body class="front-body">
    <a class="front-skip-link" href="#main-content">Skip to content</a>
    @include('partials.public-header')

    <main id="main-content">
        <section class="front-hero" aria-labelledby="hero-title">
            <div class="container">
                <div class="front-hero-copy reveal">
                    <span class="eyebrow front-eyebrow-on-dark">For commercial cleaning teams</span>
                    <h1 id="hero-title">Cleaning teams,<span>on the same page.</span></h1>
                    <p class="front-hero-lead">Give every cleaner a clear plan. See who has arrived, follow the work, and review the result — all in your company workspace.</p>
                    <div class="front-hero-actions">
                        <a class="btn front-btn-bright" href="{{ $currentUser ? route('dashboard') : route('register') }}">{{ $currentUser ? 'Open your workspace' : 'Create your workspace' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <a class="front-text-link front-link-on-dark" href="{{ route('login') }}">Sign in to your workspace <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    </div>
                    <p class="front-hero-note">Your company. Your crew. Your workspace.</p>
                </div>
                <div class="front-hero-rail" aria-label="Cleaning workflow">
                    <span><b>01</b> Plan the shift</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    <span><b>02</b> Confirm arrival</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    <span><b>03</b> Review the work</span>
                </div>
            </div>
        </section>

        <section class="front-section front-features-section" id="features" aria-labelledby="features-title">
            <div class="container">
                <div class="front-section-head"><div><span class="eyebrow">Less chasing. More clarity.</span><h2 id="features-title">A better handle on the day.</h2></div><p>The essentials for keeping your cleaning operation moving, together in one workspace.</p></div>
                <div class="front-feature-grid">
                    <article class="card front-feature-card reveal">
                        <div class="card-header"><span>01 / Scheduling</span><i class="bi bi-calendar3" aria-hidden="true"></i></div>
                        <div class="card-body"><h3>A clear plan for every shift.</h3><p>Assign the right crew to each site. Keep recurring visits and the week’s work on one schedule.</p></div>
                    </article>
                    <article class="card front-feature-card reveal front-reveal-second">
                        <div class="card-header"><span>02 / Attendance</span><i class="bi bi-geo-alt" aria-hidden="true"></i></div>
                        <div class="card-body"><h3>Know who has arrived.</h3><p>Follow clock-ins with location records and depot QR check-in, without chasing the team for updates.</p></div>
                    </article>
                    <article class="card front-feature-card reveal front-reveal-third">
                        <div class="card-header"><span>03 / Proof of work</span><i class="bi bi-check2-square" aria-hidden="true"></i></div>
                        <div class="card-body"><h3>Close the loop on every job.</h3><p>Review checklists and photo evidence. Approve completed work or send it back with a clear correction.</p></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="front-section front-setup-section" id="how-it-works" aria-labelledby="setup-title">
            <div class="container front-setup-grid">
                <div class="front-setup-intro"><span class="eyebrow">Make it your operation</span><h2 id="setup-title">A workspace<br>for your way<br>of working.</h2><p>Keep your company’s people, sites, and jobs together. Managers coordinate the day; cleaners know what’s next.</p><a class="front-text-link" href="{{ route('register') }}">Set up your workspace <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
                <div>
                    <ol class="front-setup-list">
                        <li><span class="front-setup-number" aria-hidden="true">01</span><div><h3>Bring in your team and sites.</h3><p>Add your cleaners, managers, and the places you service.</p></div></li>
                        <li><span class="front-setup-number" aria-hidden="true">02</span><div><h3>Build the schedule.</h3><p>Set up tasks, assign the crew, and plan recurring visits.</p></div></li>
                        <li><span class="front-setup-number" aria-hidden="true">03</span><div><h3>Follow the work through.</h3><p>Check attendance, review submitted tasks, and keep a record of the result.</p></div></li>
                    </ol>
                    <div class="front-connections" id="sync"><span class="eyebrow">Fits into your working day</span><p><i class="bi bi-calendar3" aria-hidden="true"></i> Calendar feeds <span aria-hidden="true">/</span> <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Google Sheets</p><p class="front-connections-note">Connect schedules and workspace records to your existing tools. Available connections depend on your plan; connected apps control their refresh times.</p></div>
                </div>
            </div>
        </section>

        <section class="front-section" id="pricing" aria-labelledby="pricing-title">
            <div class="container">
                <div class="front-section-head"><div><span class="eyebrow">Plans for your crew</span><h2 id="pricing-title">Start with your team.<br>Grow from there.</h2></div><p>Choose a plan for the number of employees you manage. Prices are in USD, with monthly and yearly options.</p></div>
                <div class="row g-4 front-pricing-grid">
                    @forelse($packages as $package)
                        <div class="col-md-6 col-lg-4">
                            <article class="card front-pricing-card {{ $package->is_popular ? 'front-pricing-featured' : '' }}">
                                <div class="card-body">
                                    <div class="front-plan-top"><span class="eyebrow">Up to {{ $package->max_employees }} employees</span>@if($package->is_popular)<span class="front-plan-featured-label">Featured</span>@endif</div>
                                    <div class="front-plan-intro"><h3>{{ $package->name }}</h3><p>{{ $package->description }}</p></div>
                                    <div class="front-plan-price"><strong>{{ $package->formattedMonthlyPrice() }}</strong><span>/ month</span></div>
                                    <p class="front-plan-yearly">{{ $package->formattedYearlyPrice() }} billed yearly</p>
                                    <ul class="front-plan-features">
                                        @foreach($package->features ?? [] as $feature)
                                            <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                                        @endforeach
                                    </ul>
                                    <div class="front-plan-action"><p>{{ $package->trial_days > 0 ? $package->trial_days.'-day free trial' : 'Subscription plan' }}</p><a class="btn {{ $package->is_popular ? 'front-btn-primary' : 'front-btn-outline' }} w-100" href="{{ route('register') }}">Create workspace <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                                </div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12"><div class="card"><div class="card-body front-pricing-empty"><h3>Plans are being updated.</h3><p>You can still create your company workspace and check the billing area for plan updates.</p><a class="btn front-btn-primary" href="{{ route('register') }}">Create workspace <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></div></div>
                    @endforelse
                </div>
                <p class="front-pricing-note">Create your workspace first. Choose your plan in billing when you’re ready.</p>
            </div>
        </section>

        <section class="front-section front-faq-section" id="faq" aria-labelledby="faq-title">
            <div class="container front-faq-grid">
                <div><span class="eyebrow">Good to know</span><h2 id="faq-title">A few answers<br>before you start.</h2></div>
                <div class="accordion front-faq" id="front-faq">
                    @foreach($faqs as $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faq-heading-{{ $loop->index }}"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-answer-{{ $loop->index }}" aria-expanded="false" aria-controls="faq-answer-{{ $loop->index }}">{{ $faq['question'] }}</button></h3>
                            <div id="faq-answer-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="faq-heading-{{ $loop->index }}" data-bs-parent="#front-faq"><div class="accordion-body">{{ $faq['answer'] }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="front-final-cta" aria-labelledby="cta-title">
            <div class="container"><div><span class="eyebrow front-eyebrow-on-dark">Ready for the next shift</span><h2 id="cta-title">Give the day<br>a clear plan.</h2></div><div class="front-final-actions"><a class="btn front-btn-bright" href="{{ $currentUser ? route('dashboard') : route('register') }}">{{ $currentUser ? 'Open your workspace' : 'Create your workspace' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a><a class="front-link-on-dark" href="{{ route('login') }}">Already using ScrubRoster? Sign in</a></div></div>
        </section>
    </main>

    <footer class="front-footer">
        <div class="container">
            <div class="front-footer-top">
                <div><a class="front-brand" href="{{ route('root') }}"><img src="{{ asset('logo.jpg') }}" width="40" height="40" alt=""><span><span class="front-brand-name">ScrubRoster</span><span class="front-brand-tag">Field operations</span></span></a><p>Scheduling, attendance, and proof of work<br>for commercial cleaning teams.</p></div>
                <nav aria-label="Footer product navigation"><span class="eyebrow">Explore</span><a href="#features">Features</a><a href="#how-it-works">How it works</a><a href="#sync">Integrations</a><a href="#pricing">Plans &amp; pricing</a></nav>
                <nav aria-label="Footer workspace navigation"><span class="eyebrow">Your workspace</span><a href="{{ route('login') }}">Sign in</a><a href="{{ route('register') }}">Create company workspace</a><a href="#faq">Common questions</a></nav>
            </div>
            <div class="front-footer-bottom"><span>&copy; {{ date('Y') }} ScrubRoster. All rights reserved.</span><span>Built for the work in the field.</span></div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
    <script>
        (function ($) {
            $('#front-navigation a[href^="#"]').on('click', function (event) {
                if ($('.front-menu-toggle').is(':visible')) {
                    event.preventDefault();
                    var target = $(this).attr('href');
                    var $navigation = $('#front-navigation');
                    var collapse = bootstrap.Collapse.getOrCreateInstance($navigation[0], { toggle: false });
                    $navigation.one('hidden.bs.collapse', function () {
                        window.location.hash = target;
                        $(target)[0].scrollIntoView({ behavior: 'instant', block: 'start' });
                    });
                    if ($navigation.hasClass('collapsing') && $('.front-menu-toggle').attr('aria-expanded') === 'true') {
                        $navigation.one('shown.bs.collapse', function () { collapse.hide(); });
                    } else {
                        collapse.hide();
                    }
                }
            });
        })(jQuery);
    </script>
</body>
</html>
