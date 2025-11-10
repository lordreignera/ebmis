@extends('layouts.admin')

@section('title', 'Pending Loans Report')

@section('styles')
<style>
/* Enhanced KPI Cards Styling */
.widget-flat {
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease !important;
    min-height: 140px !important;
}

.widget-flat:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important;
}

.widget-flat .card-body {
    padding: 1.5rem !important;
    height: 100% !important;
}

.widget-flat h2 {
    line-height: 1.1 !important;
    margin-bottom: 0.5rem !important;
}

.widget-flat h6 {
    margin-bottom: 0.5rem !important;
    font-weight: 700 !important;
}

.h-100 {
    height: 100% !important;
}

/* Equal height cards in a row */
.row .col-md-6.col-xl-3 {
    display: flex;
    margin-bottom: 1rem;
}

.row .col-md-6.col-xl-3 .card {
    width: 100%;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Pending Loan Applications Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Pending Loans</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter & Search</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.pending-loans') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search by loan ID, member name...">
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach(\App\Models\Branch::all() as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="loan_type" class="form-label">Loan Type</label>
                            <select class="form-select" id="loan_type" name="loan_type">
                                <option value="">All Types</option>
                                <option value="Personal Loan" {{ request('loan_type') == 'Personal Loan' ? 'selected' : '' }}>Personal Loan</option>
                                <option value="Group Loan" {{ request('loan_type') == 'Group Loan' ? 'selected' : '' }}>Group Loan</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="mdi mdi-magnify"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            @if(isset($data['loans']) && $data['loans']->count() > 0)
                                <div class="btn-group d-block">
                                    <a href="{{ request()->fullUrlWithQuery(['download' => 'csv']) }}" class="btn btn-sm btn-outline-success mb-1">
                                        <i class="mdi mdi-file-delimited"></i> CSV
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['download' => 'excel']) }}" class="btn btn-sm btn-outline-primary mb-1">
                                        <i class="mdi mdi-file-excel"></i> Excel
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                                        <i class="mdi mdi-file-pdf"></i> PDF
                                    </a>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Loan Applications ({{ isset($data['loans']) ? count($data['loans']) : 0 }} records)</h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover modern-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Loan ID</th>
                                    <th>Member</th>
                                    <th>Loan Type</th>
                                    <th>Branch</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Applied Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($data['loans']) && count($data['loans']) > 0)
                                @foreach($data['loans'] as $loan)
                                <tr>
                                    <td>
                                        <strong>{{ $loan->loan_id }}</strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $loan->member->first_name }} {{ $loan->member->last_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $loan->member->member_id }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($loan->member_id && !$loan->group_id)
                                            <span class="badge bg-primary">Personal Loan</span>
                                        @elseif($loan->group_id)
                                            <span class="badge bg-info">Group Loan</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                                    <td>
                                        <strong>{{ number_format($loan->loan_amount) }} UGX</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ ucfirst($loan->status) }}</span>
                                    </td>
                                    <td>{{ $loan->created_at->format('d M Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('loans.edit', $loan->id) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if($loan->status == 'pending')
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="approveLoan({{ $loan->id }})" title="Approve">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="rejectLoan({{ $loan->id }})" title="Reject">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No pending loan applications found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        @if(isset($data['loans']) && count($data['loans']) > 0)
                        <div>
                            <small class="text-muted">
                                Showing {{ count($data['loans']) }} pending loans
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this loan application?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprove">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejection_reason" class="form-label">Rejection Reason</label>
                    <textarea class="form-control" id="rejection_reason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentLoanId = null;

function approveLoan(loanId) {
    currentLoanId = loanId;
    $('#approveModal').modal('show');
}

function rejectLoan(loanId) {
    currentLoanId = loanId;
    $('#rejectModal').modal('show');
}

$('#confirmApprove').click(function() {
    if (currentLoanId) {
        // Here you would make an AJAX call to approve the loan
        $.post(`/admin/loans/${currentLoanId}/approve`, {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            location.reload();
        });
    }
});

$('#confirmReject').click(function() {
    const reason = $('#rejection_reason').val();
    if (currentLoanId && reason) {
        // Here you would make an AJAX call to reject the loan
        $.post(`/admin/loans/${currentLoanId}/reject`, {
            _token: '{{ csrf_token() }}',
            reason: reason
        }).done(function() {
            location.reload();
        });
    }
});

function exportToExcel() {
    window.open('{{ route("admin.reports.pending-loans") }}?{{ request()->getQueryString() }}&export=excel', '_blank');
}

function exportToPDF() {
    window.open('{{ route("admin.reports.pending-loans") }}?{{ request()->getQueryString() }}&export=pdf', '_blank');
}
</script>
@endpush

@push('styles')
<style>
/* Modern Table Styles for Reports */
.modern-table {
    background: #fff;
    border-collapse: collapse;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.modern-table thead th {
    background: #2c3e50;
    color: #fff;
    font-weight: 600;
    padding: 12px 15px;
    text-align: left;
    border: none;
}

.modern-table tbody tr {
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s ease;
}

.modern-table tbody tr:hover {
    background-color: #f8f9fa;
}

.modern-table tbody td {
    padding: 12px 15px;
    color: #2c3e50;
    border: none;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-block;
}

.status-verified {
    background-color: #d4edda;
    color: #155724;
}

.status-not-verified {
    background-color: #fff3cd;
    color: #856404;
}

.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
}

.status-info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.account-number {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #007bff;
}

.table-container {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
@endpush