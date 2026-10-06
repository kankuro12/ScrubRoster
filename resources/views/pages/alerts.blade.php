@extends('layouts.app')

@section('title', 'System Alerts & Notifications')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Action Row -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-bell me-1"></i> Tenant Notifications · Automated Alerts</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">System Alerts & Operations Notices</h1>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('tenant.alerts.scan') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary font-monospace btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Check Now
                </button>
            </form>
            @if($unreadCount > 0)
                <form action="{{ route('tenant.alerts.dismiss-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary font-monospace btn-sm">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success font-monospace d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Unread vs Total Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #ef4444 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Unread Alerts</span>
                <div class="h3 fw-bold font-archivo text-danger mb-0">{{ $unreadCount }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card p-3 border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-left: 4px solid #0284c7 !important; border-radius: var(--cw-radius-md, 8px);">
                <span class="text-secondary font-monospace small text-uppercase">Total Dispatched</span>
                <div class="h3 fw-bold font-archivo mb-0">{{ $alerts->total() }}</div>
            </div>
        </div>
    </div>

    <!-- Alerts Feed -->
    <div class="card border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 font-archivo"><i class="bi bi-list-stars me-1"></i> Notification Feed</h5>
        </div>
        <div class="list-group list-group-flush">
            @forelse($alerts as $alert)
                <div class="list-group-item p-3 p-md-4 {{ !$alert->is_read ? 'bg-light border-start border-4' : '' }}"
                     style="{{ !$alert->is_read ? ($alert->severity === 'danger' ? 'border-color: #ef4444 !important;' : ($alert->severity === 'warning' ? 'border-color: #f59e0b !important;' : 'border-color: #0284c7 !important;')) : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if($alert->severity === 'danger')
                                    <span class="badge bg-danger text-white font-monospace text-uppercase" style="font-size: 10px;">CRITICAL</span>
                                @elseif($alert->severity === 'warning')
                                    <span class="badge bg-warning text-dark font-monospace text-uppercase" style="font-size: 10px;">WARNING</span>
                                @else
                                    <span class="badge bg-info-subtle text-info font-monospace text-uppercase" style="font-size: 10px;">INFO</span>
                                @endif
                                
                                <h6 class="mb-0 fw-bold font-archivo {{ !$alert->is_read ? 'text-dark' : 'text-secondary' }}">{{ $alert->title }}</h6>
                                
                                @if(!$alert->is_read)
                                    <span class="badge bg-danger-subtle text-danger font-monospace" style="font-size: 10px;">NEW</span>
                                @endif
                            </div>
                            <p class="mb-2 text-secondary font-sans" style="font-size: 14px; line-height: 1.5;">{{ $alert->message }}</p>
                            <div class="d-flex flex-wrap align-items-center gap-3 font-monospace small text-muted">
                                <span><i class="bi bi-clock me-1"></i> {{ $alert->created_at->diffForHumans() }} ({{ $alert->created_at->format('M j, Y H:i') }})</span>
                                @if($alert->email_sent)
                                    <span class="text-success"><i class="bi bi-envelope-check me-1"></i> Emailed to admin</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2 align-items-end">
                            @if($alert->action_url)
                                <a href="{{ $alert->action_url }}" class="btn btn-sm btn-outline-primary font-monospace py-1 px-3">
                                    Take Action <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            @endif
                            @if(!$alert->is_read)
                                <form action="{{ route('tenant.alerts.read', $alert->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-link text-secondary font-monospace p-0 text-decoration-none" style="font-size: 12px;">
                                        <i class="bi bi-check2 me-1"></i> Mark as read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-5 text-center text-secondary">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 text-muted"></i>
                    No alerts or operational notices at this time. All systems and quotas are operating normally.
                </div>
            @endforelse
        </div>
        @if($alerts->hasPages())
            <div class="card-footer bg-transparent py-3">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
