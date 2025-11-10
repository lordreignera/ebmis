@extends('layouts.admin')

@section('title', 'Loan Approvals')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.index') }}">Loans</a></li>
                        <li class="breadcrumb-item active">Approvals</li>
                    </ol>
                </div>
                <h4 class="page-title">Loan Approvals</h4>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Pending Approval</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $stats['pending_approval'] }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="bx bx-hourglass text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Approved Loans</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $stats['approved_loans'] }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Pending Amount</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">UGX {{ number_format($stats['pending_amount'], 0) }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="bx bx-money text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Approved Amount</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">UGX {{ number_format($stats['approved_amount'], 0) }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="bx bx-check text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Loans Pending Approval</h5>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="refreshData()">
                                    <i class="mdi mdi-refresh me-1"></i> Refresh
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="mdi mdi-filter me-1"></i> Filter
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('admin.loans.approvals') }}">All Loans</a>
                                        <a class="dropdown-item" href="{{ route('admin.loans.approvals', ['status' => 'pending']) }}">Pending Only</a>
                                        <a class="dropdown-item" href="{{ route('admin.loans.approvals', ['status' => 'approved']) }}">Approved Only</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search by loan code or member name..." 
                                       name="search" value="{{ request('search') }}" id="searchInput">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="branch_id" id="branchFilter">
                                <option value="">All Branches</option>
                                {{-- Add branch options here --}}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary w-100" onclick="clearFilters()">
                                <i class="mdi mdi-filter-off me-1"></i> Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- Loans Table -->
                    @if($loans->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Loan Code</th>
                                    <th scope="col">Borrower</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Principal</th>
                                    <th scope="col">Interest (%)</th>
                                    <th scope="col">Period</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Date Applied</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $counter = 1; @endphp
                                @foreach($loans as $loan)
                                <tr>
                                    <td>{{ $counter++ }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    <div class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                        {{ substr($loan->code, 0, 1) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0">{{ $loan->code }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($loan->loan_type === 'personal')
                                            <div>
                                                <h6 class="mb-0">{{ $loan->member->fname ?? 'N/A' }} {{ $loan->member->lname ?? '' }}</h6>
                                                <small class="text-muted">{{ $loan->member->contact ?? 'N/A' }}</small>
                                            </div>
                                        @else
                                            <div>
                                                <h6 class="mb-0">{{ $loan->group->group_name ?? 'N/A' }}</h6>
                                                <small class="text-muted">Group Loan</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $loan->loan_type === 'personal' ? 'info' : 'warning' }}-subtle text-{{ $loan->loan_type === 'personal' ? 'info' : 'warning' }}">
                                            {{ ucfirst($loan->loan_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                    <td>
                                        <strong>UGX {{ number_format($loan->principal, 0) }}</strong>
                                    </td>
                                    <td>{{ $loan->interest }}%</td>
                                    <td>{{ $loan->period ?? 'N/A' }}</td>
                                    <td>
                                        @if($loan->status == 0)
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="mdi mdi-clock me-1"></i>Pending
                                            </span>
                                        @elseif($loan->status == 1)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="mdi mdi-check-circle me-1"></i>Approved
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                Status: {{ $loan->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($loan->datecreated)->format('M d, Y') }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="#" role="button" id="dropdownMenuLink{{ $loan->id }}" 
                                               data-bs-toggle="dropdown" aria-expanded="false" 
                                               class="btn btn-soft-secondary btn-sm">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $loan->id }}">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.loans.show', $loan->id) }}?type={{ $loan->loan_type }}">
                                                        <i class="mdi mdi-eye text-info me-2"></i> View Details
                                                    </a>
                                                </li>
                                                @if($loan->status == 0)
                                                <li>
                                                    <button class="dropdown-item" onclick="approveLoan({{ $loan->id }}, '{{ $loan->loan_type }}', '{{ $loan->code }}')">
                                                        <i class="mdi mdi-check-circle text-success me-2"></i> Approve
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" onclick="rejectLoan({{ $loan->id }}, '{{ $loan->loan_type }}', '{{ $loan->code }}')">
                                                        <i class="mdi mdi-close-circle text-danger me-2"></i> Reject
                                                    </button>
                                                </li>
                                                @elseif($loan->status == 1)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.loans.disbursements.approve.show', $loan->id) }}">
                                                        <i class="mdi mdi-cash text-primary me-2"></i> Process Disbursement
                                                    </a>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.loans.edit', $loan->id) }}">
                                                        <i class="mdi mdi-pencil text-warning me-2"></i> Edit
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="row align-items-center mt-4">
                        <div class="col-sm-6">
                            <div class="text-muted">
                                Showing {{ $loans->firstItem() }} to {{ $loans->lastItem() }} of {{ $loans->total() }} entries
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-end">
                                {{ $loans->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <div class="avatar-md mx-auto mb-4">
                            <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4">
                                <i class="bx bx-search-alt-2"></i>
                            </div>
                        </div>
                        <h5 class="mt-2">No loans found for approval!</h5>
                        <p class="text-muted">There are no loans pending approval at this time.</p>
                        <a href="{{ route('admin.loans.index') }}" class="btn btn-primary">
                            <i class="mdi mdi-arrow-left me-1"></i> Back to All Loans
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Loan Modal -->
<div class="modal fade" id="approveLoanModal" tabindex="-1" aria-labelledby="approveLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveLoanModalLabel">Approve Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveLoanForm">
                @csrf
                <input type="hidden" id="approveLoanId" name="loan_id">
                <input type="hidden" id="approveLoanType" name="loan_type">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        You are about to approve loan <strong id="approveLoanCode"></strong>. This action will move the loan to approved status and make it available for disbursement.
                    </div>
                    
                    <div class="form-group">
                        <label for="approveComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="approveComments" name="comments" rows="3" 
                                  placeholder="Enter approval comments (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-check me-1"></i> Approve Loan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Loan Modal -->
<div class="modal fade" id="rejectLoanModal" tabindex="-1" aria-labelledby="rejectLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectLoanModalLabel">Reject Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectLoanForm">
                @csrf
                <input type="hidden" id="rejectLoanId" name="loan_id">
                <input type="hidden" id="rejectLoanType" name="loan_type">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert me-2"></i>
                        You are about to reject loan <strong id="rejectLoanCode"></strong>. This action cannot be undone.
                    </div>
                    
                    <div class="form-group">
                        <label for="rejectComments" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectComments" name="comments" rows="3" 
                                  placeholder="Enter reason for rejecting this loan application..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-close-circle me-1"></i> Reject Loan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#searchInput, #statusFilter, #branchFilter').on('change keyup', debounce(function() {
        filterLoans();
    }, 500));
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function filterLoans() {
    const search = $('#searchInput').val();
    const status = $('#statusFilter').val();
    const branch = $('#branchFilter').val();
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (branch) params.append('branch_id', branch);
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function clearFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('');
    $('#branchFilter').val('');
    window.location.href = window.location.pathname;
}

function refreshData() {
    window.location.reload();
}

function approveLoan(loanId, loanType, loanCode) {
    $('#approveLoanId').val(loanId);
    $('#approveLoanType').val(loanType);
    $('#approveLoanCode').text(loanCode);
    $('#approveLoanModal').modal('show');
}

function rejectLoan(loanId, loanType, loanCode) {
    $('#rejectLoanId').val(loanId);
    $('#rejectLoanType').val(loanType);
    $('#rejectLoanCode').text(loanCode);
    $('#rejectLoanModal').modal('show');
}

// Handle approve loan form submission
$('#approveLoanForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const loanId = $('#approveLoanId').val();
    const loanType = $('#approveLoanType').val();
    
    $.ajax({
        url: '{{ route("admin.loans.approve", ":id") }}'.replace(':id', loanId),
        method: 'POST',
        data: formData + '&type=' + loanType,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
            $('#approveLoanModal').modal('hide');
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to approve loan';
            Swal.fire('Error!', errorMsg, 'error');
            $('#approveLoanModal').modal('hide');
        }
    });
});

// Handle reject loan form submission
$('#rejectLoanForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const loanId = $('#rejectLoanId').val();
    const loanType = $('#rejectLoanType').val();
    
    $.ajax({
        url: '{{ route("admin.loans.reject", ":id") }}'.replace(':id', loanId),
        method: 'POST',
        data: formData + '&type=' + loanType,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
            $('#rejectLoanModal').modal('hide');
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to reject loan';
            Swal.fire('Error!', errorMsg, 'error');
            $('#rejectLoanModal').modal('hide');
        }
    });
});
</script>
@endpush
@endsection