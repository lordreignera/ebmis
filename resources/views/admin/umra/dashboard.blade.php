@extends('layouts.admin')

@section('title', 'UMRA Dashboard - Executive Portfolio Indicators')

@push('styles')
<style>
    .umra-dashboard {
        color: #1f2937;
    }

    .umra-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1.25rem 1.5rem;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }

    .umra-header h3 {
        margin: 0;
        color: #111827;
        font-size: 1.35rem;
        font-weight: 700;
    }

    .umra-header p {
        margin: 0.35rem 0 0;
        color: #6b7280;
    }

    .umra-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .umra-kpi-card {
        height: 100%;
        padding: 1rem;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .umra-kpi-card .kpi-topline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .umra-kpi-card .kpi-title {
        margin: 0;
        color: #6b7280;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: uppercase;
        line-height: 1.35;
    }

    .umra-kpi-card .kpi-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        border-radius: 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #111827;
        font-size: 1.1rem;
    }

    .umra-kpi-card .kpi-value {
        margin: 0;
        color: #111827;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.15;
        word-break: break-word;
    }

    .umra-kpi-card .kpi-caption {
        margin-top: 0.35rem;
        color: #6b7280;
        font-size: 0.82rem;
    }

    .umra-kpi-card.primary .kpi-icon,
    .umra-kpi-card.teal .kpi-icon,
    .umra-kpi-card.amber .kpi-icon,
    .umra-kpi-card.red .kpi-icon,
    .umra-kpi-card.green .kpi-icon,
    .umra-kpi-card.slate .kpi-icon {
        background: #f3f4f6;
        color: #111827;
    }

    .umra-panel {
        height: 100%;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .umra-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .umra-panel-header h4 {
        margin: 0;
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
    }

    .umra-panel-header span {
        color: #6b7280;
        font-size: 0.82rem;
    }

    .umra-panel-body {
        padding: 1rem 1.25rem 1.25rem;
    }

    .umra-chart-wrap {
        position: relative;
        height: 300px;
    }

    .umra-mini-stat {
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
    }

    .umra-mini-stat strong {
        display: block;
        color: #111827;
        font-size: 1.05rem;
    }

    .umra-mini-stat span {
        color: #6b7280;
        font-size: 0.8rem;
    }

    .umra-table-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .umra-table-card .table {
        margin-bottom: 0;
    }

    .umra-branch-table th {
        background: #eef4f8;
        color: #111827;
        border-color: #dbe5ec;
        font-weight: 700;
        white-space: nowrap;
    }

    .umra-branch-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .umra-branch-table .numeric {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    @media (max-width: 767px) {
        .umra-header {
            align-items: stretch;
            flex-direction: column;
        }

        .umra-actions {
            justify-content: flex-start;
        }

        .umra-chart-wrap {
            height: 260px;
        }
    }
</style>
@endpush

@section('content')
<div class="umra-dashboard">
    <div class="umra-header">
        <div>
            <h3>UMRA Executive Portfolio Indicators</h3>
            <p>Reporting date: <strong>{{ $indicators['reporting_date'] }}</strong></p>
        </div>
        <div class="umra-actions">
            <a href="{{ route('admin.umra.export-excel') }}" class="btn btn-sm btn-outline-success">
                <i class="mdi mdi-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('admin.umra.export-pdf') }}" class="btn btn-sm btn-outline-danger">
                <i class="mdi mdi-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('admin.umra.schedule3.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-download"></i> Schedule 3
            </a>
            <a href="{{ route('admin.umra.prudential-pack') }}" class="btn btn-sm btn-outline-dark">
                <i class="mdi mdi-chart-box"></i> Prudential Pack
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card primary">
                <div class="kpi-topline">
                    <p class="kpi-title">Active Loan Accounts</p>
                    <span class="kpi-icon"><i class="mdi mdi-account-multiple"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['total_active_loan_accounts'] }}</p>
                <div class="kpi-caption">Loans currently in portfolio</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card teal">
                <div class="kpi-topline">
                    <p class="kpi-title">Outstanding Principal</p>
                    <span class="kpi-icon"><i class="mdi mdi-cash-multiple"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['gross_outstanding_principal'] }}</p>
                <div class="kpi-caption">UGX principal balance due</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card slate">
                <div class="kpi-topline">
                    <p class="kpi-title">Interest Outstanding</p>
                    <span class="kpi-icon"><i class="mdi mdi-percent"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['interest_outstanding'] }}</p>
                <div class="kpi-caption">UGX interest balance due</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card red">
                <div class="kpi-topline">
                    <p class="kpi-title">Required Provision</p>
                    <span class="kpi-icon"><i class="mdi mdi-shield-alert"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['required_provision'] }}</p>
                <div class="kpi-caption">UGX per UMRA rates</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card green">
                <div class="kpi-topline">
                    <p class="kpi-title">Provision Coverage</p>
                    <span class="kpi-icon"><i class="mdi mdi-chart-donut"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['provision_coverage'] }}%</p>
                <div class="kpi-caption">Required provision / outstanding</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card amber">
                <div class="kpi-topline">
                    <p class="kpi-title">PAR 30</p>
                    <span class="kpi-icon"><i class="mdi mdi-timer-sand"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['par_30'] }}%</p>
                <div class="kpi-caption">Portfolio 31+ days late</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card red">
                <div class="kpi-topline">
                    <p class="kpi-title">PAR 90</p>
                    <span class="kpi-icon"><i class="mdi mdi-alert-octagon"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['par_90'] }}%</p>
                <div class="kpi-caption">Portfolio 91+ days late</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
            <div class="umra-kpi-card slate">
                <div class="kpi-topline">
                    <p class="kpi-title">Write-off Review</p>
                    <span class="kpi-icon"><i class="mdi mdi-file-search"></i></span>
                </div>
                <p class="kpi-value">{{ $indicators['writeoff_review_accounts'] }}</p>
                <div class="kpi-caption">Non-performing loans over 270 days</div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-xl-5 mb-4">
            <div class="umra-panel">
                <div class="umra-panel-header">
                    <div>
                        <h4>Risk Classification Mix</h4>
                        <span>Accounts by UMRA class</span>
                    </div>
                </div>
                <div class="umra-panel-body">
                    <div class="umra-chart-wrap">
                        <canvas id="umraRiskMixChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7 mb-4">
            <div class="umra-panel">
                <div class="umra-panel-header">
                    <div>
                        <h4>Exposure vs Required Provision</h4>
                        <span>UGX by risk class</span>
                    </div>
                </div>
                <div class="umra-panel-body">
                    <div class="umra-chart-wrap">
                        <canvas id="umraExposureChart"></canvas>
                    </div>
                    <div class="row mt-3">
                        <div class="col-sm-4 mb-2">
                            <div class="umra-mini-stat">
                                <strong>{{ number_format($chartData['portfolio_total'], 2) }}</strong>
                                <span>Total outstanding UGX</span>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-2">
                            <div class="umra-mini-stat">
                                <strong>{{ number_format($chartData['provision_total'], 2) }}</strong>
                                <span>Total provision UGX</span>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-2">
                            <div class="umra-mini-stat">
                                <strong>{{ $indicators['loss_classified_exposure'] }}</strong>
                                <span>Loss exposure UGX</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 mb-4">
            <div class="umra-table-card">
                <div class="umra-panel-header">
                    <div>
                        <h4>Branch Portfolio Summary</h4>
                        <span>Active accounts, PAR 30 exposure and provisioning by branch</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover umra-branch-table">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="numeric">Active Accounts</th>
                                <th class="numeric">Outstanding Principal (UGX)</th>
                                <th class="numeric">PAR 30 Exposure (UGX)</th>
                                <th class="numeric">PAR 30 %</th>
                                <th class="numeric">Provision Required (UGX)</th>
                                <th class="numeric">Loss Exposure (UGX)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branchSummary as $branch)
                                <tr>
                                    <td><strong>{{ $branch['branch'] }}</strong></td>
                                    <td class="numeric">{{ number_format($branch['active_accounts']) }}</td>
                                    <td class="numeric">{{ number_format($branch['outstanding_principal'], 0) }}</td>
                                    <td class="numeric">{{ number_format($branch['par30_exposure'], 0) }}</td>
                                    <td class="numeric">{{ number_format($branch['par30_percent'], 1) }}%</td>
                                    <td class="numeric">{{ number_format($branch['provision_required'], 0) }}</td>
                                    <td class="numeric">{{ number_format($branch['loss_exposure'], 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No active branch portfolio records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($branchSummary))
                            <tfoot>
                                <tr>
                                    <th>TOTAL</th>
                                    <th class="numeric">{{ number_format(array_sum(array_column($branchSummary, 'active_accounts'))) }}</th>
                                    <th class="numeric">{{ number_format(array_sum(array_column($branchSummary, 'outstanding_principal')), 0) }}</th>
                                    <th class="numeric">{{ number_format(array_sum(array_column($branchSummary, 'par30_exposure')), 0) }}</th>
                                    <th class="numeric">
                                        @php
                                            $totalBranchPrincipal = array_sum(array_column($branchSummary, 'outstanding_principal'));
                                            $totalBranchPar30 = array_sum(array_column($branchSummary, 'par30_exposure'));
                                        @endphp
                                        {{ number_format($totalBranchPrincipal > 0 ? ($totalBranchPar30 / $totalBranchPrincipal) * 100 : 0, 1) }}%
                                    </th>
                                    <th class="numeric">{{ number_format(array_sum(array_column($branchSummary, 'provision_required')), 0) }}</th>
                                    <th class="numeric">{{ number_format(array_sum(array_column($branchSummary, 'loss_exposure')), 0) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Regulatory Return Status Table -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Regulatory Return Status</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Return / Report</th>
                                <th>Regulator / Basis</th>
                                <th>Cadence</th>
                                <th>Workbook Sheet</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($regulatoryStatus as $return)
                            <tr>
                                <td><strong>{{ $return['return_name'] ?? 'Untitled return' }}</strong></td>
                                <td><small>{{ $return['regulator'] ?? 'UMRA' }}</small></td>
                                <td>{{ $return['cadence'] ?? 'N/A' }}</td>
                                <td>{{ $return['workbook_sheet'] ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if(($return['status'] ?? null) == 'Generated')
                                        <span class="badge bg-success">Generated</span>
                                    @elseif(($return['status'] ?? null) == 'Management draft')
                                        <span class="badge bg-warning text-dark">Management draft</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $return['status'] ?? 'Pending' }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($return['route']))
                                        <a href="{{ route($return['route']) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="mdi mdi-eye"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">View Detailed Reports</h5>
                    <p class="text-muted">Access detailed UMRA compliance reports and schedules:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.umra.schedule3') }}" class="btn btn-primary">
                            <i class="mdi mdi-file-document"></i> Schedule 3 - Risk Classification
                        </a>
                        <a href="{{ route('admin.umra.collateral-register') }}" class="btn btn-outline-primary">
                            <i class="mdi mdi-shield-check"></i> Collateral Register
                        </a>
                        <a href="{{ route('admin.umra.loan-records') }}" class="btn btn-outline-primary">
                            <i class="mdi mdi-format-list-bulleted"></i> Loan Records
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    const chartData = @json($chartData);
    const riskColors = [
        '#16a34a', // Performing
        '#2563eb', // Watch
        '#f59e0b', // Substandard
        '#f97316', // Doubtful
        '#dc2626'  // Loss
    ];
    const gridColor = 'rgba(148, 163, 184, 0.25)';

    const riskCanvas = document.getElementById('umraRiskMixChart');
    if (riskCanvas) {
        new Chart(riskCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.risk_labels,
                datasets: [{
                    data: chartData.risk_counts,
                    backgroundColor: riskColors,
                    borderColor: '#ffffff',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutoutPercentage: 62,
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 16
                    }
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            const label = data.labels[tooltipItem.index] || '';
                            const value = data.datasets[0].data[tooltipItem.index] || 0;
                            return label + ': ' + value + ' account(s)';
                        }
                    }
                }
            }
        });
    }

    const exposureCanvas = document.getElementById('umraExposureChart');
    if (exposureCanvas) {
        new Chart(exposureCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.risk_labels,
                datasets: [
                    {
                        label: 'Outstanding',
                        data: chartData.risk_outstanding,
                        backgroundColor: '#2563eb',
                        borderColor: '#1d4ed8',
                        borderWidth: 1
                    },
                    {
                        label: 'Required Provision',
                        data: chartData.risk_provisions,
                        backgroundColor: '#dc2626',
                        borderColor: '#b91c1c',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                }
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K';
                                }
                                return value;
                            }
                        },
                        gridLines: {
                            color: gridColor
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            const label = data.datasets[tooltipItem.datasetIndex].label || '';
                            const amount = Number(tooltipItem.yLabel || 0).toLocaleString();
                            return label + ': UGX ' + amount;
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
