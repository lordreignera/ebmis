@extends('layouts.admin')

@section('title', 'Personal Loan Preview Dashboard')

@section('content')
<style>
    .kpi-card {
        border: 0;
        border-radius: 12px;
        overflow: hidden;
        color: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
    }

    .kpi-body {
        position: relative;
        z-index: 1;
        padding: 1.25rem 1.35rem;
    }

    .kpi-title {
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        opacity: 0.95;
    }

    .kpi-value {
        font-size: 2.05rem;
        line-height: 1.05;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .kpi-meta {
        font-size: 0.9rem;
        opacity: 0.95;
    }

    .kpi-collections {
        background: linear-gradient(135deg, #1f4ea1 0%, #0d2d66 100%);
    }

    .kpi-disbursements {
        background: linear-gradient(135deg, #0b6c8b 0%, #0c4f68 100%);
    }

    .kpi-net {
        background: linear-gradient(135deg, #2f9e44 0%, #1f7a33 100%);
    }

    .kpi-pending {
        background: linear-gradient(135deg, #f08c00 0%, #d97706 100%);
    }

    .kpi-exceptions {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    }
</style>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Personal Loan Management - Preview Dashboard</h4>
                <p class="text-muted mb-0">Real figures preview (separate from main dashboard) - {{ $monthLabel }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('admin.loans.personal.preview-dashboard') }}" class="d-flex align-items-center gap-2">
                    <label for="month" class="mb-0 text-muted small">Month</label>
                    <input type="month" id="month" name="month" class="form-control form-control-sm" value="{{ $selectedMonth }}">
                    <label for="officer" class="mb-0 text-muted small">Officer</label>
                    <select id="officer" name="officer" class="form-select form-select-sm">
                        <option value="">All Officers</option>
                        @foreach($officerOptions as $officer)
                            <option value="{{ $officer['id'] }}" {{ (int) ($selectedOfficer ?? 0) === (int) $officer['id'] ? 'selected' : '' }}>
                                {{ $officer['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    <a href="{{ route('admin.loans.personal.preview-dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset to Current Month</a>
                </form>
                <span class="badge bg-success">Preview</span>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-3">
        <div class="col">
            <div class="card kpi-card kpi-collections h-100">
                <div class="card-body kpi-body">
                    <p class="kpi-title mb-2">Collections (Month)</p>
                    <h3 class="kpi-value mb-1">UGX {{ number_format((float) $collectionsTodayAmount, 0) }}</h3>
                    <small class="kpi-meta">{{ number_format((int) $collectionsTodayCount) }} Transactions</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-disbursements h-100">
                <div class="card-body kpi-body">
                    <p class="kpi-title mb-2">Disbursements (Month)</p>
                    <h3 class="kpi-value mb-1">UGX {{ number_format((float) $disbursementsTodayAmount, 0) }}</h3>
                    <small class="kpi-meta">{{ number_format((int) $disbursementsTodayCount) }} Loans</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-net h-100">
                <div class="card-body kpi-body">
                    <p class="kpi-title mb-2">Net Cash Position (Month)</p>
                    <h3 class="kpi-value mb-1">UGX {{ number_format((float) $expectedBalance, 0) }}</h3>
                    <small class="kpi-meta">Total collections - total disbursements</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-pending h-100">
                <div class="card-body kpi-body">
                    <p class="kpi-title mb-2">Pending Pipeline</p>
                    <h3 class="kpi-value mb-1">{{ number_format((int) $pendingApprovals) }}</h3>
                    <small class="kpi-meta">Unapproved + approved awaiting disbursement</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-exceptions h-100">
                <div class="card-body kpi-body">
                    <p class="kpi-title mb-2">Exceptions / Holds</p>
                    <h3 class="kpi-value mb-1">{{ number_format((int) $exceptionsCount) }}</h3>
                    <small class="kpi-meta">Rejected / held records</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Collections Queue</h6>
                    <span class="badge bg-secondary">Live Preview</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Loan No</th>
                                    <th>Client</th>
                                    <th class="text-end">Amount</th>
                                    <th>Channel</th>
                                    <th>Ref</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($collectionsQueue as $repayment)
                                    @php
                                        $loan = optional(optional($repayment->schedule)->personalLoan);
                                        $member = optional($loan->member);
                                    @endphp
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($repayment->date_created)->format('H:i') }}</td>
                                        <td>{{ $loan->code ?? ('LN-' . $repayment->loan_id) }}</td>
                                        <td>{{ trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) ?: 'N/A' }}</td>
                                        <td class="text-end">UGX {{ number_format((float) $repayment->amount, 0) }}</td>
                                        <td>{{ ucfirst($repayment->platform ?? 'n/a') }}</td>
                                        <td>{{ $repayment->transaction_reference ?? ('RP-' . $repayment->id) }}</td>
                                        <td class="text-end">
                                            @if($loan && $loan->id)
                                                <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-outline-primary btn-sm">Review</a>
                                            @else
                                                <button class="btn btn-outline-secondary btn-sm" disabled>Review</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">No repayment records for {{ $monthLabel }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">Teller Session</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Opening Float</span><strong>UGX 0</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total Collections</span><strong>UGX {{ number_format((float) $collectionsTodayAmount, 0) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total Paid Out</span><strong>UGX {{ number_format((float) $totalPaidOut, 0) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Expected Balance</span><strong>UGX {{ number_format((float) $expectedBalance, 0) }}</strong></div>
                    <small class="text-muted d-block mb-2">
                        Collections breakdown ({{ $monthLabel }}):
                        Cash UGX {{ number_format((float) $cashReceived, 0) }} |
                        Bank UGX {{ number_format((float) $bankReceived, 0) }} |
                        Mobile UGX {{ number_format((float) $mobileReceived, 0) }}
                    </small>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Physical Count</span><strong>N/A</strong></div>
                    <div class="d-flex justify-content-between mt-2"><span>Variance</span><strong class="text-muted">N/A</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @forelse($recentActivity as $activity)
                            <li class="mb-2">✓ {{ $activity['text'] }}</li>
                        @empty
                            <li class="text-muted">No activity found for {{ $monthLabel }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Collector Performance (Bar Chart)</h6>
                    <small class="text-muted">Active Loans: {{ number_format((int) $activeLoans) }} | Month: {{ $monthLabel }}</small>
                </div>
                <div class="card-body">
                    @if($officerPerformance->count() > 0)
                        <canvas id="collectorPerformanceChart" height="100"></canvas>
                    @else
                        <p class="text-muted mb-0">No assigned officer performance data available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-12">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">Reminder Summary (Today)</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Active Loans</span>
                        <strong>{{ number_format((int) $activeLoans) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Clients due today</span>
                        <strong class="text-primary">{{ $clientsDueToday->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Badly overdue clients</span>
                        <strong class="text-danger">{{ $badlyOverdueClients->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Officers tracked</span>
                        <strong>{{ $officerPerformance->count() }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Clients to Call Today</h6>
                    <span class="badge bg-primary">{{ $clientsDueToday->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Loan</th>
                                    <th>Officer</th>
                                    <th>Contact</th>
                                    <th class="text-end">Due</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientsDueToday as $client)
                                    <tr>
                                        <td>{{ trim(($client->fname ?? '') . ' ' . ($client->lname ?? '')) ?: 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('admin.loans.repayments.schedules', $client->loan_id) }}">{{ $client->loan_code ?? ('LN-' . $client->loan_id) }}</a>
                                        </td>
                                        <td>{{ $client->officer_name ?? 'Unassigned' }}</td>
                                        <td>{{ $client->contact ?? 'N/A' }}</td>
                                        <td class="text-end">UGX {{ number_format((float) $client->payment, 0) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.loans.repayments.schedules', $client->loan_id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No clients due today.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Badly Overdue Alerts (30+ Days)</h6>
                    <div class="d-flex align-items-center gap-2">
                        <form method="GET" action="{{ route('admin.loans.personal.preview-dashboard') }}" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <select name="officer" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Officers</option>
                                @foreach($officerOptions as $officer)
                                    <option value="{{ $officer['id'] }}" {{ (int) ($selectedOfficer ?? 0) === (int) $officer['id'] ? 'selected' : '' }}>
                                        {{ $officer['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <span class="badge bg-danger">{{ $badlyOverdueClients->count() }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Loan</th>
                                    <th>Officer</th>
                                    <th>Days</th>
                                    <th class="text-end">Due</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($badlyOverdueClients as $client)
                                    <tr>
                                        <td>{{ trim(($client->fname ?? '') . ' ' . ($client->lname ?? '')) ?: 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('admin.loans.repayments.schedules', $client->loan_id) }}">{{ $client->loan_code ?? ('LN-' . $client->loan_id) }}</a>
                                        </td>
                                        <td>{{ $client->officer_name ?? 'Unassigned' }}</td>
                                        <td><span class="badge bg-danger">{{ (int) $client->days_overdue }}d</span></td>
                                        <td class="text-end">UGX {{ number_format((float) $client->payment, 0) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.loans.repayments.schedules', $client->loan_id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No badly overdue clients (30+ days).</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('collectorPerformanceChart');
        if (!canvas) return;

        const labels = @json($officerPerformance->pluck('officer_name'));
        const assignedLoanCounts = @json($officerPerformance->pluck('assigned_loans'));
        const overdueCounts = @json($officerPerformance->pluck('overdue_count'));
        const severeOverdueCounts = @json($officerPerformance->pluck('severe_overdue_count'));

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Assigned Active Loans',
                        data: assignedLoanCounts,
                        backgroundColor: '#0d6efd'
                    },
                    {
                        label: 'Overdue Loans',
                        data: overdueCounts,
                        backgroundColor: '#fd7e14'
                    },
                    {
                        label: '30+ Days Overdue Loans',
                        data: severeOverdueCounts,
                        backgroundColor: '#dc3545'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    });
</script>
@endpush
