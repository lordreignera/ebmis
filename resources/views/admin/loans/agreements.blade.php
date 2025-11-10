@extends('layouts.admin')

@section('title', 'Loan Agreements')

@section('content')
<div class="nk-content nk-content-lg nk-content-fluid">
    <div class="container-xl wide-lg">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <h4 class="nk-block-title">Loan Agreement Signing</h4>
                        <div class="nk-block-des">
                            <p>Manage loan agreements pending electronic signature.</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-gs mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="nk-ecwg nk-ecwg-compact">
                                <div class="card-inner">
                                    <div class="nk-ecwg-head">
                                        <div class="nk-ecwg-data">
                                            <div class="number">{{ $statistics['pending_signature'] ?? 0 }}</div>
                                            <div class="title">Pending Signature</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="nk-ecwg nk-ecwg-compact">
                                <div class="card-inner">
                                    <div class="nk-ecwg-head">
                                        <div class="nk-ecwg-data">
                                            <div class="number">{{ $statistics['signed_today'] ?? 0 }}</div>
                                            <div class="title">Signed Today</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="nk-ecwg nk-ecwg-compact">
                                <div class="card-inner">
                                    <div class="nk-ecwg-head">
                                        <div class="nk-ecwg-data">
                                            <div class="number">{{ $statistics['total_amount'] ?? 0 }}</div>
                                            <div class="title">Total Amount (UGX)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="nk-ecwg nk-ecwg-compact">
                                <div class="card-inner">
                                    <div class="nk-ecwg-head">
                                        <div class="nk-ecwg-data">
                                            <div class="number">{{ $statistics['expired_signatures'] ?? 0 }}</div>
                                            <div class="title">Expired OTPs</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-inner">
                        <form method="GET" action="{{ route('admin.loans.agreements') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Search</label>
                                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Member name, loan code...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Loan Type</label>
                                        <select class="form-control" name="loan_type">
                                            <option value="">All Types</option>
                                            <option value="personal" {{ request('loan_type') == 'personal' ? 'selected' : '' }}>Personal Loans</option>
                                            <option value="group" {{ request('loan_type') == 'group' ? 'selected' : '' }}>Group Loans</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Signature Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="signed" {{ request('status') == 'signed' ? 'selected' : '' }}>Signed</option>
                                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>OTP Expired</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-block">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <a href="{{ route('admin.loans.agreements') }}" class="btn btn-light">Clear</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Loans List -->
                <div class="card">
                    <div class="card-inner">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Loan Code</th>
                                        <th>Member</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>OTP Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($loans as $loan)
                                    <tr>
                                        <td>
                                            <span class="text-dark fw-bold">{{ $loan->loan_code }}</span>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="text-dark">{{ $loan->member_name }}</span>
                                                <small class="text-muted d-block">{{ $loan->member_contact }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline-{{ $loan->loan_type == 'personal' ? 'primary' : 'info' }}">
                                                {{ ucfirst($loan->loan_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount">{{ number_format($loan->amount) }} UGX</span>
                                        </td>
                                        <td>
                                            @if($loan->signature_status == 'pending')
                                                <span class="badge badge-dim bg-warning">Pending Signature</span>
                                            @elseif($loan->signature_status == 'signed')
                                                <span class="badge badge-dim bg-success">Signed</span>
                                            @elseif($loan->signature_status == 'expired')
                                                <span class="badge badge-dim bg-danger">OTP Expired</span>
                                            @else
                                                <span class="badge badge-dim bg-secondary">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($loan->otp_code && $loan->otp_expires_at)
                                                @if(now()->gt($loan->otp_expires_at))
                                                    <span class="text-danger">Expired</span>
                                                @else
                                                    <span class="text-success">Active</span>
                                                    <small class="text-muted d-block">
                                                        {{ $loan->otp_expires_at->diffForHumans() }}
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">No OTP</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $loan->created_at->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewAgreement('{{ $loan->id }}', '{{ $loan->loan_type }}')">
                                                        <em class="icon ni ni-file-pdf"></em> View Agreement
                                                    </a></li>
                                                    @if($loan->signature_status == 'pending')
                                                        <li><a class="dropdown-item" href="#" onclick="sendOTP('{{ $loan->id }}', '{{ $loan->loan_type }}')">
                                                            <em class="icon ni ni-send"></em> Send OTP
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="signAgreement('{{ $loan->id }}', '{{ $loan->loan_type }}', '{{ $loan->member_name }}')">
                                                            <em class="icon ni ni-check-thick"></em> eSign with OTP
                                                        </a></li>
                                                    @endif
                                                    @if($loan->signature_status == 'signed')
                                                        <li><a class="dropdown-item" href="#" onclick="downloadSignedAgreement('{{ $loan->id }}', '{{ $loan->loan_type }}')">
                                                            <em class="icon ni ni-download"></em> Download Signed
                                                        </a></li>
                                                    @endif
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="rejectLoan('{{ $loan->id }}', '{{ $loan->loan_type }}', '{{ $loan->member_name }}')">
                                                        <em class="icon ni ni-cross-circle"></em> Reject Loan
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <em class="icon ni ni-file-text mb-2" style="font-size: 2rem;"></em>
                                                <p>No loan agreements found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($loans->hasPages())
                            <div class="mt-4">
                                {{ $loans->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- eSign Modal -->
<div class="modal fade" id="eSignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Electronic Signature</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="eSignForm">
                <div class="modal-body">
                    <div id="eSignContent">
                        <div class="form-group">
                            <label class="form-label">Member</label>
                            <p id="eSignMember" class="form-text"></p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Enter OTP Code</label>
                            <input type="number" class="form-control" id="otpCode" name="otp_code" placeholder="Enter 6-digit OTP" maxlength="6" required>
                            <div class="form-note">
                                <small class="text-muted">Check your SMS for the verification code.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="signatureComments" name="comments" rows="3" placeholder="Add any comments about the signature..."></textarea>
                        </div>
                    </div>
                    <div id="eSignLoading" style="display: none;">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <p class="mt-2">Processing signature...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="eSignBtn">
                        <em class="icon ni ni-check-thick"></em> Sign Agreement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Member</label>
                        <p id="rejectMember" class="form-text"></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="rejectionReason" name="reason" rows="4" placeholder="Explain why this loan is being rejected..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <em class="icon ni ni-cross-circle"></em> Reject Loan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentLoanId = null;
let currentLoanType = null;

// Send OTP
function sendOTP(loanId, loanType) {
    if (confirm('Send OTP to member for loan agreement signing?')) {
        $.post('{{ route("admin.loans.send-otp") }}', {
            loan_id: loanId,
            loan_type: loanType,
            _token: '{{ csrf_token() }}'
        }).done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message);
            }
        }).fail(function() {
            toastr.error('Error sending OTP');
        });
    }
}

// Sign Agreement
function signAgreement(loanId, loanType, memberName) {
    currentLoanId = loanId;
    currentLoanType = loanType;
    $('#eSignMember').text(memberName);
    $('#otpCode').val('');
    $('#signatureComments').val('');
    $('#eSignModal').modal('show');
}

// Handle eSign form submission
$('#eSignForm').on('submit', function(e) {
    e.preventDefault();
    
    $('#eSignContent').hide();
    $('#eSignLoading').show();
    $('#eSignBtn').prop('disabled', true);
    
    $.post('{{ route("admin.loans.sign-agreement") }}', {
        loan_id: currentLoanId,
        loan_type: currentLoanType,
        otp_code: $('#otpCode').val(),
        comments: $('#signatureComments').val(),
        _token: '{{ csrf_token() }}'
    }).done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            $('#eSignModal').modal('hide');
            setTimeout(() => location.reload(), 2000);
        } else {
            toastr.error(response.message);
            $('#eSignContent').show();
            $('#eSignLoading').hide();
            $('#eSignBtn').prop('disabled', false);
        }
    }).fail(function() {
        toastr.error('Error processing signature');
        $('#eSignContent').show();
        $('#eSignLoading').hide();
        $('#eSignBtn').prop('disabled', false);
    });
});

// Reject Loan
function rejectLoan(loanId, loanType, memberName) {
    currentLoanId = loanId;
    currentLoanType = loanType;
    $('#rejectMember').text(memberName);
    $('#rejectionReason').val('');
    $('#rejectModal').modal('show');
}

// Handle reject form submission
$('#rejectForm').on('submit', function(e) {
    e.preventDefault();
    
    $.post('{{ route("admin.loans.reject") }}', {
        loan_id: currentLoanId,
        loan_type: currentLoanType,
        reason: $('#rejectionReason').val(),
        _token: '{{ csrf_token() }}'
    }).done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            $('#rejectModal').modal('hide');
            setTimeout(() => location.reload(), 2000);
        } else {
            toastr.error(response.message);
        }
    }).fail(function() {
        toastr.error('Error rejecting loan');
    });
});

// View Agreement
function viewAgreement(loanId, loanType) {
    const url = `{{ route('admin.loans.view-agreement', ['loan' => 'LOAN_ID', 'type' => 'LOAN_TYPE']) }}`
        .replace('LOAN_ID', loanId)
        .replace('LOAN_TYPE', loanType);
    window.open(url, '_blank');
}

// Download Signed Agreement
function downloadSignedAgreement(loanId, loanType) {
    const url = `{{ route('admin.loans.download-signed-agreement', ['loan' => 'LOAN_ID', 'type' => 'LOAN_TYPE']) }}`
        .replace('LOAN_ID', loanId)
        .replace('LOAN_TYPE', loanType);
    window.open(url, '_blank');
}

// Reset modal state when hidden
$('#eSignModal').on('hidden.bs.modal', function () {
    $('#eSignContent').show();
    $('#eSignLoading').hide();
    $('#eSignBtn').prop('disabled', false);
});
</script>
@endsection