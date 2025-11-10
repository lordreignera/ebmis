@extends('layouts.admin')

@section('title', 'Paid Loans Portfolio')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Paid Loans Portfolio</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.loans.export', ['status' => 'paid']) }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export
            </a>
            <button type="button" class="btn btn-info" onclick="generateReport()">
                <i class="mdi mdi-file-chart"></i> Performance Report
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Paid Loans
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_paid']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Paid Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">UGX {{ number_format($stats['total_paid_amount']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-cash-multiple fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Average Payment Period
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['average_payment_period'] ?? 0, 1) }} days</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Collection Rate
                            </div>
                            @php
                                $totalLoans = \App\Models\Loan::whereIn('status', ['disbursed', 'paid'])->count();
                                $paidLoans = $stats['total_paid'];
                                $collectionRate = $totalLoans > 0 ? ($paidLoans / $totalLoans) * 100 : 0;
                            @endphp
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($collectionRate, 1) }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-trending-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Payment Completions</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyPaymentsChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Payment Performance by Product</h6>
                </div>
                <div class="card-body">
                    <canvas id="productPerformanceChart" width="100%" height="50"></canvas>
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
            <form method="GET" action="{{ route('admin.portfolio.paid') }}" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="Loan ID, Member name...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="start_date">Paid From</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               value="{{ request('start_date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="end_date">Paid To</label>
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="product_id">Product</label>
                        <select name="product_id" id="product_id" class="form-control">
                            <option value="">All Products</option>
                            @foreach(\App\Models\Product::loanProducts()->get() as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
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
                            <a href="{{ route('admin.portfolio.paid') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Paid Loans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-check-circle"></i> Completed Loans
                <span class="badge badge-success ml-2">{{ $loans->total() }}</span>
            </h6>
        </div>
        <div class="card-body">
            @if($loans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Loan Details</th>
                                <th>Member</th>
                                <th>Amount Details</th>
                                <th>Payment Period</th>
                                <th>Final Payment</th>
                                <th>Performance</th>
                                <th>Interest Earned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loans as $loan)
                            @php
                                $disbursedDate = \Carbon\Carbon::parse($loan->disbursed_at);
                                $paidDate = \Carbon\Carbon::parse($loan->paid_date);
                                $actualDays = $disbursedDate->diffInDays($paidDate);
                                $expectedDays = $loan->loan_period * 30; // Approximate days
                                $performance = $actualDays <= $expectedDays ? 'excellent' : ($actualDays <= $expectedDays * 1.1 ? 'good' : 'fair');
                                $performanceColor = $performance == 'excellent' ? 'success' : ($performance == 'good' ? 'info' : 'warning');
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $loan->loan_id }}</strong><br>
                                    <span class="badge badge-info">{{ $loan->product->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ ucfirst($loan->loan_type) }} • {{ $loan->loan_period }}m</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($loan->member->fname, 0, 1) . substr($loan->member->lname, 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">{{ $loan->member->fname }} {{ $loan->member->lname }}</div>
                                            <small class="text-muted">{{ $loan->member->pm_code }}</small><br>
                                            <small class="text-muted">
                                                <i class="mdi mdi-phone"></i> {{ $loan->member->contact }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <small class="text-muted">Principal:</small>
                                        <strong>UGX {{ number_format($loan->loan_amount) }}</strong>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted">Total Paid:</small>
                                        <span class="text-success font-weight-bold">UGX {{ number_format($loan->paid_amount) }}</span>
                                    </div>
                                    <div>
                                        <small class="text-muted">Interest Rate:</small>
                                        <span>{{ $loan->interest_rate }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <small class="text-muted">Disbursed:</small>
                                        <div>{{ $disbursedDate->format('M d, Y') }}</div>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted">Completed:</small>
                                        <div>{{ $paidDate->format('M d, Y') }}</div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Duration:</small>
                                        <div><strong>{{ $actualDays }} days</strong></div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $finalPayment = $loan->payments()->latest()->first();
                                    @endphp
                                    @if($finalPayment)
                                        <div class="mb-1">
                                            <strong>UGX {{ number_format($finalPayment->amount) }}</strong>
                                        </div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($finalPayment->payment_date)->format('M d, Y') }}</small><br>
                                        <span class="badge badge-success">Final Payment</span>
                                    @else
                                        <span class="text-muted">No payment record</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $performanceColor }}">
                                        {{ ucfirst($performance) }}
                                    </span>
                                    <div class="mt-1">
                                        @if($actualDays <= $expectedDays)
                                            <small class="text-success">
                                                <i class="mdi mdi-check"></i> On time
                                            </small>
                                        @else
                                            <small class="text-warning">
                                                <i class="mdi mdi-clock"></i> {{ $actualDays - $expectedDays }} days late
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $interestEarned = $loan->paid_amount - $loan->loan_amount;
                                    @endphp
                                    <strong class="text-success">UGX {{ number_format($interestEarned) }}</strong>
                                    <div class="mt-1">
                                        @php
                                            $profitMargin = $loan->loan_amount > 0 ? ($interestEarned / $loan->loan_amount) * 100 : 0;
                                        @endphp
                                        <small class="text-muted">{{ number_format($profitMargin, 1) }}% return</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.loans.statement', $loan->id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="mdi mdi-file-document"></i>
                                        </a>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.loans.payment-history', $loan->id) }}">
                                                    <i class="mdi mdi-history"></i> Payment History
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.members.show', $loan->member->id) }}">
                                                    <i class="mdi mdi-account"></i> View Member
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="#" onclick="generateCertificate({{ $loan->id }})">
                                                    <i class="mdi mdi-certificate"></i> Completion Certificate
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="markForRenewal({{ $loan->id }})">
                                                    <i class="mdi mdi-refresh"></i> Mark for Renewal
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
                    <i class="mdi mdi-check-circle-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No paid loans found</h5>
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'branch_id', 'product_id']))
                        <p class="text-muted">Try adjusting your filters or <a href="{{ route('admin.portfolio.paid') }}">clear all filters</a></p>
                    @else
                        <p class="text-muted">No loans have been completed yet</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#branch_id, #product_id').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Initialize charts
    initializeCharts();
});

function initializeCharts() {
    // Monthly Payments Chart
    const monthlyCtx = document.getElementById('monthlyPaymentsChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Completed Loans',
                data: [12, 19, 13, 25, 22, 13, 20, 25, 30, 28, 35, 42],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Product Performance Chart
    const productCtx = document.getElementById('productPerformanceChart').getContext('2d');
    new Chart(productCtx, {
        type: 'doughnut',
        data: {
            labels: ['Personal Loans', 'Business Loans', 'Group Loans', 'Emergency Loans'],
            datasets: [{
                data: [45, 30, 15, 10],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function generateReport() {
    showAlert('info', 'Performance report generation feature coming soon');
}

function generateCertificate(loanId) {
    showAlert('info', 'Certificate generation feature coming soon');
}

function markForRenewal(loanId) {
    if (confirm('Mark this customer for loan renewal?')) {
        showAlert('info', 'Renewal marking feature coming soon');
    }
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