@php
    $onHomepage = request()->routeIs('root');
    $currentUser = $currentUser ?? auth()->user();
@endphp
<header class="front-header">
    <nav class="navbar navbar-expand-lg front-nav" aria-label="Main navigation">
        <div class="container">
            <a class="front-brand" href="{{ route('root') }}" aria-label="ScrubRoster home">
                <span class="front-brand-mark" aria-hidden="true">
                    <img src="{{ asset('logo.jpg') }}" width="1024" height="1024" alt="">
                </span>
                <span><span class="front-brand-name">ScrubRoster</span><span class="front-brand-tag">Field operations</span></span>
            </a>
            <div class="front-nav-actions order-lg-3">
                @if($currentUser)
                    <a class="btn front-btn-primary d-none d-md-inline-flex" href="{{ route('dashboard') }}">Open workspace <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                @else
                    <a class="front-signin" href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Sign in <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    <a class="btn front-btn-primary d-none d-md-inline-flex" href="{{ route('register') }}" @if(request()->routeIs('register', 'signup')) aria-current="page" @endif>Create workspace</a>
                @endif
                @if($onHomepage)
                <button class="navbar-toggler front-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#front-navigation" aria-controls="front-navigation" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                @endif
            </div>
            @if($onHomepage)
            <div class="collapse navbar-collapse order-lg-2" id="front-navigation">
                <ul class="navbar-nav mx-lg-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $onHomepage ? '#how-it-works' : route('root').'#how-it-works' }}">How it works</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $onHomepage ? '#pricing' : route('root').'#pricing' }}">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $onHomepage ? '#faq' : route('root').'#faq' }}">FAQ</a></li>
                </ul>
                @if($currentUser)
                    <a class="btn front-btn-primary d-md-none front-mobile-trial" href="{{ route('dashboard') }}">Open workspace <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                @else
                    <a class="btn front-btn-primary d-md-none front-mobile-trial" href="{{ route('register') }}" @if(request()->routeIs('register', 'signup')) aria-current="page" @endif>Create workspace <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                @endif
            </div>
            @endif
        </div>
    </nav>
</header>
