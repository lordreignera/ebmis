@extends('layouts.admin')

@section('title', 'Rejected Loans Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Rejected Loans Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Rejected Loans</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-danger text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-close-circle widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Total Rejected">Total Rejected</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['total_rejected']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-warning text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-currency-usd widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Rejected Amount">Rejected Amount</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['rejected_amount']) }} UGX</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-info text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-calendar-today widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Today's Rejections">Today's Rejections</h5>
                    <h3 class="mt-3 mb-3">{{ number_format($stats['today_rejected']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card widget-flat bg-secondary text-white">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-percent widget-icon"></i>
                    </div>
                    <h5 class="text-white fw-normal mt-0" title="Rejection Rate">Rejection Rate</h5>
                    <h3 class="mt-3 mb-3">{{ number_format(($stats['total_rejected'] / max(1, $stats['total_rejected'] + 100)) * 100, 1) }}%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rejection Reasons Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="rejectionReasonsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Rejection Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="rejectionTrendChart" height="300"></canvas>
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
                    <form method="GET" action="{{ route('admin.reports.rejected-loans') }}" class="row g-3">
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
                            <label for="rejection_reason" class="form-label">Rejection Reason</label>
                            <select class="form-select" id="rejection_reason" name="rejection_reason">
                                <option value="">All Reasons</option>
                                <option value="insufficient_income" {{ request('rejection_reason') == 'insufficient_income' ? 'selected' : '' }}>Insufficient Income</option>
                                <option value="poor_credit_history" {{ request('rejection_reason') == 'poor_credit_history' ? 'selected' : '' }}>Poor Credit History</option>
                                <option value="incomplete_documents" {{ request('rejection_reason') == 'incomplete_documents' ? 'selected' : '' }}>Incomplete Documents</option>
                                <option value="high_debt_ratio" {{ request('rejection_reason') == 'high_debt_ratio' ? 'selected' : '' }}>High Debt Ratio</option>
                                <option value="policy_violation" {{ request('rejection_reason') == 'policy_violation' ? 'selected' : '' }}>Policy Violation</option>
                                <option value="other" {{ request('rejection_reason') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="mdi mdi-magnify"></i> Filter
                            </button>
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
                    <h5 class="card-title mb-0">Rejected Loans ({{ isset($data['loans']) ? count($data['loans']) : 0 }} records)</h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="bulkReactivate()">
                            <i class="mdi mdi-refresh"></i> Bulk Reactivate
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Loan ID</th>
                                    <th>Member</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Applied Date</th>
                                    <th>Rejected Date</th>
                                    <th>Rejection Reason</th>
                                    <th>Rejected By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($data['loans']) && count($data['loans']) > 0)
                                @foreach($data['loans'] as $loan)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input loan-checkbox" value="{{ $loan->id }}">
                                    </td>
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
                                    <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                    <td>
                                        <strong class="text-danger">{{ number_format($loan->loan_amount) }} UGX</strong>
                                    </td>
                                    <td>{{ $loan->created_at->format('d M Y') }}</td>
                                    <td>{{ $loan->updated_at->format('d M Y') }}</td>
                                    <td>
                                        @if($loan->rejection_reason)
                                            <span class="badge bg-warning">{{ ucwords(str_replace('_', ' ', $loan->rejection_reason)) }}</span>
                                            @if($loan->rejection_notes)
                                                <br><small class="text-muted">{{ Str::limit($loan->rejection_notes, 50) }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($loan->rejected_by)
                                            {{ $loan->rejectedBy->name ?? 'N/A' }}
                                            <br><small class="text-muted">{{ $loan->rejectedBy->role ?? 'Staff' }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewRejectionDetails({{ $loan->id }})" title="Rejection Details">
                                                <i class="mdi mdi-information"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="reactivateLoan({{ $loan->id }})" title="Reactivate">
                                                <i class="mdi mdi-refresh"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="contactMember({{ $loan->member->id }})" title="Contact Member">
                                                <i class="mdi mdi-phone"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No rejected loans found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        @if(isset($data['loans']) && count($data['loans']) > 0)
                        <div>
                            <small class="text-muted">
                                Showing {{ count($data['loans']) }} rejected loans
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Details Modal -->
<div class="modal fade" id="rejectionDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejection Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rejectionDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reactivate Modal -->
<div class="modal fade" id="reactivateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reactivate Loan Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="reactivation_reason" class="form-label">Reactivation Reason</label>
                    <textarea class="form-control" id="reactivation_reason" rows="3" 
                              placeholder="Enter reason for reactivating this loan application..."></textarea>
                </div>
                <div class="mb-3">
                    <label for="new_status" class="form-label">New Status</label>
                    <select class="form-select" id="new_status">
                        <option value="pending">Pending Review</option>
                        <option value="under_review">Under Review</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmReactivate">Reactivate</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentLoanId = null;

// Select All functionality
$('#selectAll').change(function() {
    $('.loan-checkbox').prop('checked', this.checked);
});

$('.loan-checkbox').change(function() {
    if (!this.checked) {
        $('#selectAll').prop('checked', false);
    } else if ($('.loan-checkbox:checked').length === $('.loan-checkbox').length) {
        $('#selectAll').prop('checked', true);
    }
});

// Rejection Reasons Chart
const reasonsCtx = document.getElementById('rejectionReasonsChart').getContext('2d');
new Chart(reasonsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Insufficient Income', 'Poor Credit History', 'Incomplete Documents', 'High Debt Ratio', 'Policy Violation', 'Other'],
        datasets: [{
            data: [35, 25, 20, 10, 7, 3],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Rejection Trend Chart
const trendCtx = document.getElementById('rejectionTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Rejections',
            data: [12, 15, 8, 22, 18, 14, 16, 19, 11, 25, 20, 17],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function viewRejectionDetails(loanId) {
    $('#rejectionDetailsContent').html('<div class="text-center p-3"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>');
    $('#rejectionDetailsModal').modal('show');
    
    $.get(`/admin/loans/${loanId}/rejection-details`).done(function(data) {
        $('#rejectionDetailsContent').html(data);
    }).fail(function() {
        $('#rejectionDetailsContent').html('<div class="alert alert-danger">Failed to load rejection details</div>');
    });
}

function reactivateLoan(loanId) {
    currentLoanId = loanId;
    $('#reactivateModal').modal('show');
}

$('#confirmReactivate').click(function() {
    const reason = $('#reactivation_reason').val();
    const newStatus = $('#new_status').val();
    
    if (!reason.trim()) {
        toastr.error('Please enter a reactivation reason');
        return;
    }

    if (currentLoanId) {
        $.post(`/admin/loans/${currentLoanId}/reactivate`, {
            _token: '{{ csrf_token() }}',
            reason: reason,
            new_status: newStatus
        }).done(function() {
            $('#reactivateModal').modal('hide');
            toastr.success('Loan application reactivated successfully');
            location.reload();
        }).fail(function() {
            toastr.error('Failed to reactivate loan application');
        });
    }
});

function bulkReactivate() {
    const selectedLoans = $('.loan-checkbox:checked').map(function() {
        return this.value;
    }).get();

    if (selectedLoans.length === 0) {
        toastr.warning('Please select loans to reactivate');
        return;
    }

    if (confirm(`Are you sure you want to reactivate ${selectedLoans.length} loan applications?`)) {
        $.post('/admin/loans/bulk-reactivate', {
            _token: '{{ csrf_token() }}',
            loan_ids: selectedLoans
        }).done(function() {
            toastr.success('Loan applications reactivated successfully');
            location.reload();
        }).fail(function() {
            toastr.error('Failed to reactivate loan applications');
        });
    }
}

function contactMember(memberId) {
    window.open(`/admin/members/${memberId}/contact`, '_blank');
}

function exportToExcel() {
    window.open('{{ route("admin.reports.rejected-loans") }}?{{ request()->getQueryString() }}&export=excel', '_blank');
}

function exportToPDF() {
    window.open('{{ route("admin.reports.rejected-loans") }}?{{ request()->getQueryString() }}&export=pdf', '_blank');
}
</script>
@endpush