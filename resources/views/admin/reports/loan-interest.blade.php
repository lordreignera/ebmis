@extends('layouts.admin')

@section('title', 'Loan Interest Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Loan Interest Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Loan Interest</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Interest Income Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="interestTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Interest by Product</h5>
                </div>
                <div class="card-body">
                    <canvas id="productInterestChart" height="300"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Collection Rate</span>
                            <strong>{{ number_format(($stats['interest_paid'] / max(1, $stats['total_interest_charged'])) * 100, 1) }}%</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: {{ ($stats['interest_paid'] / max(1, $stats['total_interest_charged'])) * 100 }}%"></div>
                        </div>
                    </div>
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
                    <form method="GET" action="{{ route('admin.reports.loan-interest') }}" class="row g-3">
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
                            <label for="interest_rate_range" class="form-label">Interest Rate</label>
                            <select class="form-select" id="interest_rate_range" name="interest_rate_range">
                                <option value="">All Rates</option>
                                <option value="0-10" {{ request('interest_rate_range') == '0-10' ? 'selected' : '' }}>0% - 10%</option>
                                <option value="10-20" {{ request('interest_rate_range') == '10-20' ? 'selected' : '' }}>10% - 20%</option>
                                <option value="20-30" {{ request('interest_rate_range') == '20-30' ? 'selected' : '' }}>20% - 30%</option>
                                <option value="30+" {{ request('interest_rate_range') == '30+' ? 'selected' : '' }}>30%+</option>
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
                    <h5 class="card-title mb-0">Loan Interest Details ({{ isset($data['loans']) ? count($data['loans']) : 0 }} records)</h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-info btn-sm" onclick="calculateAccruals()">
                            <i class="mdi mdi-calculator"></i> Calculate Accruals
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
                                    <th>Product</th>
                                    <th>Principal</th>
                                    <th>Interest Rate</th>
                                    <th>Interest Charged</th>
                                    <th>Interest Paid</th>
                                    <th>Interest Due</th>
                                    <th>Days Outstanding</th>
                                    <th>Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($data['loans']) && count($data['loans']) > 0)
                                @foreach($data['loans'] as $loan)
                                @php
                                    $interestDue = $loan->interest_amount - ($loan->interest_paid ?? 0);
                                    $daysOutstanding = $loan->disbursed_at ? $loan->disbursed_at->diffInDays(now()) : 0;
                                    $performance = $interestDue <= 0 ? 'Fully Paid' : ($interestDue / $loan->interest_amount > 0.5 ? 'Poor' : 'Good');
                                    $performanceClass = $interestDue <= 0 ? 'success' : ($interestDue / $loan->interest_amount > 0.5 ? 'danger' : 'warning');
                                @endphp
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
                                    <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                    <td>
                                        <strong>{{ number_format($loan->loan_amount) }} UGX</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $loan->interest_rate }}%</span>
                                        <br>
                                        <small class="text-muted">{{ $loan->interest_type ?? 'Fixed' }}</small>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ number_format($loan->interest_amount) }} UGX</strong>
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($loan->interest_paid ?? 0) }} UGX</strong>
                                    </td>
                                    <td>
                                        <strong class="{{ $interestDue > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($interestDue) }} UGX
                                        </strong>
                                    </td>
                                    <td>
                                        {{ $daysOutstanding }} days
                                        @if($daysOutstanding > 365)
                                            <br><small class="text-warning">Long term</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $performanceClass }}">{{ $performance }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewInterestBreakdown({{ $loan->id }})" title="Interest Breakdown">
                                                <i class="mdi mdi-chart-pie"></i>
                                            </button>
                                            @if($interestDue > 0)
                                            <a href="{{ route('payments.create', ['loan_id' => $loan->id]) }}" 
                                               class="btn btn-outline-success" title="Record Payment">
                                                <i class="mdi mdi-plus"></i>
                                            </a>
                                            @endif
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="calculatePenalty({{ $loan->id }})" title="Calculate Penalty">
                                                <i class="mdi mdi-calculator"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No loan interest data found</p>
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
                                Showing {{ count($data['loans']) }} loan records
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Interest Breakdown Modal -->
<div class="modal fade" id="interestBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Interest Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="interestBreakdownContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Accrual Calculation Modal -->
<div class="modal fade" id="accrualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calculate Interest Accruals</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="accrual_date" class="form-label">Accrual Date</label>
                    <input type="date" class="form-control" id="accrual_date" value="{{ date('Y-m-d') }}">
                </div>
                <div class="mb-3">
                    <label for="calculation_method" class="form-label">Calculation Method</label>
                    <select class="form-select" id="calculation_method">
                        <option value="daily">Daily Accrual</option>
                        <option value="monthly">Monthly Accrual</option>
                        <option value="compound">Compound Interest</option>
                    </select>
                </div>
                <div class="alert alert-info">
                    <i class="mdi mdi-information"></i>
                    This will calculate and post interest accruals for all active loans up to the selected date.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="performAccrualCalculation">Calculate</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Interest Trend Chart
const trendCtx = document.getElementById('interestTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Interest Charged (UGX)',
            data: [2500000, 3200000, 2800000, 4100000, 3600000, 4500000, 3900000, 4800000, 4200000, 5100000, 4700000, 5500000],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Interest Collected (UGX)',
            data: [2200000, 2900000, 2500000, 3700000, 3200000, 4000000, 3500000, 4300000, 3800000, 4600000, 4200000, 4900000],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'UGX ' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': UGX ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});

// Product Interest Chart
const productCtx = document.getElementById('productInterestChart').getContext('2d');
new Chart(productCtx, {
    type: 'bar',
    data: {
        labels: ['Personal Loan', 'Business Loan', 'Group Loan', 'Agricultural Loan'],
        datasets: [{
            label: 'Interest Income (UGX)',
            data: [12000000, 8500000, 6200000, 4100000],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'UGX ' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Income: UGX ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});

function viewInterestBreakdown(loanId) {
    $('#interestBreakdownContent').html('<div class="text-center p-3"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>');
    $('#interestBreakdownModal').modal('show');
    
    $.get(`/admin/loans/${loanId}/interest-breakdown`).done(function(data) {
        $('#interestBreakdownContent').html(data);
    }).fail(function() {
        $('#interestBreakdownContent').html('<div class="alert alert-danger">Failed to load interest breakdown</div>');
    });
}

function calculatePenalty(loanId) {
    $.post(`/admin/loans/${loanId}/calculate-penalty`, {
        _token: '{{ csrf_token() }}'
    }).done(function(response) {
        toastr.success('Penalty calculated and applied');
        location.reload();
    }).fail(function() {
        toastr.error('Failed to calculate penalty');
    });
}

function calculateAccruals() {
    $('#accrualModal').modal('show');
}

$('#performAccrualCalculation').click(function() {
    const accrualDate = $('#accrual_date').val();
    const method = $('#calculation_method').val();
    
    if (!accrualDate) {
        toastr.error('Please select an accrual date');
        return;
    }

    $.post('/admin/loans/calculate-accruals', {
        _token: '{{ csrf_token() }}',
        accrual_date: accrualDate,
        method: method
    }).done(function(response) {
        $('#accrualModal').modal('hide');
        toastr.success('Interest accruals calculated successfully');
        location.reload();
    }).fail(function() {
        toastr.error('Failed to calculate accruals');
    });
});

function exportToExcel() {
    window.open('{{ route("admin.reports.loan-interest") }}?{{ request()->getQueryString() }}&export=excel', '_blank');
}

function exportPDF() {
    window.open('{{ route("admin.reports.loan-interest") }}?{{ request()->getQueryString() }}&export=pdf', '_blank');
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