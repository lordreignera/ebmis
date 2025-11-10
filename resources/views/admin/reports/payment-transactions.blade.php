@extends('layouts.admin')

@section('title', 'Payment Transactions Report')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Payment Transactions Report</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Payment Transactions</li>
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
                    <h5 class="card-title mb-0">Transaction Volume Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="transactionTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" height="300"></canvas>
                    <div class="mt-3">
                        @foreach($stats['by_method'] as $method)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ ucwords(str_replace('_', ' ', $method->payment_method)) }}</span>
                            <div>
                                <strong>{{ number_format($method->count) }}</strong>
                                <small class="text-muted">({{ number_format($method->total) }} UGX)</small>
                            </div>
                        </div>
                        @endforeach
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
                    <form method="GET" action="{{ route('admin.reports.payment-transactions') }}" class="row g-3">
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
                            <label for="payment_type" class="form-label">Payment Type</label>
                            <select class="form-select" id="payment_type" name="payment_type">
                                <option value="">All Types</option>
                                <option value="loan_repayment" {{ request('payment_type') == 'loan_repayment' ? 'selected' : '' }}>Loan Repayment</option>
                                <option value="loan_disbursement" {{ request('payment_type') == 'loan_disbursement' ? 'selected' : '' }}>Loan Disbursement</option>
                                <option value="membership_fee" {{ request('payment_type') == 'membership_fee' ? 'selected' : '' }}>Membership Fee</option>
                                <option value="loan_charge" {{ request('payment_type') == 'loan_charge' ? 'selected' : '' }}>Loan Charge</option>
                                <option value="penalty" {{ request('payment_type') == 'penalty' ? 'selected' : '' }}>Penalty</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="mobile_money" {{ request('payment_method') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                <option value="cheque" {{ request('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
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

    <!-- Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Payment Transactions ({{ $payments->total() }} records)</h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                        <button class="btn btn-info btn-sm" onclick="reconcileTransactions()">
                            <i class="mdi mdi-check-circle-outline"></i> Reconcile
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover modern-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Pay Type</th>
                                    <th>Phone</th>
                                    <th>Amount</th>
                                    <th>Trans ID</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Pay Status</th>
                                    <th>Date Initiated</th>
                                    <th>Date Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td>
                                        @if($payment->loan && $payment->loan->member)
                                            <div>
                                                <strong>{{ $payment->loan->member->fname }} {{ $payment->loan->member->lname }}</strong>
                                                <br><small class="text-muted">ID: {{ $payment->loan->member->id }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $payment->type == 1 ? 'Loan Repayment' : 'Other Payment' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($payment->loan && $payment->loan->member && $payment->loan->member->contact)
                                            {{ $payment->loan->member->contact }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            {{ number_format($payment->amount) }} UGX
                                        </strong>
                                    </td>
                                    <td>
                                        @if($payment->txn_id)
                                            <span class="text-primary">{{ $payment->txn_id }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->loan && $payment->loan->branch)
                                            {{ $payment->loan->branch->name ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @elseif($payment->status == 0)
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->pay_status)
                                            <span class="badge bg-info">{{ $payment->pay_status }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->date_created)
                                            <div>
                                                {{ \Carbon\Carbon::parse($payment->date_created)->format('d M Y') }}
                                                <br>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($payment->date_created)->format('H:i:s') }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->datecreated)
                                            <div>
                                                {{ \Carbon\Carbon::parse($payment->datecreated)->format('d M Y') }}
                                                <br>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($payment->datecreated)->format('H:i:s') }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                            <p class="text-muted">No payment transactions found</p>
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

<!-- Transaction Action Modals -->
<div class="modal fade" id="confirmTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to confirm this transaction?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmTransactionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reconcileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reconcile Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reconcile_date" class="form-label">Reconciliation Date</label>
                            <input type="date" class="form-control" id="reconcile_date" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reconcile_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="reconcile_method">
                                <option value="">All Methods</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reconcile_notes" class="form-label">Reconciliation Notes</label>
                    <textarea class="form-control" id="reconcile_notes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="performReconciliation">Reconcile</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentTransactionId = null;

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

// Transaction Trend Chart
const trendCtx = document.getElementById('transactionTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
            label: 'Transaction Volume (UGX)',
            data: [45000000, 52000000, 48000000, 61000000],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Transaction Count',
            data: [320, 380, 340, 420],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            yAxisID: 'y1',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return 'UGX ' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Payment Method Distribution Chart
const methodCtx = document.getElementById('paymentMethodChart').getContext('2d');
new Chart(methodCtx, {
    type: 'doughnut',
    data: {
        labels: [@foreach($stats['by_method'] as $method)'{{ ucwords(str_replace("_", " ", $method->payment_method)) }}',@endforeach],
        datasets: [{
            data: [@foreach($stats['by_method'] as $method){{ $method->count }},@endforeach],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)'
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

function confirmTransaction(transactionId) {
    currentTransactionId = transactionId;
    $('#confirmTransactionModal').modal('show');
}

function cancelTransaction(transactionId) {
    if (confirm('Are you sure you want to cancel this transaction?')) {
        $.post(`/admin/payments/${transactionId}/cancel`, {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            location.reload();
        });
    }
}

$('#confirmTransactionBtn').click(function() {
    if (currentTransactionId) {
        $.post(`/admin/payments/${currentTransactionId}/confirm`, {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            location.reload();
        });
    }
});

function reconcileTransactions() {
    $('#reconcileModal').modal('show');
}

$('#performReconciliation').click(function() {
    const selectedTransactions = $('.payment-checkbox:checked').map(function() {
        return this.value;
    }).get();

    const reconcileData = {
        _token: '{{ csrf_token() }}',
        transaction_ids: selectedTransactions,
        reconcile_date: $('#reconcile_date').val(),
        payment_method: $('#reconcile_method').val(),
        notes: $('#reconcile_notes').val()
    };

    $.post('/admin/payments/reconcile', reconcileData).done(function(response) {
        $('#reconcileModal').modal('hide');
        toastr.success('Transactions reconciled successfully');
        location.reload();
    }).fail(function() {
        toastr.error('Failed to reconcile transactions');
    });
});

function printReceipt(paymentId) {
    window.open(`/admin/payments/${paymentId}/receipt`, '_blank');
}

function exportToExcel() {
    window.open('{{ route("admin.reports.payment-transactions") }}?{{ request()->getQueryString() }}&export=excel', '_blank');
}

function exportPDF() {
    window.open('{{ route("admin.reports.payment-transactions") }}?{{ request()->getQueryString() }}&export=pdf', '_blank');
}
</script>
@endpush

@push('styles')
<style>
/* Modern Table Styles for Reports */
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

.status-individual {
    background-color: #e2e3e5;
    color: #383d41;
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

.table-responsive {
    border-radius: 8px;
}

/* Pagination styles */
.pagination {
    margin-top: 20px;
}

.page-link {
    color: #2c3e50;
    border: 1px solid #dee2e6;
}

.page-link:hover {
    color: #1a252f;
    background-color: #e9ecef;
}

.page-item.active .page-link {
    background-color: #2c3e50;
    border-color: #2c3e50;
}

/* Empty state styles */
.table-empty {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.table-empty i {
    color: #dee2e6;
    margin-bottom: 15px;
}
</style>
@endpush