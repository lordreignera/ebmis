@extends('layouts.admin')

@section('title', 'Pending Loans Portfolio')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pending Loans Portfolio</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.loans.export', ['status' => 'pending']) }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export
            </a>
            <button type="button" class="btn btn-primary" onclick="bulkApprove()">
                <i class="mdi mdi-check-all"></i> Bulk Approve
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Pending Applications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_pending']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-clock-outline fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Pending Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['pending_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-currency-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Applications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['today_applications']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-calendar-today fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-flash"></i> Quick Actions
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <button type="button" class="btn btn-success btn-block" onclick="approveSelected()">
                        <i class="mdi mdi-check"></i> Approve Selected
                    </button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger btn-block" onclick="rejectSelected()">
                        <i class="mdi mdi-close"></i> Reject Selected
                    </button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-info btn-block" onclick="requestDocuments()">
                        <i class="mdi mdi-file-document"></i> Request Documents
                    </button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-warning btn-block" onclick="scheduleVisit()">
                        <i class="mdi mdi-calendar-clock"></i> Schedule Visit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-filter-variant"></i> Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.portfolio.pending') }}" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="Loan ID, Member name...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status_filter">Status</label>
                        <select name="status_filter" id="status_filter" class="form-control">
                            <option value="">All Pending</option>
                            <option value="pending" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Pending Review</option>
                            <option value="approved" {{ request('status_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               value="{{ request('start_date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="branch_id">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-control">
                            <option value="">All Branches</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <a href="{{ route('admin.portfolio.pending') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-format-list-bulleted"></i> Pending Loan Applications
                <span class="badge badge-warning ml-2">{{ $loans->total() }}</span>
            </h6>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="selectAll">
                <label class="custom-control-label" for="selectAll">Select All</label>
            </div>
        </div>
        <div class="card-body">
            @if($loans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th width="30px">
                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                </th>
                                <th>Application ID</th>
                                <th>Member</th>
                                <th>Product</th>
                                <th>Amount Requested</th>
                                <th>Purpose</th>
                                <th>Application Date</th>
                                <th>Days Pending</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_loans[]" value="{{ $loan->id }}" class="loan-checkbox form-check-input">
                                </td>
                                <td>
                                    <strong>{{ $loan->loan_id }}</strong><br>
                                    <small class="text-muted">{{ $loan->created_at->format('M d, Y H:i') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($loan->member->fname, 0, 1) . substr($loan->member->lname, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $loan->member->fname }} {{ $loan->member->lname }}</div>
                                            <small class="text-muted">{{ $loan->member->pm_code }}</small><br>
                                            <small class="text-muted">{{ $loan->member->contact }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $loan->product->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ ucfirst($loan->loan_type) }} Loan</small><br>
                                    <small class="text-muted">{{ $loan->loan_period }} months</small>
                                </td>
                                <td>
                                    <strong class="text-primary">UGX {{ number_format($loan->loan_amount) }}</strong><br>
                                    <small class="text-muted">{{ $loan->interest_rate }}% interest</small>
                                </td>
                                <td>
                                    @if($loan->purpose)
                                        <span data-toggle="tooltip" title="{{ $loan->purpose }}">
                                            {{ Str::limit($loan->purpose, 30) }}
                                        </span>
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </td>
                                <td>
                                    <span>{{ $loan->created_at->format('M d, Y') }}</span><br>
                                    <small class="text-muted">{{ $loan->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    @php
                                        $daysPending = $loan->created_at->diffInDays(now());
                                    @endphp
                                    <span class="badge 
                                        {{ $daysPending <= 1 ? 'badge-success' : ($daysPending <= 3 ? 'badge-warning' : 'badge-danger') }}">
                                        {{ $daysPending }} {{ $daysPending == 1 ? 'day' : 'days' }}
                                    </span>
                                </td>
                                <td>
                                    @switch($loan->status)
                                        @case('pending')
                                            <span class="badge badge-warning">Pending Review</span>
                                            @break
                                        @case('approved')
                                            <span class="badge badge-success">Approved</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($loan->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if($loan->status == 'pending')
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="approveLoan({{ $loan->id }})">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="rejectLoan({{ $loan->id }})">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                        @endif
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.loans.edit', $loan->id) }}">
                                                    <i class="mdi mdi-pencil"></i> Edit
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.members.show', $loan->member->id) }}">
                                                    <i class="mdi mdi-account"></i> View Member
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#" onclick="requestDocuments({{ $loan->id }})">
                                                    <i class="mdi mdi-file-document"></i> Request Documents
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="scheduleVisit({{ $loan->id }})">
                                                    <i class="mdi mdi-calendar-clock"></i> Schedule Visit
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <p class="text-muted">
                            Showing {{ $loans->firstItem() }} to {{ $loans->lastItem() }} of {{ $loans->total() }} results
                        </p>
                    </div>
                    <div>
                        {{ $loans->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="mdi mdi-clock-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No pending applications found</h5>
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'branch_id', 'status_filter']))
                        <p class="text-muted">Try adjusting your filters or <a href="{{ route('admin.portfolio.pending') }}">clear all filters</a></p>
                    @else
                        <p class="text-muted">All loan applications have been processed</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all functionality
    $('#selectAll, #selectAllTable').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.loan-checkbox').prop('checked', isChecked);
        $('#selectAll, #selectAllTable').prop('checked', isChecked);
    });

    // Individual checkbox change
    $('.loan-checkbox').on('change', function() {
        var totalCheckboxes = $('.loan-checkbox').length;
        var checkedCheckboxes = $('.loan-checkbox:checked').length;
        
        $('#selectAll, #selectAllTable').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Auto-submit form on filter change
    $('#branch_id, #status_filter').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function getSelectedLoans() {
    return $('.loan-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
}

function approveSelected() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select at least one loan to approve');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${selectedLoans.length} loan(s)?`)) {
        bulkAction('approve', selectedLoans);
    }
}

function rejectSelected() {
    var selectedLoans = getSelectedLoans();
    if (selectedLoans.length === 0) {
        showAlert('warning', 'Please select at least one loan to reject');
        return;
    }
    
    if (confirm(`Are you sure you want to reject ${selectedLoans.length} loan(s)?`)) {
        var reason = prompt('Please provide a reason for rejection:');
        if (reason) {
            bulkAction('reject', selectedLoans, reason);
        }
    }
}

function approveLoan(loanId) {
    if (confirm('Are you sure you want to approve this loan?')) {
        bulkAction('approve', [loanId]);
    }
}

function rejectLoan(loanId) {
    if (confirm('Are you sure you want to reject this loan?')) {
        var reason = prompt('Please provide a reason for rejection:');
        if (reason) {
            bulkAction('reject', [loanId], reason);
        }
    }
}

function bulkAction(action, loanIds, reason = null) {
    var formData = {
        _token: '{{ csrf_token() }}',
        action: action,
        loan_ids: loanIds,
        reason: reason
    };

    $.ajax({
        url: '{{ route("admin.loans.bulk-action") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            showAlert('success', response.message);
            setTimeout(function() {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            var message = xhr.responseJSON?.message || 'An error occurred';
            showAlert('danger', message);
        }
    });
}

function requestDocuments(loanId = null) {
    var loanIds = loanId ? [loanId] : getSelectedLoans();
    if (loanIds.length === 0) {
        showAlert('warning', 'Please select at least one loan');
        return;
    }
    
    // Implement document request functionality
    showAlert('info', 'Document request feature coming soon');
}

function scheduleVisit(loanId = null) {
    var loanIds = loanId ? [loanId] : getSelectedLoans();
    if (loanIds.length === 0) {
        showAlert('warning', 'Please select at least one loan');
        return;
    }
    
    // Implement visit scheduling functionality
    showAlert('info', 'Visit scheduling feature coming soon');
}

function showAlert(type, message) {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').first().alert('close');
    }, 5000);
}
</script>
@endpush