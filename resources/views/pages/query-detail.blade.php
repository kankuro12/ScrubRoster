@extends('layouts.app')

@section('title', 'Query #' . $query->id . ' - ' . $query->subject)

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <a href="{{ route('tenant.queries.index') }}" class="btn btn-outline-secondary btn-sm font-monospace mb-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Inquiries
            </a>
            <span class="eyebrow d-block"><i class="bi bi-chat-quote me-1"></i> Inquiry Reference #{{ $query->id }}</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">{{ $query->subject }}</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($query->status !== 'resolved' && $query->status !== 'closed')
                <form action="{{ route('tenant.queries.resolve', $query->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-success font-monospace btn-sm" onclick="return confirm('Mark this inquiry as resolved?')">
                        <i class="bi bi-check-circle me-1"></i> Mark Resolved
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

    <div class="row g-4">
        <!-- Main Conversation Stream -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 font-archivo"><i class="bi bi-chat-text me-1"></i> Conversation Thread</h5>
                    <span class="badge bg-secondary font-monospace">{{ $query->messages->count() }} Messages</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        @foreach($query->messages as $msg)
                            <div class="p-3 rounded {{ $msg->sender_type === 'super_admin' ? 'border border-primary border-opacity-50' : 'border border-secondary border-opacity-25' }}"
                                 style="background: {{ $msg->sender_type === 'super_admin' ? 'rgba(2, 132, 199, 0.05)' : 'var(--cw-surface-tint, #f8fafc)' }};">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong class="font-archivo {{ $msg->sender_type === 'super_admin' ? 'text-primary' : 'text-dark' }}">
                                            {{ $msg->sender?->name ?? ($msg->sender_type === 'super_admin' ? 'Platform Administrator' : 'Tenant Admin') }}
                                        </strong>
                                        <span class="badge {{ $msg->sender_type === 'super_admin' ? 'bg-primary' : 'bg-secondary' }} font-monospace ms-2 text-uppercase" style="font-size: 10px;">
                                            {{ $msg->sender_type === 'super_admin' ? 'Platform Admin' : 'Workspace Admin' }}
                                        </span>
                                    </div>
                                    <span class="text-secondary font-monospace small">
                                        {{ $msg->created_at->format('M j, Y H:i') }} ({{ $msg->created_at->diffForHumans() }})
                                    </span>
                                </div>
                                <div class="text-body" style="white-space: pre-wrap; line-height: 1.6;">{{ $msg->message }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Reply Composer Box -->
                @if($query->status !== 'closed')
                    <div class="card-footer bg-light p-4 border-top">
                        <h6 class="font-archivo fw-bold mb-3"><i class="bi bi-reply-fill text-warning me-1"></i> Post Your Response</h6>
                        <form action="{{ route('tenant.queries.reply', $query->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <textarea name="message" rows="4" class="form-control" placeholder="Write your response to the platform administrator..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary small font-monospace">
                                    <i class="bi bi-info-circle me-1"></i> Your reply will notify platform administrators immediately via email & in-app.
                                </span>
                                <button type="submit" class="btn btn-warning font-monospace fw-bold">
                                    <i class="bi bi-send me-1"></i> Send Response
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="card-footer bg-light p-3 text-center text-muted font-monospace small">
                        This inquiry has been closed. If you have additional questions, please open a new inquiry.
                    </div>
                @endif
            </div>
        </div>

        <!-- Meta Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
                <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10">
                    <h5 class="card-title mb-0 font-archivo"><i class="bi bi-info-circle me-1"></i> Inquiry Details</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush font-monospace small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Reference ID:</span>
                            <strong>#{{ $query->id }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Status:</span>
                            @if($query->status === 'resolved' || $query->status === 'closed')
                                <span class="badge bg-success-subtle text-success">{{ strtoupper($query->status) }}</span>
                            @elseif($query->status === 'waiting_response')
                                <span class="badge bg-danger-subtle text-danger">ACTION REQUIRED</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary">{{ strtoupper(str_replace('_', ' ', $query->status)) }}</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Category:</span>
                            <span class="badge bg-secondary text-uppercase">{{ $query->category }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Priority:</span>
                            <span class="fw-bold text-uppercase">{{ $query->priority }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Initiated By:</span>
                            <span>{{ $query->creator?->name ?? 'Administrator' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Created:</span>
                            <span>{{ $query->created_at->format('M j, Y') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-secondary">Last Active:</span>
                            <span>{{ $query->last_message_at ? $query->last_message_at->diffForHumans() : $query->created_at->diffForHumans() }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
