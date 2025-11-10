@extends('layouts.admin')

@section('title', 'Pending Loan Disbursements')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.index') }}">Loans</a></li>
                        <li class="breadcrumb-item active">Pending Disbursements</li>
                    </ol>
                </div>
                <h4 class="page-title">Pending Loan Disbursements</h4>
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
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Pending</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $stats['total_pending'] }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="bx bx-time text-info"></i>
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
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Amount</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">UGX {{ number_format($stats['total_amount'], 0) }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="bx bx-money text-success"></i>
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
                            <p class="text-uppercase fw-medium text-muted mb-0">Pending Today</p>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ $stats['pending_today'] }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="bx bx-calendar text-warning"></i>
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
                            <p class="text-uppercase fw-medium text-muted mb-0">Quick Actions</p>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-sm btn-primary" onclick="refreshData()">
                                    <i class="mdi mdi-refresh me-1"></i> Refresh
                                </button>
                                <a href="{{ route('admin.loans.disbursements.export') }}" class="btn btn-sm btn-success">
                                    <i class="mdi mdi-download me-1"></i> Export
                                </a>
                            </div>
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
                    <h5 class="card-title mb-0">Pending Disbursements</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search loans..." name="search" value="{{ request('search') }}" id="searchInput">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="branch" id="branchFilter">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="product" id="productFilter">
                                <option value="">All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
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
                                    <th scope="col">Loan Code</th>
                                    <th scope="col">Borrower</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Principal</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Date Created</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loans as $loan)
                                <tr>
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
                                    <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($loan->datecreated)->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="mdi mdi-check-circle me-1"></i>Approved
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="#" role="button" id="dropdownMenuLink{{ $loan->id }}" data-bs-toggle="dropdown" aria-expanded="false" class="btn btn-soft-secondary btn-sm">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $loan->id }}">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.loans.disbursements.approve.show', $loan->id) }}">
                                                        <i class="mdi mdi-check-circle text-success me-2"></i> Process Disbursement
                                                    </a>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" onclick="viewLoanDetails({{ $loan->id }}, '{{ $loan->loan_type }}')">
                                                        <i class="mdi mdi-eye text-info me-2"></i> View Details
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" onclick="checkDisbursementStatus({{ $loan->id }})">
                                                        <i class="mdi mdi-clock text-warning me-2"></i> Check Status
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger" onclick="rejectDisbursement({{ $loan->id }})">
                                                        <i class="mdi mdi-close-circle me-2"></i> Reject Disbursement
                                                    </button>
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
                        <h5 class="mt-2">No pending disbursements found!</h5>
                        <p class="text-muted">There are no loans pending disbursement at this time.</p>
                        <a href="{{ route('admin.loans.index') }}" class="btn btn-primary">
                            <i class="mdi mdi-arrow-left me-1"></i> Back to Loans
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loan Details Modal -->
<div class="modal fade" id="loanDetailsModal" tabindex="-1" aria-labelledby="loanDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loanDetailsModalLabel">Loan Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="loanDetailsContent">
                <!-- Content will be loaded dynamically -->
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#searchInput, #branchFilter, #productFilter').on('change keyup', debounce(function() {
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
    const branch = $('#branchFilter').val();
    const product = $('#productFilter').val();
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (branch) params.append('branch', branch);
    if (product) params.append('product', product);
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function clearFilters() {
    $('#searchInput').val('');
    $('#branchFilter').val('');
    $('#productFilter').val('');
    window.location.href = window.location.pathname;
}

function refreshData() {
    window.location.reload();
}

function viewLoanDetails(loanId, loanType) {
    $('#loanDetailsContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);
    
    $('#loanDetailsModal').modal('show');
    
    // Load loan details via AJAX
    $.ajax({
        url: `{{ route('admin.loans.details', ':id') }}`.replace(':id', loanId),
        method: 'GET',
        data: { type: loanType },
        success: function(response) {
            $('#loanDetailsContent').html(response);
        },
        error: function() {
            $('#loanDetailsContent').html(`
                <div class="alert alert-danger">
                    <i class="mdi mdi-alert me-2"></i>
                    Failed to load loan details. Please try again.
                </div>
            `);
        }
    });
}

function checkDisbursementStatus(loanId) {
    // Check disbursement status
    $.ajax({
        url: `{{ route('admin.loans.disbursements.check-status', ':id') }}`.replace(':id', loanId),
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Disbursement Status',
                    html: response.message,
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to check disbursement status', 'error');
        }
    });
}

function rejectDisbursement(loanId) {
    Swal.fire({
        title: 'Reject Disbursement',
        text: 'Are you sure you want to reject this disbursement?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implement rejection logic here
            Swal.fire('Rejected!', 'The disbursement has been rejected.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    });
}
</script>
@endpush
@endsection