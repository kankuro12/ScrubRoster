@extends('layouts.app')

@section('title', 'Depot QR Station')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 reveal">
        <div>
            <span class="eyebrow">Dispatch & Attendance · Rapid Access</span>
            <h1 class="h3 mt-1 mb-0">Depot QR Station</h1>
        </div>
        <div>
            <a href="{{ route('personnel') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people me-1" aria-hidden="true"></i>Back to Personnel
            </a>
        </div>
    </div>

    <div class="row g-4 reveal" style="--d: 80ms">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white font-monospace d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-lines-fill me-2"></i>Select Crew Member</span>
                    <span class="badge bg-secondary font-monospace">{{ $users->count() }} workers</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="workerSearch" class="form-label small text-muted">Quick Search</label>
                        <input type="text" id="workerSearch" class="form-control form-control-sm font-monospace" placeholder="Filter by name or employee #...">
                    </div>

                    <div class="list-group list-group-flush border rounded overflow-auto" id="workersList" style="max-height: 480px;">
                        @foreach ($users as $user)
                            <button type="button" class="list-group-item list-group-item-action worker-item p-3" data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-role="{{ $user->role == 1 ? 'Supervisor' : ($user->role == 2 ? 'Cleaner' : 'Admin') }}" data-emp="{{ $user->employee_no ?? ('CW-'.$user->id) }}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">{{ $user->name }}</span>
                                    <span class="badge bg-light text-dark border font-monospace small">{{ $user->role == 1 ? 'Supervisor' : ($user->role == 2 ? 'Cleaner' : 'Admin') }}</span>
                                </div>
                                <div class="small text-muted font-monospace">{{ $user->employee_no ?? ('ID #'.$user->id) }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm text-center h-100 p-4">
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div id="noWorkerSelected" class="py-5 text-muted">
                        <i class="bi bi-qr-code-scan display-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h4 class="fw-semibold">No Worker Selected</h4>
                        <p class="small text-muted">Select an employee from the list on the left to generate their live QR login badge.</p>
                    </div>

                    <div id="workerQrDisplay" style="display: none; width: 100%; max-width: 420px;">
                        <div class="p-3 bg-light rounded border mb-3">
                            <span class="eyebrow mb-1 d-block text-uppercase" id="selectedUserRole">CLEANER</span>
                            <h2 class="fw-black mb-1" id="selectedUserName">—</h2>
                            <span class="badge bg-dark font-monospace" id="selectedUserEmp">CW-001</span>
                        </div>

                        <div class="p-3 bg-white rounded border shadow-sm my-2 d-inline-block">
                            <canvas id="stationCanvas" style="max-width: 260px; height: auto; display: block;"></canvas>
                            <div id="stationLoading" class="py-5 text-muted small"><i class="bi bi-arrow-repeat spin me-1"></i>Generating security token...</div>
                        </div>

                        <div class="alert alert-info py-2 px-3 small font-monospace mt-3 text-start">
                            <i class="bi bi-shield-check me-1 text-primary"></i>
                            Scan with phone camera for <strong>instant passwordless login</strong>.
                        </div>

                        <div class="input-group input-group-sm mb-3">
                            <input type="text" id="stationDirectUrl" class="form-control font-monospace" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="btnCopyStationUrl">Copy URL</button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center w-100 pt-2 border-top">
                            <span class="badge bg-light text-muted border font-monospace" id="stationExpiry">Valid for 2h</span>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnRefreshStationQr">
                                <i class="bi bi-arrow-clockwise me-1"></i>Regenerate QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        (function ($) {
            var activeUserId = null;

            $('#workerSearch').on('input', function() {
                var term = $(this).val().toLowerCase();
                $('#workersList .worker-item').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(term) > -1);
                });
            });

            $(document).on('click', '.worker-item', function() {
                $('.worker-item').removeClass('active');
                $(this).addClass('active');

                var id = $(this).data('id');
                var name = $(this).data('name');
                var role = $(this).data('role');
                var emp = $(this).data('emp');

                activeUserId = id;

                $('#noWorkerSelected').hide();
                $('#workerQrDisplay').show();
                $('#selectedUserName').text(name);
                $('#selectedUserRole').text(role);
                $('#selectedUserEmp').text(emp);

                $('#stationCanvas').hide();
                $('#stationLoading').show();

                loadStationQr(id);
            });

            $('#btnRefreshStationQr').on('click', function() {
                if (activeUserId) {
                    $('#stationCanvas').hide();
                    $('#stationLoading').show();
                    loadStationQr(activeUserId);
                }
            });

            function loadStationQr(userId) {
                axios.post('{{ url('admin/personnel') }}/' + userId + '/qr-token')
                    .then(function(response) {
                        var data = response.data;
                        $('#stationDirectUrl').val(data.qr_url);
                        $('#stationExpiry').text('Expires ' + data.expires_human);

                        var canvas = document.getElementById('stationCanvas');
                        QRCode.toCanvas(canvas, data.qr_url, {
                            width: 250,
                            margin: 1,
                            color: {
                                dark: '#0a1128',
                                light: '#ffffff'
                            }
                        }, function(err) {
                            if (!err) {
                                $('#stationLoading').hide();
                                $('#stationCanvas').show();
                            }
                        });
                    })
                    .catch(function(err) {
                        alert('Could not generate token: ' + (err.response?.data?.message || 'Error'));
                    });
            }

            $('#btnCopyStationUrl').on('click', function() {
                var input = document.getElementById('stationDirectUrl');
                input.select();
                navigator.clipboard.writeText(input.value);
                $(this).text('Copied!');
                var btn = this;
                setTimeout(function() { $(btn).text('Copy URL'); }, 2000);
            });
        })(jQuery);
    </script>
@endpush
