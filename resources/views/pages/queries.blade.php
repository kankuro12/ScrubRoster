@extends('layouts.app')

@section('title', 'Support & Administrative Inquiries')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">

    <!-- Header & Action Row -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="eyebrow"><i class="bi bi-question-circle me-1"></i> Tenant Operations · Communications Hub</span>
            <h1 class="h3 fw-bold mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);">Inquiries & Administrative Queries</h1>
        </div>
        <div>
            <button type="button" class="btn btn-warning font-monospace fw-bold" data-bs-toggle="modal" data-bs-target="#newQueryModal">
                <i class="bi bi-plus-circle me-1"></i> Submit New Inquiry
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success font-monospace d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Filters Rail -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('tenant.queries.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm font-monospace" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="waiting_response" {{ request('status') === 'waiting_response' ? 'selected' : '' }}>Waiting Tenant Response</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm font-monospace" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>General</option>
                        <option value="billing" {{ request('category') === 'billing' ? 'selected' : '' }}>Billing & Subscription</option>
                        <option value="quota" {{ request('category') === 'quota' ? 'selected' : '' }}>Quota & Limits</option>
                        <option value="technical" {{ request('category') === 'technical' ? 'selected' : '' }}>Technical Support</option>
                        <option value="compliance" {{ request('category') === 'compliance' ? 'selected' : '' }}>Compliance & Audit</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subject or reference..." class="form-control form-control-sm font-monospace">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary font-monospace w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Queries Table -->
    <div class="card border-0 shadow-sm" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
        <div class="card-header bg-transparent py-3 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 font-archivo" style="color: var(--cw-text-strong, #0F172A);"><i class="bi bi-chat-left-dots me-1"></i> Query History & Correspondence</h5>
            <span class="badge bg-secondary font-monospace">{{ $queries->total() }} Total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="font-monospace text-secondary small text-uppercase bg-light">
                    <tr>
                        <th>Reference #</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Last Activity</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queries as $q)
                        <tr>
                            <td><strong class="font-monospace text-primary">#{{ $q->id }}</strong></td>
                            <td>
                                <a href="{{ route('tenant.queries.show', $q->id) }}" class="fw-bold text-decoration-none font-archivo text-dark d-block">
                                    {{ $q->subject }}
                                </a>
                                <span class="small text-secondary font-monospace">{{ $q->messages_count }} {{ Str::plural('message', $q->messages_count) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary font-monospace text-uppercase">{{ $q->category }}</span>
                            </td>
                            <td>
                                @if($q->priority === 'urgent')
                                    <span class="badge bg-danger-subtle text-danger font-monospace">URGENT</span>
                                @elseif($q->priority === 'high')
                                    <span class="badge bg-warning-subtle text-warning font-monospace">HIGH</span>
                                @elseif($q->priority === 'medium')
                                    <span class="badge bg-info-subtle text-info font-monospace">MEDIUM</span>
                                @else
                                    <span class="badge bg-light text-secondary font-monospace">LOW</span>
                                @endif
                            </td>
                            <td>
                                @if($q->status === 'resolved' || $q->status === 'closed')
                                    <span class="badge bg-success-subtle text-success font-monospace">{{ strtoupper($q->status) }}</span>
                                @elseif($q->status === 'waiting_response')
                                    <span class="badge bg-danger-subtle text-danger font-monospace">ACTION REQUIRED</span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary font-monospace">{{ strtoupper(str_replace('_', ' ', $q->status)) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="small font-monospace">{{ $q->creator?->name ?? 'Platform Admin' }}</span>
                            </td>
                            <td class="font-monospace small text-secondary">
                                {{ $q->last_message_at ? $q->last_message_at->diffForHumans() : $q->created_at->diffForHumans() }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tenant.queries.show', $q->id) }}" class="btn btn-sm btn-outline-primary font-monospace">
                                    View / Respond <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                No active inquiries or messages found. Submit an inquiry above to connect with the platform team.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($queries->hasPages())
            <div class="card-footer bg-transparent py-3">
                {{ $queries->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: New Inquiry Submission -->
<div class="modal fade" id="newQueryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('tenant.queries.store') }}" class="modal-content" style="background: var(--cw-surface, #FFFFFF); border: 1px solid var(--cw-border, #E2E8F0); border-radius: var(--cw-radius-md, 8px);">
            @csrf
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-archivo fw-bold"><i class="bi bi-envelope-plus me-1"></i> New Administrative Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label font-monospace small text-secondary">Subject / Issue Summary <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control" required placeholder="e.g. Request to increase cleaner slots quota">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label font-monospace small text-secondary">Category</label>
                        <select name="category" class="form-select font-monospace">
                            <option value="general">General Support</option>
                            <option value="billing">Billing & Invoicing</option>
                            <option value="quota">Quota Increase</option>
                            <option value="technical">Technical Assistance</option>
                            <option value="compliance">Compliance</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-monospace small text-secondary">Priority</label>
                        <select name="priority" class="form-select font-monospace">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label font-monospace small text-secondary">Detailed Message <span class="text-danger">*</span></label>
                    <textarea name="message" rows="5" class="form-control" required placeholder="Provide clear details so our team can assist you immediately..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary font-monospace" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning font-monospace fw-bold">Send Inquiry to Platform Admin</button>
            </div>
        </form>
    </div>
</div>
@endsection
