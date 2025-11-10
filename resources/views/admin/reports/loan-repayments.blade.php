@extends('layouts.admin')

@section('title', 'Loan Repayments Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Loan Repayments Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Loan Repayments</li>
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
                    <h5 class="card-title mb-0">Daily Repayments Trend (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="repaymentTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>This Month</span>
                                    <strong>{{ number_format($stats['this_month_amount']) }} UGX</strong>
                                </div>
                                <div class="progress mt-1">
                                    <div class="progress-bar bg-success" style="width: 75%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Collection Rate</span>
                                    <strong>85%</strong>
                                </div>
                                <div class="progress mt-1">
                                    <div class="progress-bar bg-info" style="width: 85%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>On-time Payments</span>
                                    <strong>92%</strong>
                                </div>
                                <div class="progress mt-1">
                                    <div class="progress-bar bg-primary" style="width: 92%"></div>
                                </div>
                            </div>
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
                    <form method="GET" action="{{ route('admin.reports.loan-repayments') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search by reference, member name...">
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
                            <label for="payment_method" class="form-label">Platform</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">All Platforms</option>
                                <option value="Web" {{ request('payment_method') == 'Web' ? 'selected' : '' }}>Web</option>
                                <option value="Mobile" {{ request('payment_method') == 'Mobile' ? 'selected' : '' }}>Mobile</option>
                                <option value="USSD" {{ request('payment_method') == 'USSD' ? 'selected' : '' }}>USSD</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="amount_range" class="form-label">Amount Range</label>
                            <select class="form-select" id="amount_range" name="amount_range">
                                <option value="">All Amounts</option>
                                <option value="0-100000" {{ request('amount_range') == '0-100000' ? 'selected' : '' }}>0 - 100K</option>
                                <option value="100000-500000" {{ request('amount_range') == '100000-500000' ? 'selected' : '' }}>100K - 500K</option>
                                <option value="500000-1000000" {{ request('amount_range') == '500000-1000000' ? 'selected' : '' }}>500K - 1M</option>
                                <option value="1000000+" {{ request('amount_range') == '1000000+' ? 'selected' : '' }}>1M+</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="mdi mdi-magnify"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <x-export-buttons />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Loan Repayments ({{ $payments->total() }} records)</h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-info btn-sm" onclick="printReceipts()">
                            <i class="mdi mdi-printer"></i> Print Receipts
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
                                    <th>Payment Ref</th>
                                    <th>Loan ID</th>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input payment-checkbox" value="{{ $payment->id }}">
                                    </td>
                                    <td>
                                        <strong>{{ $payment->txn_id ?: 'REP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                    </td>
                                    <td>
                                        @if($payment->loan)
                                            <a href="{{ route('admin.loans.show', $payment->loan->id) }}" class="text-decoration-none">
                                                {{ $payment->loan->code }}
                                            </a>
                                        @elseif($payment->loan_id)
                                            <span class="text-muted">Loan #{{ $payment->loan_id }}</span>
                                            <br><small class="text-warning">Loan record missing</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->loan && $payment->loan->member)
                                            <div>
                                                <strong>{{ $payment->loan->member->fname }} {{ $payment->loan->member->lname }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $payment->loan->member->account }}</small>
                                            </div>
                                        @else
                                            <div class="text-warning">
                                                <small>Missing loan or member data</small>
                                                @if($payment->loan_id)
                                                    <br><small class="text-muted">Loan ID: {{ $payment->loan_id }}</small>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($payment->amount) }} UGX</strong>
                                        @if($payment->type == 3)
                                            <br><small class="text-muted">Payment</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ ucfirst($payment->platform ?: 'Unknown') }}
                                        </span>
                                        @if($payment->txn_id)
                                            <br><small class="text-muted">{{ $payment->txn_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->date_created)
                                            {{ \Carbon\Carbon::parse($payment->date_created)->format('d M Y H:i') }}
                                            @if(\Carbon\Carbon::parse($payment->date_created)->isToday())
                                                <br><small class="text-success">Today</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->status == 1)
                                            <span class="badge bg-success">Confirmed</span>
                                        @elseif($payment->status == 0)
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-danger">Status {{ $payment->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.repayments.show', $payment->id) }}" class="btn btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="printReceipt({{ $payment->id }})" title="Print Receipt">
                                                <i class="mdi mdi-printer"></i>
                                            </button>
                                            @if($payment->status == 0)
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="confirmPayment({{ $payment->id }})" title="Confirm">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="rejectPayment({{ $payment->id }})" title="Reject">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No loan repayments found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} 
                                of {{ $payments->total() }} results
                            </small>
                        </div>
                        <div>
                            {{ $payments->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Action Modals -->
<div class="modal fade" id="confirmPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to confirm this payment?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmPaymentBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="reject_reason" class="form-label">Rejection Reason</label>
                    <textarea class="form-control" id="reject_reason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="rejectPaymentBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentPaymentId = null;

// Select All functionality
$('#selectAll').change(function() {
    $('.payment-checkbox').prop('checked', this.checked);
});

$('.payment-checkbox').change(function() {
    if (!this.checked) {
        $('#selectAll').prop('checked', false);
    } else if ($('.payment-checkbox:checked').length === $('.payment-checkbox').length) {
        $('#selectAll').prop('checked', true);
    }
});

// Repayment Trend Chart
const trendCtx = document.getElementById('repaymentTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: ['Day 1', 'Day 5', 'Day 10', 'Day 15', 'Day 20', 'Day 25', 'Day 30'],
        datasets: [{
            label: 'Daily Repayments (UGX)',
            data: [5000000, 8000000, 6000000, 12000000, 9000000, 15000000, 11000000],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
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
                        return 'Amount: UGX ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});

function confirmPayment(paymentId) {
    currentPaymentId = paymentId;
    $('#confirmPaymentModal').modal('show');
}

function rejectPayment(paymentId) {
    currentPaymentId = paymentId;
    $('#rejectPaymentModal').modal('show');
}

$('#confirmPaymentBtn').click(function() {
    if (currentPaymentId) {
        $.post(`/admin/repayments/${currentPaymentId}/confirm`, {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            location.reload();
        });
    }
});

$('#rejectPaymentBtn').click(function() {
    const reason = $('#reject_reason').val();
    if (currentPaymentId && reason) {
        $.post(`/admin/repayments/${currentPaymentId}/reject`, {
            _token: '{{ csrf_token() }}',
            reason: reason
        }).done(function() {
            location.reload();
        });
    }
});

function printReceipt(paymentId) {
    window.open(`/admin/repayments/${paymentId}/receipt`, '_blank');
}

function printReceipts() {
    const selectedPayments = $('.payment-checkbox:checked').map(function() {
        return this.value;
    }).get();

    if (selectedPayments.length === 0) {
        toastr.warning('Please select payments to print receipts');
        return;
    }

    window.open('/admin/repayments/bulk-receipts?ids=' + selectedPayments.join(','), '_blank');
}

function exportToExcel() {
    window.open('{{ route("admin.reports.loan-repayments") }}?{{ request()->getQueryString() }}&export=excel', '_blank');
}

function exportPDF() {
    window.open('{{ route("admin.reports.loan-repayments") }}?{{ request()->getQueryString() }}&export=pdf', '_blank');
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