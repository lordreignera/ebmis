@extends('layouts.admin')

@section('title', 'Active Loans')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Active Loans</li>
                    </ol>
                </div>
                <h4 class="page-title">Active Loans Management</h4>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Active Loans">Active Loans</h5>
                            <h3 class="my-2 py-1">{{ $stats['total_active'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">Total Count</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="active-loans-chart" data-colors="#0066cc"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Outstanding Amount">Outstanding</h5>
                            <h3 class="my-2 py-1">{{ number_format($stats['outstanding_amount'] ?? 0, 0) }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">UGX</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="outstanding-chart" data-colors="#ff6b35"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Overdue Loans">Overdue</h5>
                            <h3 class="my-2 py-1 text-danger">{{ $stats['overdue_count'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">Loans</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="overdue-chart" data-colors="#dc3545"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Collections Today">Today's Collections</h5>
                            <h3 class="my-2 py-1 text-success">{{ number_format($stats['collections_today'] ?? 0, 0) }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">UGX</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="collections-chart" data-colors="#28a745"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter Active Loans</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.loans.active') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Loan code, borrower name, phone...">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="branch" class="form-label">Branch</label>
                            <select class="form-select" id="branch" name="branch">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="product" class="form-label">Product</label>
                            <select class="form-select" id="product" name="product">
                                <option value="">All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="current" {{ request('status') == 'current' ? 'selected' : '' }}>Current</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="restructured" {{ request('status') == 'restructured' ? 'selected' : '' }}>Restructured</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-magnify me-1"></i> Filter
                                </button>
                                <a href="{{ route('admin.loans.active') }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Loans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Active Loans ({{ $loans->total() }} total)</h5>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <a class="btn btn-light dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-export me-1"></i> Export
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('admin.loans.active.export', ['format' => 'excel'] + request()->all()) }}">
                                        <i class="mdi mdi-file-excel me-1"></i> Excel
                                    </a>
                                    <a class="dropdown-item" href="{{ route('admin.loans.active.export', ['format' => 'pdf'] + request()->all()) }}">
                                        <i class="mdi mdi-file-pdf me-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($loans->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 12%;">Loan Code</th>
                                        <th style="width: 15%;">Borrower</th>
                                        <th style="width: 8%;">Phone</th>
                                        <th style="width: 10%;">Principal</th>
                                        <th style="width: 10%;">Outstanding</th>
                                        <th style="width: 8%;">Next Due</th>
                                        <th style="width: 8%;">Due Amount</th>
                                        <th style="width: 7%;">Days Late</th>
                                        <th style="width: 7%;">Status</th>
                                        <th style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($loans as $index => $loan)
                                        @php
                                            $daysLate = $loan->days_overdue ?? 0;
                                            $statusClass = $daysLate > 0 ? 'table-danger' : ($daysLate > -7 ? 'table-warning' : '');
                                        @endphp
                                        <tr class="{{ $statusClass }}">
                                            <td>{{ $loans->firstItem() + $index }}</td>
                                            <td>
                                                <strong>{{ $loan->loan_code }}</strong>
                                                <br><small class="text-muted">{{ $loan->product_name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $loan->borrower_name }}</strong>
                                                <br><small class="text-muted">{{ $loan->branch_name ?? 'No Branch' }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $loan->phone_number }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong>{{ number_format($loan->principal_amount, 0) }}</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-primary">{{ number_format($loan->outstanding_balance, 0) }}</strong>
                                            </td>
                                            <td class="text-center">
                                                @if($loan->next_due_date)
                                                    <small>{{ date('M d, Y', strtotime($loan->next_due_date)) }}</small>
                                                @else
                                                    <small class="text-muted">N/A</small>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($loan->next_due_amount)
                                                    <strong class="{{ $daysLate > 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($loan->next_due_amount, 0) }}
                                                    </strong>
                                                @else
                                                    <small class="text-muted">N/A</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($daysLate > 0)
                                                    <span class="badge bg-danger">{{ $daysLate }} days</span>
                                                @elseif($daysLate > -7)
                                                    <span class="badge bg-warning">Due soon</span>
                                                @else
                                                    <span class="badge bg-success">Current</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($loan->is_restructured)
                                                    <span class="badge bg-info">Restructured</span>
                                                @elseif($daysLate > 30)
                                                    <span class="badge bg-danger">Critical</span>
                                                @elseif($daysLate > 0)
                                                    <span class="badge bg-warning">Overdue</span>
                                                @else
                                                    <span class="badge bg-success">Current</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" 
                                                       class="btn btn-primary btn-sm" title="View Schedules">
                                                        <i class="mdi mdi-calendar-clock"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="quickRepay({{ $loan->id }}, '{{ $loan->loan_code }}', {{ $loan->next_due_amount ?? 0 }}, '{{ $loan->phone_number }}')"
                                                            title="Quick Repayment" {{ !$loan->next_due_amount ? 'disabled' : '' }}>
                                                        <i class="mdi mdi-cash-fast"></i>
                                                    </button>
                                                    <div class="dropdown">
                                                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" 
                                                                data-bs-toggle="dropdown">
                                                            <i class="mdi mdi-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="{{ route('admin.loans.show', $loan->id) }}">
                                                                <i class="mdi mdi-eye me-2"></i>View Details
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="{{ route('admin.loans.history', $loan->id) }}">
                                                                <i class="mdi mdi-history me-2"></i>Payment History
                                                            </a></li>
                                                            @if($daysLate > 7)
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-warning" href="{{ route('admin.loans.restructure', $loan->id) }}">
                                                                    <i class="mdi mdi-account-convert me-2"></i>Restructure
                                                                </a></li>
                                                            @endif
                                                        </ul>
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
                                <small class="text-muted">
                                    Showing {{ $loans->firstItem() }} to {{ $loans->lastItem() }} of {{ $loans->total() }} results
                                </small>
                            </div>
                            <div>
                                {{ $loans->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="mdi mdi-bank-outline display-4 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Active Loans Found</h5>
                            <p class="text-muted mb-0">No loans match your current filter criteria.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Repayment Modal -->
<div class="modal fade" id="quickRepayModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Repayment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickRepayForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Loan</label>
                        <input type="text" class="form-control" id="modal_loan_code" readonly>
                        <input type="hidden" id="modal_loan_id">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" id="modal_amount" step="0.01" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" id="modal_payment_method" required>
                            <option value="">Select Method</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="modal_network_div" style="display: none;">
                        <label class="form-label">Network</label>
                        <select class="form-select" id="modal_network">
                            <option value="">Select Network</option>
                            <option value="MTN">MTN Money</option>
                            <option value="AIRTEL">Airtel Money</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="modal_phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="modal_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Process Repayment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-refresh data every 5 minutes
    setInterval(function() {
        if (!$('.modal').hasClass('show')) {
            window.location.reload();
        }
    }, 300000);
});

function quickRepay(loanId, loanCode, dueAmount, phone) {
    $('#modal_loan_id').val(loanId);
    $('#modal_loan_code').val(loanCode);
    $('#modal_amount').val(dueAmount);
    $('#modal_phone').val(phone);
    $('#quickRepayModal').modal('show');
}

// Handle payment method change
$('#modal_payment_method').change(function() {
    if ($(this).val() === 'mobile_money') {
        $('#modal_network_div').show();
        $('#modal_network').prop('required', true);
    } else {
        $('#modal_network_div').hide();
        $('#modal_network').prop('required', false);
    }
});

// Handle quick repayment form submission
$('#quickRepayForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = {
        loan_id: $('#modal_loan_id').val(),
        amount: $('#modal_amount').val(),
        payment_method: $('#modal_payment_method').val(),
        network: $('#modal_network').val(),
        phone: $('#modal_phone').val(),
        notes: $('#modal_notes').val(),
        _token: '{{ csrf_token() }}'
    };
    
    $.ajax({
        url: '{{ route("admin.loans.repayments.quick") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#quickRepayModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            var message = xhr.responseJSON?.message || 'An error occurred';
            Swal.fire('Error!', message, 'error');
        }
    });
});

// Auto-detect network from phone number
$('#modal_phone').on('input', function() {
    if ($('#modal_payment_method').val() === 'mobile_money') {
        var phone = $(this).val().replace(/[^0-9]/g, '');
        
        if (phone.length >= 9) {
            if (phone.match(/^256(77|78|76)/)) {
                $('#modal_network').val('MTN');
            } else if (phone.match(/^256(70|75|74|71)/)) {
                $('#modal_network').val('AIRTEL');
            }
        }
    }
});
</script>
@endpush
@endsection