@extends('layouts.admin')

@section('title', 'Portfolio by Product')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Portfolio by Product</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.portfolio.export-product') }}" class="btn btn-success">
                <i class="mdi mdi-download"></i> Export Report
            </a>
            <a href="{{ route('admin.loan-products.create') }}" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> New Product
            </a>
        </div>
    </div>

    <!-- Product Performance Overview -->
    <div class="row mb-4">
        @foreach($productStats as $stat)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                {{ $stat['product']->name }}
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
                                    <strong>Interest Rate:</strong> {{ $stat['product']->interest_rate }}%<br>
                                    <strong>Max Amount:</strong> UGX {{ number_format($stat['product']->max_amount) }}<br>
                                    <strong>Max Period:</strong> {{ $stat['product']->max_period }} months
                                </small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="mdi mdi-package-variant fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    
                    <!-- Performance Metrics -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-primary font-weight-bold">UGX {{ number_format($stat['disbursed_amount']) }}</div>
                                    <small class="text-muted">Disbursed</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-success font-weight-bold">UGX {{ number_format($stat['paid_amount']) }}</div>
                                    <small class="text-muted">Collected</small>
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

                    <!-- Status Overview -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-4 text-center">
                                <div class="text-success font-weight-bold">{{ $stat['running_loans'] }}</div>
                                <small class="text-muted">Running</small>
                            </div>
                            <div class="col-4 text-center">
                                <div class="text-danger font-weight-bold">{{ $stat['overdue_loans'] }}</div>
                                <small class="text-muted">Overdue</small>
                            </div>
                            <div class="col-4 text-center">
                                <div class="text-warning font-weight-bold">UGX {{ number_format($stat['outstanding_amount']) }}</div>
                                <small class="text-muted">Outstanding</small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-3">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="{{ route('admin.loan-products.show', $stat['product']->id) }}" class="btn btn-outline-primary">
                                <i class="mdi mdi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.portfolio.running', ['product_id' => $stat['product']->id]) }}" class="btn btn-outline-success">
                                <i class="mdi mdi-format-list-bulleted"></i> Loans
                            </a>
                            <a href="{{ route('admin.loan-products.edit', $stat['product']->id) }}" class="btn btn-outline-info">
                                <i class="mdi mdi-pencil"></i> Edit
                            </a>
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
                <i class="mdi mdi-compare"></i> Product Performance Analysis
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="productTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Interest Rate</th>
                            <th>Total Loans</th>
                            <th>Disbursed</th>
                            <th>Outstanding</th>
                            <th>Collected</th>
                            <th>Collection Rate</th>
                            <th>Profitability</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productStats as $stat)
                        @php
                            $collectionRate = $stat['disbursed_amount'] > 0 ? ($stat['paid_amount'] / $stat['disbursed_amount']) * 100 : 0;
                            $profit = $stat['paid_amount'] - $stat['disbursed_amount'];
                            $profitability = $stat['disbursed_amount'] > 0 ? ($profit / $stat['disbursed_amount']) * 100 : 0;
                            $performanceColor = $collectionRate >= 80 ? 'success' : ($collectionRate >= 60 ? 'warning' : 'danger');
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px; font-size: 12px;">
                                            {{ strtoupper(substr($stat['product']->name, 0, 2)) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">{{ $stat['product']->name }}</div>
                                        <small class="text-muted">{{ $stat['product']->description ?? 'No description' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <strong>{{ $stat['product']->interest_rate }}%</strong><br>
                                <small class="text-muted">{{ ucfirst($stat['product']->interest_type ?? 'annual') }}</small>
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
                                    <div class="progress-bar bg-{{ $performanceColor }}" 
                                         role="progressbar" style="width: {{ $collectionRate }}%">
                                        {{ number_format($collectionRate, 1) }}%
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="font-weight-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    UGX {{ number_format($profit) }}
                                </div>
                                <small class="text-muted">{{ number_format($profitability, 1) }}% ROI</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.loan-products.show', $stat['product']->id) }}" class="btn btn-outline-primary">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.portfolio.running', ['product_id' => $stat['product']->id]) }}" class="btn btn-outline-success">
                                        <i class="mdi mdi-format-list-bulleted"></i>
                                    </a>
                                    <a href="{{ route('admin.loan-products.edit', $stat['product']->id) }}" class="btn btn-outline-info">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
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
                    <h6 class="m-0 font-weight-bold text-primary">Product Popularity</h6>
                </div>
                <div class="card-body">
                    <canvas id="popularityChart" width="100%" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue by Product</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" width="100%" height="100"></canvas>
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
    const productNames = @json($productStats->pluck('product.name'));
    const loanCounts = @json($productStats->pluck('total_loans'));
    const revenues = @json($productStats->map(function($stat) { return $stat['paid_amount'] - $stat['disbursed_amount']; }));
    
    // Product Popularity Chart
    const popularityCtx = document.getElementById('popularityChart').getContext('2d');
    new Chart(popularityCtx, {
        type: 'doughnut',
        data: {
            labels: productNames,
            datasets: [{
                data: loanCounts,
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
                            return context.label + ': ' + context.parsed + ' loans (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: productNames,
            datasets: [{
                label: 'Net Revenue (UGX)',
                data: revenues,
                backgroundColor: revenues.map(revenue => revenue >= 0 ? 'rgba(75, 192, 192, 0.8)' : 'rgba(255, 99, 132, 0.8)'),
                borderColor: revenues.map(revenue => revenue >= 0 ? 'rgba(75, 192, 192, 1)' : 'rgba(255, 99, 132, 1)'),
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
                            return 'Revenue: UGX ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
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