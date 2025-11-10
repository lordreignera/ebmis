@extends('layouts.admin')

@section('title', 'Portfolio by Branch')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Portfolio by Branch</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.portfolio.export-branch') }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export Report
            </a>
            <button type="button" class="btn btn-info" onclick="comparePerformance()">
                <i class="mdi mdi-chart-bar"></i> Compare Performance
            </button>
        </div>
    </div>

    <!-- Branch Performance Overview -->
    <div class="row mb-4">
        @foreach($branchStats as $stat)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ $stat['branch']->name }}
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ number_format($stat['total_loans']) }} Loans
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>Disbursed:</strong> UGX {{ number_format($stat['disbursed_amount']) }}<br>
                                    <strong>Outstanding:</strong> UGX {{ number_format($stat['outstanding_amount']) }}<br>
                                    <strong>Collected:</strong> UGX {{ number_format($stat['paid_amount']) }}
                                </small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-office-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    
                    <!-- Performance Indicators -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-success font-weight-bold">{{ $stat['running_loans'] }}</div>
                                    <small class="text-muted">Running</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-danger font-weight-bold">{{ $stat['overdue_loans'] }}</div>
                                    <small class="text-muted">Overdue</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collection Rate Progress -->
                    <div class="mt-3">
                        @php
                            $collectionRate = $stat['disbursed_amount'] > 0 ? ($stat['paid_amount'] / $stat['disbursed_amount']) * 100 : 0;
                        @endphp
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $collectionRate >= 80 ? 'success' : ($collectionRate >= 60 ? 'warning' : 'danger') }}" 
                                 role="progressbar" style="width: {{ $collectionRate }}%" 
                                 aria-valuenow="{{ $collectionRate }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">Collection Rate: {{ number_format($collectionRate, 1) }}%</small>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-3">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="{{ route('admin.branches.show', $stat['branch']->id) }}" class="btn btn-outline-primary">
                                <i class="mdi mdi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.portfolio.running', ['branch_id' => $stat['branch']->id]) }}" class="btn btn-outline-success">
                                <i class="mdi mdi-format-list-bulleted"></i> Loans
                            </a>
                            <button type="button" class="btn btn-outline-info" onclick="viewAnalytics({{ $stat['branch']->id }})">
                                <i class="mdi mdi-chart-line"></i> Analytics
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Detailed Comparison Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="mdi mdi-compare"></i> Branch Performance Comparison
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="branchTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Branch</th>
                            <th>Total Loans</th>
                            <th>Disbursed Amount</th>
                            <th>Outstanding</th>
                            <th>Collected</th>
                            <th>Collection Rate</th>
                            <th>Running Loans</th>
                            <th>Overdue Loans</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchStats as $stat)
                        @php
                            $collectionRate = $stat['disbursed_amount'] > 0 ? ($stat['paid_amount'] / $stat['disbursed_amount']) * 100 : 0;
                            $overdueRate = $stat['total_loans'] > 0 ? ($stat['overdue_loans'] / $stat['total_loans']) * 100 : 0;
                            $performance = $collectionRate >= 80 && $overdueRate <= 10 ? 'excellent' : 
                                         ($collectionRate >= 60 && $overdueRate <= 20 ? 'good' : 'needs-improvement');
                            $performanceColor = $performance == 'excellent' ? 'success' : 
                                              ($performance == 'good' ? 'warning' : 'danger');
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px; font-size: 12px;">
                                            {{ strtoupper(substr($stat['branch']->name, 0, 2)) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">{{ $stat['branch']->name }}</div>
                                        <small class="text-muted">{{ $stat['branch']->location ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <strong>{{ number_format($stat['total_loans']) }}</strong>
                            </td>
                            <td>
                                <strong>UGX {{ number_format($stat['disbursed_amount']) }}</strong>
                            </td>
                            <td>
                                <span class="text-warning font-weight-bold">UGX {{ number_format($stat['outstanding_amount']) }}</span>
                            </td>
                            <td>
                                <span class="text-success">UGX {{ number_format($stat['paid_amount']) }}</span>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-{{ $collectionRate >= 80 ? 'success' : ($collectionRate >= 60 ? 'warning' : 'danger') }}" 
                                         role="progressbar" style="width: {{ $collectionRate }}%">
                                        {{ number_format($collectionRate, 1) }}%
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success">{{ $stat['running_loans'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $stat['overdue_loans'] > 0 ? 'danger' : 'success' }}">
                                    {{ $stat['overdue_loans'] }}
                                </span>
                                @if($stat['overdue_loans'] > 0)
                                    <br><small class="text-muted">{{ number_format($overdueRate, 1) }}%</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $performanceColor }}">
                                    {{ ucfirst(str_replace('-', ' ', $performance)) }}
                                </span>
                                <div class="mt-1">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.branches.show', $stat['branch']->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.portfolio.running', ['branch_id' => $stat['branch']->id]) }}" class="btn btn-outline-success btn-sm">
                                            <i class="mdi mdi-format-list-bulleted"></i>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Disbursement by Branch</h6>
                </div>
                <div class="card-body">
                    <canvas id="disbursementChart" width="100%" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Collection Performance</h6>
                </div>
                <div class="card-body">
                    <canvas id="collectionChart" width="100%" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    initializeCharts();
});

function initializeCharts() {
    // Prepare data for charts
    const branchNames = @json($branchStats->pluck('branch.name'));
    const disbursedAmounts = @json($branchStats->pluck('disbursed_amount'));
    const collectedAmounts = @json($branchStats->pluck('paid_amount'));
    
    // Disbursement Chart
    const disbursementCtx = document.getElementById('disbursementChart').getContext('2d');
    new Chart(disbursementCtx, {
        type: 'bar',
        data: {
            labels: branchNames,
            datasets: [{
                label: 'Disbursed Amount (UGX)',
                data: disbursedAmounts,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
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
                            return 'Disbursed: UGX ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Collection Performance Chart
    const collectionCtx = document.getElementById('collectionChart').getContext('2d');
    new Chart(collectionCtx, {
        type: 'doughnut',
        data: {
            labels: branchNames,
            datasets: [{
                data: collectedAmounts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed * 100) / total).toFixed(1);
                            return context.label + ': UGX ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

function comparePerformance() {
    showAlert('info', 'Performance comparison feature coming soon');
}

function viewAnalytics(branchId) {
    showAlert('info', 'Branch analytics feature coming soon');
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