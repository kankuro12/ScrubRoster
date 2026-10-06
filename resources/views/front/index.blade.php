<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ScrubRoster — Commercial Cleaning Workforce Dispatch SaaS</title>

    <!-- Core SEO Meta Tags -->
    <meta name="description" content="ScrubRoster is the all-in-one workforce dispatch platform for commercial cleaners. Automate schedules, depot QR check-in, GPS attendance, and payroll sync.">
    <meta name="keywords" content="ScrubRoster, commercial cleaning software, cleaning roster management, janitorial SaaS, cleaning workforce dispatch, QR badge kiosk login, google calendar shift sync, google sheets payroll sync, field service cleaning operations, cleaning contractor management">
    <meta name="author" content="ScrubRoster Technologies">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0b1329">
    <meta name="application-name" content="ScrubRoster">
    <meta name="apple-mobile-web-app-title" content="ScrubRoster">

    <!-- Open Graph / Facebook -->
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="website">
    <meta property="og:title" content="ScrubRoster — Commercial Cleaning Workforce Dispatch & Field Ops SaaS">
    <meta property="og:description" content="The all-in-one platform for commercial cleaning fleets: dedicated company portals, instant QR badge check-in, live Google Calendar & Sheets sync, and automated billing.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="ScrubRoster">
    <meta property="og:image" content="{{ asset('logo.jpg') }}">
    <meta property="og:image:secure_url" content="{{ asset('logo.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="ScrubRoster Commercial Cleaning Workforce Operations">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@ScrubRoster">
    <meta name="twitter:creator" content="@ScrubRoster">
    <meta name="twitter:title" content="ScrubRoster — Commercial Cleaning Workforce Dispatch SaaS">
    <meta name="twitter:description" content="Dedicated company portals, depot QR check-in, live Google Calendar & Sheets auto-sync, GPS geofencing, and automated billing.">
    <meta name="twitter:image" content="{{ asset('logo.jpg') }}">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {!! json_encode($schemaJsonLd ?? [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon-192x192.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ versioned_asset('css/tokens.css') }}" rel="stylesheet">
    <link href="{{ versioned_asset('css/components.css') }}" rel="stylesheet">
    <style>
        :root {
            --light-canvas: #f8fafc;
            --light-surface: #ffffff;
            --light-subtle: #f1f5f9;
            --light-border: #e2e8f0;
            --light-border-strong: #cbd5e1;
            --light-ink-900: #0b1329;
            --light-ink-800: #1e293b;
            --light-ink-600: #475569;
            --light-ink-500: #64748b;
            --light-orange: #ff5a1f;
            --light-orange-hover: #e04b16;
            --light-orange-deep: #c23b00;
            --light-orange-tint: rgba(255, 90, 31, 0.08);
            --light-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --light-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --light-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
        }

        body.front-body {
            background-color: var(--light-canvas);
            color: var(--light-ink-800);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Top Navigation — Compact & Sleek */
        .front-nav {
            background-color: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--light-border);
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            height: 56px;
            display: flex;
            align-items: center;
            padding: 0;
        }

        .front-brand {
            font-family: 'Archivo', sans-serif;
            font-weight: 900;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
            color: var(--light-ink-900);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            white-space: nowrap;
        }

        .front-brand .brand-badge {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.6rem;
            font-weight: 700;
            background: var(--light-orange-tint);
            color: var(--light-orange-deep);
            border: 1px solid rgba(255, 90, 31, 0.3);
            padding: 1px 5px;
            border-radius: 3px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .front-nav-link {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            color: var(--light-ink-600);
            text-decoration: none;
            padding: 0.35rem 0.6rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            font-weight: 600;
            white-space: nowrap;
            transition: color 0.15s ease;
        }

        .front-nav-link:hover {
            color: var(--light-orange-deep);
        }

        .front-nav .btn-safety-orange {
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            height: 34px;
            white-space: nowrap;
            line-height: 1;
        }

        .front-nav .btn-outline-front {
            padding: 0.35rem 0.85rem;
            font-size: 0.78rem;
            height: 34px;
            white-space: nowrap;
            line-height: 1;
        }

        /* Buttons */
        .btn-safety-orange {
            background-color: var(--light-orange);
            color: #0b1329;
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none;
            padding: 0.65rem 1.4rem;
            border-radius: 4px;
            transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 6px rgba(255, 90, 31, 0.25);
        }

        .btn-safety-orange:hover {
            background-color: var(--light-orange-hover);
            color: #0b1329;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(255, 90, 31, 0.35);
        }

        .btn-outline-front {
            border: 1px solid var(--light-border-strong);
            color: var(--light-ink-800);
            background-color: #ffffff;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.65rem 1.3rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: var(--light-shadow-sm);
        }

        .btn-outline-front:hover {
            border-color: var(--light-orange);
            color: var(--light-orange-deep);
            background-color: var(--light-orange-tint);
        }

        /* System Ticker Rail */
        .system-ticker {
            background-color: #f1f5f9;
            border-bottom: 1px solid var(--light-border);
            padding: 0.55rem 0;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            color: var(--light-ink-600);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .system-ticker .pulse-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 6px #10b981;
            margin-right: 6px;
        }

        /* Hero */
        .front-hero {
            position: relative;
            padding: 4.5rem 0 4rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--light-border);
        }

        .hero-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--light-orange-deep);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 1rem;
            display: inline-block;
            background: var(--light-orange-tint);
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid rgba(255, 90, 31, 0.2);
        }

        .hero-title {
            font-family: 'Archivo', sans-serif;
            font-weight: 900;
            font-size: clamp(2.3rem, 5vw, 3.8rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            color: var(--light-ink-900);
            margin-bottom: 1.25rem;
        }

        .hero-lead {
            font-size: 1.15rem;
            color: var(--light-ink-600);
            max-width: 680px;
            line-height: 1.6;
            margin-bottom: 2.25rem;
        }

        /* Hero Terminal Card */
        .hero-terminal-card {
            background: #ffffff;
            border: 1px solid var(--light-border);
            border-radius: 8px;
            box-shadow: var(--light-shadow-lg);
            padding: 1.25rem;
        }

        .hero-terminal-item {
            background: #f8fafc;
            border-left: 3px solid var(--light-orange);
            border: 1px solid var(--light-border);
            border-left-width: 4px;
            border-radius: 4px;
            padding: 0.85rem;
            margin-bottom: 0.75rem;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
        }

        /* Feature Cards */
        .feature-card {
            background-color: #ffffff;
            border: 1px solid var(--light-border);
            border-radius: 8px;
            padding: 1.75rem;
            height: 100%;
            transition: all 0.2s ease;
            box-shadow: var(--light-shadow-sm);
        }

        .feature-card:hover {
            border-color: var(--light-orange);
            transform: translateY(-2px);
            box-shadow: var(--light-shadow-md);
        }

        .feature-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            background: var(--light-orange-tint);
            border: 1px solid rgba(255, 90, 31, 0.25);
            color: var(--light-orange-deep);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .feature-title {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--light-ink-900);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.92rem;
            color: var(--light-ink-600);
            line-height: 1.55;
            margin: 0;
        }

        /* Pricing Cards */
        .pricing-card {
            background-color: #ffffff;
            border: 1px solid var(--light-border);
            border-radius: 8px;
            padding: 2.25rem 1.75rem;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: all 0.2s ease;
            box-shadow: var(--light-shadow-sm);
        }

        .pricing-card:hover {
            box-shadow: var(--light-shadow-md);
        }

        .pricing-card.popular {
            border: 2px solid var(--light-orange);
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(255, 90, 31, 0.15);
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--light-orange);
            color: #0b1329;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 4px 14px;
            border-radius: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(255, 90, 31, 0.3);
        }

        .pricing-plan-name {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--light-ink-900);
            margin-bottom: 0.25rem;
        }

        .pricing-price {
            font-family: 'Archivo', sans-serif;
            font-weight: 900;
            font-size: 2.6rem;
            color: var(--light-ink-900);
            line-height: 1;
            margin: 1rem 0 0.25rem;
        }

        .pricing-period {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.8rem;
            color: var(--light-ink-500);
            margin-bottom: 1.5rem;
        }

        .pricing-features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem;
            flex-grow: 1;
        }

        .pricing-features-list li {
            font-size: 0.9rem;
            color: var(--light-ink-800);
            margin-bottom: 0.65rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .pricing-features-list li i {
            color: #10b981;
            font-size: 1rem;
            margin-top: 2px;
        }

        /* Step Card */
        .step-card {
            background: #ffffff;
            border: 1px solid var(--light-border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            height: 100%;
            box-shadow: var(--light-shadow-sm);
        }

        .step-num {
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--light-orange-deep);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        /* Footer */
        .front-footer {
            background-color: #0b1329;
            color: #94a3b8;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3.5rem 0 2rem;
            font-size: 0.88rem;
        }
    </style>
</head>
<body class="front-body">

    <!-- Top Navigation Bar — 56px Compact -->
    <nav class="front-nav" aria-label="Marketing Navigation">
        <div class="container d-flex flex-nowrap justify-content-between align-items-center h-100">
            <a href="{{ route('root') }}" class="front-brand">
                <img src="{{ asset('logo.jpg') }}" alt="ScrubRoster logo" style="height: 30px; width: 30px; border-radius: 6px; object-fit: contain;">
                <span>ScrubRoster</span>
                <span class="brand-badge">Field-Ops SaaS</span>
            </a>

            <div class="d-none d-lg-flex align-items-center gap-1 flex-nowrap">
                <a href="#features" class="front-nav-link">Features</a>
                <a href="#sync" class="front-nav-link">Google Sync</a>
                <a href="#pricing" class="front-nav-link">Pricing</a>
                <a href="#faq" class="front-nav-link">FAQ</a>
                <a href="#how-it-works" class="front-nav-link">How It Works</a>
                <a href="{{ route('login') }}" class="front-nav-link">Staff Login</a>
            </div>

            <div class="d-flex align-items-center gap-2 flex-nowrap">
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="btn-safety-orange">
                        <i class="bi bi-speedometer2"></i> Operations Console
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-outline-front d-none d-sm-inline-flex">
                        <i class="bi bi-box-arrow-in-right"></i> Log In
                    </a>
                    <a href="{{ route('register') }}" class="btn-safety-orange">
                        <i class="bi bi-rocket-takeoff"></i> Free Trial
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- System Status Rail -->
    <div class="system-ticker">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="pulse-dot"></span>
                <span>SYSTEM STATUS: <strong class="text-dark">OPERATIONAL (99.98% UPTIME)</strong></span>
            </div>
            <div>
                <span>DEDICATED FLEET PORTALS</span> &bull; 
                <span>GOOGLE CALENDAR & SHEETS LIVE SYNC</span> &bull; 
                <span>INSTANT ACTIVATION</span>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="front-hero text-center text-lg-start">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="hero-eyebrow">
                        <i class="bi bi-shield-check me-1"></i> Commercial Cleaning Fleet Management
                    </span>
                    <h1 class="hero-title">
                        Workforce dispatch, live attendance & cleaning operations.
                    </h1>
                    <p class="hero-lead">
                        Run commercial cleaning and turnover fleets with total clarity. Featuring dedicated company portals, GPS attendance verification, depot QR badge kiosks, and automatic real-time sync with Google Calendar & Google Sheets.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="{{ route('register') }}" class="btn-safety-orange btn-lg px-4 py-3">
                            <i class="bi bi-rocket-takeoff"></i> Start Free 14-Day Trial
                        </a>
                        <a href="{{ route('login') }}" class="btn-outline-front btn-lg px-4 py-3">
                            <i class="bi bi-box-arrow-in-right"></i> Operations Sign In
                        </a>
                    </div>
                    <div class="mt-4 pt-2 d-flex flex-wrap align-items-center gap-4 text-muted small font-monospace">
                        <div><i class="bi bi-check-circle-fill text-success me-1"></i> 14-day free trial</div>
                        <div><i class="bi bi-check-circle-fill text-success me-1"></i> No credit card required</div>
                        <div><i class="bi bi-check-circle-fill text-success me-1"></i> Custom company portal</div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-terminal-card" style="box-shadow: var(--light-shadow-md); border-color: var(--light-border-strong);">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <span class="badge font-monospace text-uppercase" style="background: rgba(255,90,31,0.12); color: var(--light-orange-deep); border: 1px solid rgba(255,90,31,0.25);">Live Operations Feed</span>
                            <span class="text-success small font-monospace fw-bold"><i class="bi bi-record-circle me-1"></i> Live</span>
                        </div>
                        <div class="hero-terminal-item" style="border-left-color: var(--light-orange) !important;">
                            <div class="text-dark fw-bold"><i class="bi bi-qr-code text-primary me-1"></i> Crew Check-In Verified:</div>
                            <div class="text-secondary small">Cleaner Alex Rivera clocked in at Downtown Plaza via QR Badge (On-Time).</div>
                        </div>
                        <div class="hero-terminal-item" style="border-left-color: #3b82f6 !important;">
                            <div class="text-dark fw-bold"><i class="bi bi-calendar-check text-info me-1"></i> Calendar Schedules Synced:</div>
                            <div class="text-secondary small">24 cleaning shifts updated across Google Calendar and mobile devices.</div>
                        </div>
                        <div class="hero-terminal-item" style="border-left-color: #10b981 !important;">
                            <div class="text-dark fw-bold"><i class="bi bi-file-earmark-spreadsheet text-success me-1"></i> Timesheets Exported:</div>
                            <div class="text-secondary small">Daily hours and verified attendance automatically updated in Google Sheets.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="hero-eyebrow">Enterprise Fleet Capabilities</span>
                <h2 class="display-6 fw-bold text-dark font-archivo">Engineered for Commercial Operations</h2>
                <p class="text-secondary mx-auto" style="max-width: 600px;">
                    ScrubRoster replaces fragmented spreadsheets and chat groups with a unified, battle-tested field dispatch system.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-buildings"></i>
                        </div>
                        <h3 class="feature-title">Dedicated Company Portals</h3>
                        <p class="feature-desc">
                            Give your cleaning business a branded private workspace. Multiple cleaning contractors can manage their independent teams smoothly, while cleaners can work across contractors without email or login conflicts.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h3 class="feature-title">Quick QR Badge Sign-In</h3>
                        <p class="feature-desc">
                            Crews clock in at depot tablet kiosks or on mobile using one-tap QR badges. Fast, frictionless, passwordless sign-in gets your cleaners straight onto their shifts.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-calendar2-range"></i>
                        </div>
                        <h3 class="feature-title">Live Google Calendar Sync</h3>
                        <p class="feature-desc">
                            Connect shifts directly to Google Calendar, Apple Calendar, and Outlook. Any shift reassignments or schedule updates immediately reflect on worker phones.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                        </div>
                        <h3 class="feature-title">Automated Spreadsheet Reports</h3>
                        <p class="feature-desc">
                            Seamlessly flow attendance records, completed work checklists, and payroll hours straight into Google Sheets without tedious manual data entry.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h3 class="feature-title">GPS Geofencing & Photo Proof</h3>
                        <p class="feature-desc">
                            Verify cleaner arrival on site with accurate GPS location checks. Capture mandatory before-and-after photos for flawless quality assurance and client sign-off.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-credit-card-2-front"></i>
                        </div>
                        <h3 class="feature-title">Flexible Subscription Plans</h3>
                        <p class="feature-desc">
                            Choose a package tailored to your cleaner count. Upgrade anytime, apply promotional discounts, and check out securely via credit card or PayPal.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Google Integrations Spotlight -->
    <section class="py-5" id="sync" style="background-color: #ffffff; border-top: 1px solid var(--light-border); border-bottom: 1px solid var(--light-border);">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="hero-eyebrow"><i class="bi bi-clouds me-1"></i> Seamless Integrations</span>
                    <h2 class="display-6 fw-bold text-dark font-archivo">Google Calendar & Google Sheets Sync</h2>
                    <p class="text-secondary">
                        Stay connected with zero duplicate data entry. ScrubRoster automatically keeps your external calendar and spreadsheet records aligned in real time.
                    </p>
                    <div class="mt-4">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="text-warning fs-4"><i class="bi bi-calendar-check text-primary"></i></div>
                            <div>
                                <h5 class="text-dark mb-1 fw-bold">Live Calendar Sync</h5>
                                <p class="text-secondary small mb-0">Connect your master schedule to Google Calendar. Shift updates, client notes, and site addresses sync straight to field cleaner devices.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="text-warning fs-4"><i class="bi bi-table text-success"></i></div>
                            <div>
                                <h5 class="text-dark mb-1 fw-bold">Real-Time Google Sheets Sync</h5>
                                <p class="text-secondary small mb-0">Keep your timesheets and payroll reports up to date in Google Sheets automatically. Zero manual export/import steps required.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card p-4 shadow-sm" style="background: #ffffff; border: 1px solid var(--light-border); border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase font-monospace text-dark fw-bold mb-0">
                                <i class="bi bi-table text-success me-1"></i> Live Google Sheets Preview
                            </h6>
                            <span class="badge bg-success-subtle text-success font-monospace fw-bold">Live Sync: Active</span>
                        </div>
                        <div class="table-responsive rounded border mb-3" style="font-size: 0.82rem; background: #ffffff;">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light font-monospace small">
                                    <tr>
                                        <th>Cleaner</th>
                                        <th>Site Location</th>
                                        <th>Shift Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-dark">Alex Rivera</td>
                                        <td>Downtown Highrise</td>
                                        <td>08:00 – 12:00</td>
                                        <td><span class="badge bg-success-subtle text-success">Verified</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-dark">Maria Gomez</td>
                                        <td>West End Medical</td>
                                        <td>08:30 – 14:00</td>
                                        <td><span class="badge bg-primary-subtle text-primary">On Site</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-dark">James Chen</td>
                                        <td>Tech Campus Bldg B</td>
                                        <td>09:00 – 17:00</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">Scheduled</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-secondary small mb-3">
                            Attendance records, hours worked, and task completions sync directly into your spreadsheet for effortless payroll and billing.
                        </p>
                        <hr class="my-2" style="border-color: var(--light-border);">
                        <div class="d-flex justify-content-between align-items-center small text-secondary">
                            <span><i class="bi bi-arrow-repeat text-primary me-1"></i> Updates automatically</span>
                            <span class="font-monospace">Zero manual data entry</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Packages Section -->
    <section class="py-5" id="pricing">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="hero-eyebrow">Predictable Subscriptions</span>
                <h2 class="display-6 fw-bold text-dark font-archivo">Packages Built for Every Fleet Size</h2>
                <p class="text-secondary mx-auto" style="max-width: 600px;">
                    Scale your cleaning business with packages tailored by employee count. All plans include full access to the ScrubRoster field-ops platform and a 14-day free trial.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach($packages as $package)
                    <div class="col-md-6 col-lg-4">
                        <div class="pricing-card {{ $package->is_popular ? 'popular' : '' }}">
                            @if($package->is_popular)
                                <div class="popular-badge">Most Popular Fleet Tier</div>
                            @endif

                            <div class="pricing-plan-name">{{ $package->name }}</div>
                            <p class="text-secondary small mb-0">{{ $package->description }}</p>

                            <div class="pricing-price">
                                ${{ number_format($package->price_monthly, 0) }}
                            </div>
                            <div class="pricing-period">
                                / month &bull; or ${{ number_format($package->price_yearly, 0) }} / year
                            </div>

                            <div class="p-2 mb-3 rounded font-monospace text-center small" style="background: var(--light-subtle); border: 1px solid var(--light-border);">
                                <span class="text-dark fw-bold"><i class="bi bi-people-fill text-primary me-1"></i> Up to {{ $package->max_employees }} Employees</span>
                            </div>

                            <ul class="pricing-features-list">
                                @if(is_array($package->features))
                                    @foreach($package->features as $feature)
                                        <li><i class="bi bi-check2"></i> {{ $feature }}</li>
                                    @endforeach
                                @else
                                    <li><i class="bi bi-check2"></i> Up to {{ $package->max_employees }} Active Cleaners</li>
                                    <li><i class="bi bi-check2"></i> Depot QR Scan Kiosk</li>
                                    <li><i class="bi bi-check2"></i> Google Calendar Sync</li>
                                    <li><i class="bi bi-check2"></i> Google Sheets Live Sync</li>
                                @endif
                                <li><i class="bi bi-check2"></i> {{ $package->trial_days }}-Day Free Trial Included</li>
                            </ul>

                            <a href="{{ route('register') }}?package={{ $package->id }}" class="btn-safety-orange w-100 justify-content-center py-2">
                                Start Free Trial ({{ $package->trial_days }} Days)
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 text-center p-3 rounded" style="background: #ffffff; border: 1px solid var(--light-border); box-shadow: var(--light-shadow-sm); max-width: 600px; margin: 0 auto;">
                <span class="text-danger font-monospace small fw-bold"><i class="bi bi-tag-fill me-1"></i> Promotional Codes Available:</span>
                <span class="text-secondary small ms-2">Enter coupon <code>SCRUB20</code> or <code>OPS50</code> at checkout for instant discounts.</span>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5" id="how-it-works" style="background-color: #ffffff; border-top: 1px solid var(--light-border);">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="hero-eyebrow">Fast Onboarding</span>
                <h2 class="display-6 fw-bold text-dark font-archivo">Get Field Ops Running in 4 Steps</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">01</div>
                        <h5 class="text-dark fw-bold">Create Your Workspace</h5>
                        <p class="text-secondary small mb-0">Register your company in seconds. Get a dedicated portal link for your team with instant manager access.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">02</div>
                        <h5 class="text-dark fw-bold">Issue Crew QR Badges</h5>
                        <p class="text-secondary small mb-0">Add your cleaners and generate 1-click QR badges for instant depot kiosk check-in with zero passwords to remember.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">03</div>
                        <h5 class="text-dark fw-bold">Connect Calendar & Sheets</h5>
                        <p class="text-secondary small mb-0">Keep shifts synced to Google Calendar and automatically update live attendance logs in Google Sheets.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-num">04</div>
                        <h5 class="text-dark fw-bold">Dispatch & Verify</h5>
                        <p class="text-secondary small mb-0">Schedule cleaning shifts, verify GPS arrivals, review photo proof of work, and approve payroll with confidence.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section for SEO & Rich Snippets -->
    <section class="py-5" id="faq" style="background-color: var(--light-canvas); border-top: 1px solid var(--light-border);">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="hero-eyebrow"><i class="bi bi-question-circle me-1"></i> Frequently Asked Questions</span>
                <h2 class="display-6 fw-bold text-dark font-archivo">Everything You Need to Know About ScrubRoster</h2>
                <p class="text-secondary mx-auto" style="max-width: 620px;">
                    Find quick answers to common questions about commercial cleaning workforce management, QR badge check-in, and calendar integrations.
                </p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion" id="scrubFaqAccordion">
                        <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden" style="border-color: var(--light-border) !important;">
                            <h3 class="accordion-header" id="faqHead1">
                                <button class="accordion-button fw-bold text-dark font-archivo" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1" style="background-color: #ffffff;">
                                    How does ScrubRoster help commercial cleaning companies?
                                </button>
                            </h3>
                            <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHead1" data-bs-parent="#scrubFaqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    ScrubRoster provides an all-in-one workforce operations platform built specifically for commercial cleaning contractors, facilities managers, and turnover fleets. Each company gets a private, branded workspace to schedule shifts, dispatch crews, verify GPS attendance, and manage payroll with ease.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden" style="border-color: var(--light-border) !important;">
                            <h3 class="accordion-header" id="faqHead2">
                                <button class="accordion-button fw-bold text-dark font-archivo collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2" style="background-color: #ffffff;">
                                    How does QR badge check-in work for cleaners?
                                </button>
                            </h3>
                            <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHead2" data-bs-parent="#scrubFaqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    Managers can generate digital or printable QR badges for each crew member. Cleaners simply scan their badge at a depot tablet kiosk or on a mobile device to clock in instantly for their shift—no passwords or typing required.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden" style="border-color: var(--light-border) !important;">
                            <h3 class="accordion-header" id="faqHead3">
                                <button class="accordion-button fw-bold text-dark font-archivo collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3" style="background-color: #ffffff;">
                                    Can cleaners work for multiple cleaning contractors using the same email?
                                </button>
                            </h3>
                            <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHead3" data-bs-parent="#scrubFaqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    Yes. Cleaners can take shifts with multiple cleaning contractors using a single email address while keeping each contractor’s client data, rates, and shifts strictly private and secure.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden" style="border-color: var(--light-border) !important;">
                            <h3 class="accordion-header" id="faqHead4">
                                <button class="accordion-button fw-bold text-dark font-archivo collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4" style="background-color: #ffffff;">
                                    How does calendar and spreadsheet synchronization work?
                                </button>
                            </h3>
                            <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHead4" data-bs-parent="#scrubFaqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    Cleaning shifts automatically sync to worker and client calendars (Google Calendar, Apple, Outlook). Attendance hours and completed task records also sync live into Google Sheets for effortless payroll without manual data entry.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden" style="border-color: var(--light-border) !important;">
                            <h3 class="accordion-header" id="faqHead5">
                                <button class="accordion-button fw-bold text-dark font-archivo collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5" style="background-color: #ffffff;">
                                    What payment options are available for subscription plans?
                                </button>
                            </h3>
                            <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHead5" data-bs-parent="#scrubFaqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    We accept all major credit and debit cards via Stripe as well as PayPal, with instant workspace activation and flexible monthly or yearly billing.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="front-footer">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-5">
                    <a href="{{ route('root') }}" class="front-brand text-white mb-2">
                        <img src="{{ asset('logo.jpg') }}" alt="ScrubRoster logo" style="height: 32px; width: 32px; border-radius: 6px; object-fit: contain;">
                        <span>ScrubRoster</span>
                    </a>
                    <p class="text-secondary small mt-2" style="max-width: 340px;">
                        The modern workforce management platform for commercial cleaning contractors, facilities management, and turnover fleets.
                    </p>
                </div>
                <div class="col-md-2 col-6">
                    <h6 class="text-white font-monospace text-uppercase small mb-3">Product</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#features" class="text-secondary text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="#pricing" class="text-secondary text-decoration-none">Pricing</a></li>
                        <li class="mb-2"><a href="#sync" class="text-secondary text-decoration-none">Google Sync</a></li>
                        <li class="mb-2"><a href="#faq" class="text-secondary text-decoration-none">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-2 col-6">
                    <h6 class="text-white font-monospace text-uppercase small mb-3">Portals</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('login') }}" class="text-secondary text-decoration-none">Manager & Crew Sign In</a></li>
                        <li class="mb-2"><a href="{{ route('register') }}" class="text-secondary text-decoration-none">Start Free Company Trial</a></li>
                        <li class="mb-2"><a href="{{ route('dashboard') }}" class="text-secondary text-decoration-none">Operations Console</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="text-white font-monospace text-uppercase small mb-3">Security & Billing</h6>
                    <p class="small text-secondary mb-2">
                        <i class="bi bi-lock-fill text-success me-1"></i> Powered by Stripe & PayPal
                    </p>
                    <p class="small text-secondary">
                        <i class="bi bi-shield-check text-info me-1"></i> Bank-Grade 256-Bit Data Security
                    </p>
                </div>
            </div>

            <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center font-monospace small" style="border-color: rgba(255,255,255,0.1) !important;">
                <div>&copy; {{ date('Y') }} ScrubRoster Operations SaaS. All rights reserved.</div>
                <div>Built for commercial cleaners, janitorial fleets & facility teams.</div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
