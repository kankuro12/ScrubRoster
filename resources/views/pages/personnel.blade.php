@extends('layouts.app')

@section('title', 'Personnel')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 reveal">
        <div>
            <span class="eyebrow">People</span>
            <h1 class="h3 mt-1 mb-0">Personnel registry</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('teams') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-person-workspace me-1" aria-hidden="true"></i>Teams
            </a>
            <a href="{{ route('branches') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>Branches
            </a>
            <a href="{{ route('quick-login') }}" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-qr-code-scan me-1" aria-hidden="true"></i>Depot QR Station
            </a>
            <a href="{{ route('personnel.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>New person
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 reveal" role="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2 reveal" role="alert">{{ $errors->first() }}</div>
    @endif

    @include('partials.compact-filter-bar', ['searchNames' => ['search'], 'searchPlaceholder' => 'Name, email, employee no.', 'hideJsPills' => true])

    <form method="GET" id="filter-form" class="filter-form mb-3 reveal" style="--d: 80ms">
        <div class="filter-sheet-head">
            <span class="mono text-muted">Filter options</span>
            <button type="button" class="btn btn-icon-touch" data-filter-close aria-label="Close filters">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="row g-2 filter-sheet-body">
        <div class="col-md-4">
            <label for="search" class="visually-hidden">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, email, employee no.">
        </div>
        <div class="col-md-2">
            <label for="role" class="visually-hidden">Role</label>
            <select name="role" id="role" class="form-select form-select-sm">
                <option value="">All roles</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') !== null && (int) request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="status" class="visually-hidden">Status</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                @foreach (['invited', 'active', 'inactive', 'suspended', 'on_leave', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-sm btn-outline-secondary w-100 d-none d-md-block">
                <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter
            </button>
        </div>
        </div>
        <div class="filter-sheet-foot">
            <button type="submit" class="btn btn-touch w-100">Apply filters</button>
        </div>
    </form>

    @php
        $q = request()->query();
        $pillUrl = function (array $overrides) use ($q) {
            $merged = array_merge($q, $overrides);
            foreach ($overrides as $k => $v) {
                if ($v === null) unset($merged[$k]);
            }
            return url()->current() . '?' . http_build_query($merged);
        };
    @endphp

    <div class="filter-pills d-lg-none mb-3 reveal" style="--d: 100ms" role="navigation" aria-label="Quick filters">
        <a href="{{ $pillUrl(['role' => null, 'status' => null]) }}" class="pill @if(!request()->filled('role') && !request()->filled('status')) active @endif">All</a>
        <a href="{{ $pillUrl(['role' => null, 'status' => 'active']) }}" class="pill @if(request('status') === 'active' && !request()->filled('role')) active @endif">Active</a>
        @foreach ($roles as $value => $label)
            <a href="{{ $pillUrl(['role' => $value, 'status' => null]) }}" class="pill @if(request()->filled('role') && (int) request('role') === $value) active @endif">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card shadow-sm reveal" style="--d: 140ms">
        <div class="d-lg-none p-3 d-flex flex-column gap-2">
            @forelse ($users as $user)
                <div class="mobile-task-card compact">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <span class="mtc-title">{{ $user->name }}</span>
                        <span class="status-badge status-{{ in_array($user->status, ['active']) ? 'active' : (in_array($user->status, ['suspended']) ? 'danger' : 'muted') }}">{{ $user->status }}</span>
                    </div>
                    <div class="mtc-ref mb-1">{{ $user->email }}</div>
                    <div class="mtc-meta mb-2">
                        {{ $roles[$user->role] }} @if($user->branch?->name) · {{ $user->branch->name }} @endif
                        @if($user->team?->name) · {{ $user->team->name }} @endif
                        @if($user->manager?->name) · {{ $user->manager->name }} @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if($user->phone)
                            <a href="tel:{{ $user->phone }}" class="btn btn-outline-secondary btn-icon-touch" aria-label="Call {{ $user->name }}">
                                <i class="bi bi-telephone" aria-hidden="true"></i>
                            </a>
                        @endif
                        <a href="mailto:{{ $user->email }}" class="btn btn-outline-secondary btn-icon-touch" aria-label="Email {{ $user->name }}">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="btn btn-outline-warning btn-icon-touch btn-qr-code" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" title="Quick QR Badge">
                            <i class="bi bi-qr-code" aria-hidden="true"></i>
                        </button>
                        <a href="{{ route('personnel.edit', $user) }}" class="btn btn-outline-secondary btn-touch flex-fill">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state py-4">
                    <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                    No personnel match the current filters.
                </div>
            @endforelse
        </div>
        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Branch</th>
                        <th>Team</th>
                        <th>Manager</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td data-label="Person">
                                <span class="fw-semibold text-dark">{{ $user->name }}</span><br>
                                <small class="text-muted">{{ $user->email }}</small>
                            </td>
                            <td data-label="Role">
                                <span class="status-badge status-muted">{{ $roles[$user->role] }}</span>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge status-{{ in_array($user->status, ['active']) ? 'active' : (in_array($user->status, ['suspended']) ? 'danger' : 'muted') }}">{{ $user->status }}</span>
                            </td>
                            <td data-label="Branch">{{ $user->branch?->name ?? '—' }}</td>
                            <td data-label="Team">{{ $user->team?->name ?? '—' }}</td>
                            <td data-label="Manager">{{ $user->manager?->name ?? '—' }}</td>
                            <td class="text-end" data-label="Actions">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning btn-qr-code" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" title="Quick QR Badge">
                                        <i class="bi bi-qr-code me-1" aria-hidden="true"></i>QR
                                    </button>
                                    <a href="{{ route('personnel.edit', $user) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                                    </a>
                                    @if(auth()->user()->hasPermission('2.3'))
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions">
                                            <span class="visually-hidden">More</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#passwordModal" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                                    <i class="bi bi-key me-2" aria-hidden="true"></i>Change password
                                                </button>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('personnel.toggle-active', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-{{ $user->status === 'active' ? 'pause-circle' : 'play-circle' }} me-2" aria-hidden="true"></i>
                                                        {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </li>
                                            @if(auth()->user()->hasPermission('2.4'))
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('personnel.destroy', $user) }}" onsubmit="return confirm('Archive {{ $user->name }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-archive me-2" aria-hidden="true"></i>Archive
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                                    No personnel match the current filters.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" id="password-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="passwordModalLabel">Change password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted" id="password-target"></p>
                        <div class="mb-3">
                            <label for="new-password" class="form-label">New password</label>
                            <input type="password" id="new-password" name="password" class="form-control" minlength="8" required>
                        </div>
                        <div class="mb-3">
                            <label for="new-password-confirm" class="form-label">Confirm password</label>
                            <input type="password" id="new-password-confirm" name="password_confirmation" class="form-control" minlength="8" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- QR Quick Login Badge Modal --}}
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark font-monospace">FIELD-OPS</span>
                        <h5 class="modal-title font-monospace mb-0" id="qrModalLabel">Employee Quick Login Badge</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <h4 class="fw-bold mb-1" id="qrUserName">—</h4>
                        <span class="badge bg-secondary font-monospace" id="qrUserRole">CLEANER</span>
                        <span class="badge bg-dark font-monospace text-light" id="qrUserEmpNo">#</span>
                    </div>

                    <div class="d-flex justify-content-center my-3">
                        <div class="p-3 bg-white rounded border shadow-sm" style="display: inline-block;">
                            <canvas id="qrCanvas" style="max-width: 240px; height: auto; display: block;"></canvas>
                            <div id="qrLoading" class="py-4 text-muted small"><i class="bi bi-arrow-repeat spin me-1"></i>Generating security token...</div>
                        </div>
                    </div>

                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle me-1 text-warning"></i>
                        Point worker phone camera at this QR code. <strong>Instantly signs in with zero credentials required.</strong>
                    </p>

                    <div class="input-group input-group-sm mb-2">
                        <input type="text" id="qrDirectUrl" class="form-control font-monospace small" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="btnCopyQrUrl">
                            <i class="bi bi-clipboard me-1"></i>Copy
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="badge bg-light text-muted border font-monospace" id="qrExpiry">Valid for 2h</span>
                        <a href="#" id="qrDirectLink" target="_blank" class="small text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Test login in new tab
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-top justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshQr">
                        <i class="bi bi-arrow-clockwise me-1"></i>Regenerate Token
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 reveal" style="--d: 200ms">{{ $users->links() }}</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        (function ($) {
            var currentTargetUserId = null;

            $('#passwordModal').on('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                var id = btn.dataset.userId, name = btn.dataset.userName;
                $('#password-target').text('Update password for ' + name);
                $('#password-form').attr('action', '{{ url('admin/personnel') }}/' + id + '/password');
                $('#new-password, #new-password-confirm').val('');
            });

            $(document).on('click', '.btn-qr-code', function () {
                var userId = $(this).data('userId');
                var userName = $(this).data('userName');
                currentTargetUserId = userId;

                $('#qrUserName').text(userName);
                $('#qrCanvas').hide();
                $('#qrLoading').show();
                $('#qrDirectUrl').val('');

                var modal = new bootstrap.Modal(document.getElementById('qrModal'));
                modal.show();

                loadQrCode(userId);
            });

            $('#btnRefreshQr').on('click', function () {
                if (currentTargetUserId) {
                    $('#qrCanvas').hide();
                    $('#qrLoading').show();
                    loadQrCode(currentTargetUserId);
                }
            });

            function loadQrCode(userId) {
                axios.post('{{ url('admin/personnel') }}/' + userId + '/qr-token')
                    .then(function (response) {
                        var data = response.data;
                        $('#qrUserRole').text(data.user.role_label.toUpperCase());
                        $('#qrUserEmpNo').text(data.user.employee_no);
                        $('#qrDirectUrl').val(data.qr_url);
                        $('#qrDirectLink').attr('href', data.qr_url);
                        $('#qrExpiry').text('Expires ' + data.expires_human);

                        var canvas = document.getElementById('qrCanvas');
                        QRCode.toCanvas(canvas, data.qr_url, {
                            width: 220,
                            margin: 1,
                            color: {
                                dark: '#0a1128',
                                light: '#ffffff'
                            }
                        }, function (error) {
                            if (!error) {
                                $('#qrLoading').hide();
                                $('#qrCanvas').show();
                            }
                        });
                    })
                    .catch(function (error) {
                        alert('Failed to generate QR token: ' + (error.response?.data?.message || 'Server error'));
                    });
            }

            $('#btnCopyQrUrl').on('click', function () {
                var urlInput = document.getElementById('qrDirectUrl');
                urlInput.select();
                urlInput.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(urlInput.value);
                $(this).html('<i class="bi bi-check2 me-1 text-success"></i>Copied!');
                var btn = this;
                setTimeout(function () {
                    $(btn).html('<i class="bi bi-clipboard me-1"></i>Copy');
                }, 2000);
            });
        })(jQuery);
    </script>
@endpush
