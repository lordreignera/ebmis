@extends('layouts.admin')

@section('title', 'Rejected Loans Report')

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

    <!-- Chart Section -->
    <div class="row">
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Chart Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.rejected-loans') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="chart_year" class="form-label">Year for Charts</label>
                            <select class="form-select" id="chart_year" name="chart_year">
                                @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                    <option value="{{ $year }}" {{ request('chart_year', date('Y')) == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="mdi mdi-chart-line"></i> Update Charts
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rejection Reasons Distribution ({{ request('chart_year', date('Y')) }})</h5>
                </div>
                <div class="card-body">
                    <canvas id="rejectionReasonsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Rejection Trend ({{ request('chart_year', date('Y')) }})</h5>
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
                        <table class="table table-striped table-hover modern-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Loan ID</th>
                                    <th>Member</th>
                                    <th>Loan Type</th>
                                    <th>Branch</th>
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
                                        <strong>{{ $loan->code ?? '#' . $loan->id }}</strong>
                                    </td>
                                    <td>
                                        @if($loan->loan_type === 'Personal Loan' && $loan->member)
                                            <div>
                                                <strong>{{ $loan->member->fname }} {{ $loan->member->lname }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $loan->member->account ?? 'N/A' }}</small>
                                            </div>
                                        @elseif($loan->loan_type === 'Group Loan' && $loan->group)
                                            <div>
                                                <strong>{{ $loan->group->name }}</strong>
                                                <br>
                                                <small class="text-muted">Group Loan</small>
                                            </div>
                                        @else
                                            <span class="text-muted">No data</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($loan->loan_type === 'Personal Loan')
                                            <span class="badge bg-primary">Personal Loan</span>
                                        @elseif($loan->loan_type === 'Group Loan')
                                            <span class="badge bg-info">Group Loan</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $loan->branch->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <strong class="text-danger">{{ number_format($loan->principal) }} UGX</strong>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($loan->datecreated)->format('d M Y') }}</td>
                                    <td>
                                        @if($loan->verified == 2)
                                            {{ \Carbon\Carbon::parse($loan->datecreated)->format('d M Y') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($loan->Rcomments)
                                            <span class="badge bg-warning">{{ Str::limit($loan->Rcomments, 30) }}</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($loan->added_by)
                                            <span class="text-muted">Staff #{{ $loan->added_by }}</span>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" onclick="viewLoanDetails({{ $loan->id }})" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewRejectionDetails({{ $loan->id }})" title="Rejection Details">
                                                <i class="mdi mdi-information"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="reactivateLoan({{ $loan->id }})" title="Reactivate">
                                                <i class="mdi mdi-refresh"></i>
                                            </button>
                                            @if($loan->loan_type === 'Personal Loan' && $loan->member)
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="contactMember({{ $loan->member->id }})" title="Contact Member">
                                                <i class="mdi mdi-phone"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No rejected loans found</p>
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

@push('styles')
<style>
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