@extends('layouts.admin')

@section('title', 'Pending Loan Disbursements')

@push('styles')
<!-- Modern table styles are included in the admin layout -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

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

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-search">
                                <input type="text" placeholder="Search disbursements..." value="{{ request('search') }}" id="quickSearch">
                            </div>
                            <div class="table-actions">
                                <div class="table-show-entries">
                                    Show 
                                    <select onchange="window.location.href='{{ url()->current() }}?per_page='+this.value">
                                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    entries
                                </div>
                                <a href="{{ route('admin.loans.disbursements.export') }}" class="export-btn">
                                    <i class="mdi mdi-download"></i> Export
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Loan Code</th>
                                        <th>Borrower</th>
                                        <th>Type</th>
                                        <th>Product</th>
                                        <th>Principal</th>
                                        <th>Branch</th>
                                        <th>Date Created</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            <tbody>
                                @foreach($loans as $loan)
                                <tr data-loan-id="{{ $loan->getAttribute('id') }}" data-loan-type="{{ $loan->loan_type }}">
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
                                                <span class="account-number">{{ $loan->code }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($loan->loan_type === 'personal' && $loan->member)
                                            <div>
                                                <div class="fw-medium">{{ $loan->member->fname ?? 'N/A' }} {{ $loan->member->lname ?? '' }}</div>
                                                <small class="text-muted">{{ $loan->member->contact ?? 'No contact' }}</small>
                                            </div>
                                        @elseif($loan->loan_type === 'group' && $loan->group)
                                            <div>
                                                <div class="fw-medium">{{ $loan->group->group_name ?? 'N/A' }}</div>
                                                <small class="text-muted">Group Loan</small>
                                            </div>
                                        @else
                                            <div>
                                                <div class="text-muted">Data Missing</div>
                                                <small class="text-danger">{{ ucfirst($loan->loan_type) }} relationship not found</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $loan->loan_type === 'personal' ? 'individual' : 'verified' }}">
                                            {{ ucfirst($loan->loan_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $loan->product->name ?? 'Product not found' }}</td>
                                    <td>
                                        <span class="fw-semibold">UGX {{ number_format($loan->principal ?? 0, 0) }}</span>
                                    </td>
                                    <td>{{ $loan->branch->name ?? 'Branch not found' }}</td>
                                    <td>
                                        @if($loan->datecreated)
                                            {{ \Carbon\Carbon::parse($loan->datecreated)->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">Date not available</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge status-approved">
                                            <i class="mdi mdi-check-circle me-1"></i>Approved for Disbursement
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-modern btn-view" onclick="viewLoanDetails({{ $loan->getAttribute('id') }}, '{{ $loan->loan_type }}')" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if(auth()->user()->hasRole('Super Administrator') || auth()->user()->hasRole('superadmin'))
                                                <a href="{{ route('admin.loans.disbursements.approve.show', $loan->getAttribute('id')) }}" class="btn-modern btn-success" title="Process Disbursement">
                                                    <i class="mdi mdi-check-circle"></i>
                                                </a>
                                                <button type="button" class="btn-modern btn-danger" onclick="rejectDisbursement({{ $loan->getAttribute('id') }})" title="Reject Disbursement">
                                                    <i class="mdi mdi-close-circle"></i>
                                                </button>
                                            @else
                                                <button class="btn-modern btn-secondary" disabled title="Only Super Administrator can disburse">
                                                    <i class="mdi mdi-lock"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                        <div class="modern-pagination">
                            <div class="pagination-info">
                                @if($loans->total() > 0)
                                    Showing {{ $loans->firstItem() ?? 1 }} to {{ $loans->lastItem() ?? $loans->count() }} of {{ $loans->total() }} entries
                                @else
                                    No entries found
                                @endif
                            </div>
                            <div class="pagination-controls">
                                @if($loans->hasPages())
                                    @if ($loans->onFirstPage())
                                        <span class="pagination-btn" disabled>Previous</span>
                                    @else
                                        <a class="pagination-btn" href="{{ $loans->previousPageUrl() }}">Previous</a>
                                    @endif

                                    <div class="pagination-numbers">
                                        @php
                                            $currentPage = $loans->currentPage();
                                            $lastPage = $loans->lastPage();
                                            $start = max(1, $currentPage - 2);
                                            $end = min($lastPage, $currentPage + 2);
                                            
                                            if ($currentPage <= 3) {
                                                $end = min(5, $lastPage);
                                            }
                                            if ($currentPage >= $lastPage - 2) {
                                                $start = max(1, $lastPage - 4);
                                            }
                                        @endphp

                                        @if($start > 1)
                                            <a href="{{ $loans->url(1) }}" class="pagination-btn">1</a>
                                            @if($start > 2)
                                                <span class="pagination-btn" disabled>...</span>
                                            @endif
                                        @endif

                                        @for ($page = $start; $page <= $end; $page++)
                                            @if ($page == $currentPage)
                                                <span class="pagination-btn active">{{ $page }}</span>
                                            @else
                                                <a href="{{ $loans->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                            @endif
                                        @endfor

                                        @if($end < $lastPage)
                                            @if($end < $lastPage - 1)
                                                <span class="pagination-btn" disabled>...</span>
                                            @endif
                                            <a href="{{ $loans->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                        @endif
                                    </div>

                                    @if ($loans->hasMorePages())
                                        <a class="pagination-btn" href="{{ $loans->nextPageUrl() }}">Next</a>
                                    @else
                                        <span class="pagination-btn" disabled>Next</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div class="avatar-md mx-auto mb-4" style="width: 80px; height: 80px;">
                            <div class="avatar-title rounded-circle fs-1" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); color: #856404;">
                                <i class="mdi mdi-alert-circle-outline"></i>
                            </div>
                        </div>
                        <h5 class="mt-2 mb-3">No Loans Ready for Disbursement</h5>
                        <p class="text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
                            @if($stats['total_pending'] > 0)
                                There are <strong>{{ $stats['total_pending'] }}</strong> approved loans, but they cannot be disbursed because 
                                <strong>mandatory upfront fees have not been paid</strong>.
                            @else
                                There are currently no approved loans pending disbursement.
                            @endif
                        </p>
                        
                        @if($stats['total_pending'] > 0)
                        <div class="alert alert-warning mx-auto" style="max-width: 700px; text-align: left; border-left: 4px solid #ffc107;">
                            <h6 class="alert-heading mb-2">
                                <i class="mdi mdi-information-outline me-2"></i>Important Information
                            </h6>
                            <ul class="mb-2" style="padding-left: 20px;">
                                <li>Approved loans with <strong>charge_type = 2</strong> require all upfront fees to be paid before disbursement</li>
                                <li>These {{ $stats['total_pending'] }} loans were approved before the fee validation system was implemented</li>
                                <li>To make them available for disbursement, the borrowers must pay all mandatory fees</li>
                            </ul>
                            <hr style="margin: 15px 0;">
                            <p class="mb-0">
                                <strong>Going forward:</strong> New loan approvals will automatically validate that all mandatory fees are paid before allowing approval.
                            </p>
                        </div>
                        @endif
                        
                        <div class="mt-4">
                            <a href="{{ route('admin.loans.approvals') }}" class="btn btn-primary me-2">
                                <i class="mdi mdi-clipboard-check-outline me-1"></i> Go to Loan Approvals
                            </a>
                            <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Back to All Loans
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loan Details Modal - Compact -->
<div class="modal fade" id="loanDetailsModal" tabindex="-1" aria-labelledby="loanDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="loanDetailsModalLabel">
                    <i class="mdi mdi-file-document-outline me-2"></i>Loan Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="loanDetailsContent">
                <!-- Content will be loaded dynamically -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 mb-0">Loading loan details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#searchInput, #branchFilter, #productFilter').on('change keyup', debounce(function() {
        filterLoans();
    }, 500));
    
    // Quick search functionality
    $('#quickSearch').on('keyup', debounce(function() {
        let searchValue = $(this).val();
        // Update the original search input to maintain consistency
        $('#searchInput').val(searchValue);
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
    console.log('Reject function called for loan ID:', loanId);
    
    // Check if SweetAlert2 is loaded
    if (typeof Swal === 'undefined') {
        alert('SweetAlert2 is not loaded. Please refresh the page.');
        return;
    }
    
    Swal.fire({
        title: 'Reject Disbursement',
        html: `
            <div class="text-start">
                <p class="mb-3">Please provide a reason for rejecting this loan disbursement:</p>
                <textarea id="rejection-reason" class="form-control" rows="4" placeholder="Enter rejection reason..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const reason = document.getElementById('rejection-reason').value;
            if (!reason || reason.trim() === '') {
                Swal.showValidationMessage('Please provide a rejection reason');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        console.log('SweetAlert result:', result);
        
        if (result.isConfirmed) {
            console.log('Rejection confirmed, processing...');
            
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Rejecting loan disbursement...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Get loan type from the row
            const loanType = $(`tr[data-loan-id="${loanId}"]`).data('loan-type') || 'personal';
            console.log('Loan Type:', loanType);
            console.log('Rejection Reason:', result.value);

            // Construct the URL
            const url = '{{ url("/admin/loans") }}/' + loanId + '/reject';
            console.log('AJAX URL:', url);

            // Send rejection request
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    loan_type: loanType,
                    comments: result.value
                },
                success: function(response) {
                    console.log('Success response:', response);
                    Swal.fire({
                        title: 'Rejected!',
                        text: response.message || 'The loan disbursement has been rejected successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    console.error('Error response:', xhr);
                    let errorMessage = 'Failed to reject loan disbursement.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        console.error('Response Text:', xhr.responseText);
                    }
                    Swal.fire('Error', errorMessage, 'error');
                }
            });
        } else {
            console.log('Rejection cancelled');
        }
    }).catch((error) => {
        console.error('SweetAlert error:', error);
    });
}
</script>
@endpush
@endsection