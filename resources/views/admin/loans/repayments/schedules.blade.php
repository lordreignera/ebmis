@extends('layouts.admin')

@section('title', 'Repayment Schedules - ' . $loan->loan_code)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.active') }}">Active Loans</a></li>
                        <li class="breadcrumb-item active">Repayment Schedules</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    Repayment Schedules - {{ $loan->loan_code }}
                    @if($loan->days_overdue > 0)
                        <span class="badge bg-danger ms-2">{{ $loan->days_overdue }} days overdue</span>
                    @endif
                </h4>
            </div>
        </div>
    </div>

    <!-- Loan Summary -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Loan Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Borrower Information</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $loan->borrower_name }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $loan->phone_number }}</p>
                                <p class="mb-1"><strong>Branch:</strong> {{ $loan->branch_name ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Product:</strong> {{ $loan->product_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Loan Terms</h6>
                                <p class="mb-1"><strong>Principal:</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
                                <p class="mb-1"><strong>Interest Rate:</strong> {{ $loan->interest_rate }}%</p>
                                <p class="mb-1"><strong>Term:</strong> {{ $loan->loan_term }} {{ $loan->period_type }}</p>
                                <p class="mb-0"><strong>Start Date:</strong> {{ $loan->disbursement_date ? date('M d, Y', strtotime($loan->disbursement_date)) : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end pe-3">
                                <h6 class="text-muted">Payment Status</h6>
                                <p class="mb-1"><strong>Total Payable:</strong> UGX {{ number_format($loan->total_payable, 0) }}</p>
                                <p class="mb-1"><strong>Amount Paid:</strong> <span class="text-success">UGX {{ number_format($loan->amount_paid, 0) }}</span></p>
                                <p class="mb-1"><strong>Outstanding:</strong> <span class="text-primary">UGX {{ number_format($loan->outstanding_balance, 0) }}</span></p>
                                <p class="mb-0"><strong>Progress:</strong> 
                                    <span class="badge bg-{{ $loan->payment_percentage >= 75 ? 'success' : ($loan->payment_percentage >= 50 ? 'warning' : 'danger') }}">
                                        {{ number_format($loan->payment_percentage, 1) }}%
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div>
                                <h6 class="text-muted">Next Payment</h6>
                                @if($nextDue)
                                    <p class="mb-1"><strong>Due Date:</strong> 
                                        <span class="{{ $nextDue->is_overdue ? 'text-danger' : 'text-success' }}">
                                            {{ date('M d, Y', strtotime($nextDue->due_date)) }}
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Amount:</strong> UGX {{ number_format($nextDue->due_amount, 0) }}</p>
                                    @if($nextDue->is_overdue)
                                        <p class="mb-0"><strong>Penalty:</strong> <span class="text-danger">UGX {{ number_format($nextDue->penalty_amount, 0) }}</span></p>
                                    @endif
                                @else
                                    <p class="mb-0 text-muted">No pending payments</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Alert -->
    @if($overdueCount > 0)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="mdi mdi-alert-circle-outline fs-4"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="alert-heading mb-1">Payment Overdue!</h6>
                        <p class="mb-0">
                            This loan has <strong>{{ $overdueCount }} overdue payment(s)</strong> 
                            totaling <strong>UGX {{ number_format($overdueAmount, 0) }}</strong>.
                            @if($loan->days_overdue > 7)
                                Consider immediate collection or restructuring.
                            @endif
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        @if($nextDue)
                            <button type="button" class="btn btn-light btn-sm" 
                                    onclick="openRepayModal({{ $nextDue->id }}, '{{ $nextDue->due_date }}', {{ $nextDue->due_amount + $nextDue->penalty_amount }})">
                                <i class="mdi mdi-cash-fast me-1"></i> Pay Now
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($nextDue)
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success w-100" 
                                        onclick="openRepayModal({{ $nextDue->id }}, '{{ $nextDue->due_date }}', {{ $nextDue->due_amount + $nextDue->penalty_amount }})">
                                    <i class="mdi mdi-cash-fast me-1"></i>
                                    Record Payment
                                    <br><small>UGX {{ number_format($nextDue->due_amount + $nextDue->penalty_amount, 0) }}</small>
                                </button>
                            </div>
                        @endif
                        
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" onclick="$('#partialPaymentModal').modal('show')">
                                <i class="mdi mdi-cash-multiple me-1"></i>
                                Partial Payment
                                <br><small>Any amount</small>
                            </button>
                        </div>
                        
                        @if($loan->days_overdue > 7)
                            <div class="col-md-3">
                                <a href="{{ route('admin.loans.restructure', $loan->id) }}" class="btn btn-warning w-100">
                                    <i class="mdi mdi-account-convert me-1"></i>
                                    Restructure Loan
                                    <br><small>Modify terms</small>
                                </a>
                            </div>
                        @endif
                        
                        <div class="col-md-3">
                            <div class="dropdown w-100">
                                <button class="btn btn-light dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-printer me-1"></i>
                                    Print Reports
                                    <br><small>Statements & notices</small>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.loans.statements.print', $loan->id) }}" target="_blank">
                                        <i class="mdi mdi-file-document-outline me-2"></i>Payment Statement
                                    </a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.loans.schedules.print', $loan->id) }}" target="_blank">
                                        <i class="mdi mdi-calendar-text me-2"></i>Repayment Schedule
                                    </a></li>
                                    @if($overdueCount > 0)
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.loans.notices.print', $loan->id) }}" target="_blank">
                                            <i class="mdi mdi-alert-circle-outline me-2"></i>Overdue Notice
                                        </a></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Repayment Schedules -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Repayment Schedules ({{ count($schedules) }} installments)</h5>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="scheduleFilter" id="all" value="all" checked>
                                <label class="btn btn-outline-primary btn-sm" for="all">All</label>

                                <input type="radio" class="btn-check" name="scheduleFilter" id="pending" value="pending">
                                <label class="btn btn-outline-warning btn-sm" for="pending">Pending</label>

                                <input type="radio" class="btn-check" name="scheduleFilter" id="overdue" value="overdue">
                                <label class="btn btn-outline-danger btn-sm" for="overdue">Overdue</label>

                                <input type="radio" class="btn-check" name="scheduleFilter" id="paid" value="paid">
                                <label class="btn btn-outline-success btn-sm" for="paid">Paid</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="schedulesTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 12%;">Due Date</th>
                                    <th style="width: 10%;">Principal</th>
                                    <th style="width: 10%;">Interest</th>
                                    <th style="width: 10%;">Total Due</th>
                                    <th style="width: 10%;">Penalty</th>
                                    <th style="width: 10%;">Amount Paid</th>
                                    <th style="width: 10%;">Balance</th>
                                    <th style="width: 8%;">Status</th>
                                    <th style="width: 8%;">Days</th>
                                    <th style="width: 7%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $index => $schedule)
                                    @php
                                        $totalDue = $schedule->due_amount + $schedule->penalty_amount;
                                        $balance = $totalDue - $schedule->amount_paid;
                                        $daysOverdue = $schedule->days_overdue ?? 0;
                                        
                                        if ($schedule->payment_status === 'paid') {
                                            $statusClass = 'table-success';
                                            $statusFilter = 'paid';
                                        } elseif ($daysOverdue > 0) {
                                            $statusClass = 'table-danger';
                                            $statusFilter = 'overdue';
                                        } else {
                                            $statusClass = $daysOverdue > -7 ? 'table-warning' : '';
                                            $statusFilter = 'pending';
                                        }
                                    @endphp
                                    
                                    <tr class="{{ $statusClass }} schedule-row" data-filter="{{ $statusFilter }}">
                                        <td class="text-center">{{ $schedule->installment_number }}</td>
                                        <td class="text-center">
                                            <strong>{{ date('M d, Y', strtotime($schedule->due_date)) }}</strong>
                                            <br><small class="text-muted">{{ date('l', strtotime($schedule->due_date)) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($schedule->principal_amount, 0) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($schedule->interest_amount, 0) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-primary">{{ number_format($schedule->due_amount, 0) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            @if($schedule->penalty_amount > 0)
                                                <strong class="text-danger">{{ number_format($schedule->penalty_amount, 0) }}</strong>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($schedule->amount_paid > 0)
                                                <strong class="text-success">{{ number_format($schedule->amount_paid, 0) }}</strong>
                                                @if($schedule->payment_date)
                                                    <br><small class="text-muted">{{ date('M d', strtotime($schedule->payment_date)) }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($balance > 0)
                                                <strong class="{{ $daysOverdue > 0 ? 'text-danger' : 'text-warning' }}">
                                                    {{ number_format($balance, 0) }}
                                                </strong>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($schedule->payment_status === 'paid')
                                                <span class="badge bg-success">Paid</span>
                                            @elseif($daysOverdue > 0)
                                                <span class="badge bg-danger">Overdue</span>
                                            @elseif($daysOverdue > -7)
                                                <span class="badge bg-warning">Due Soon</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($schedule->payment_status === 'paid')
                                                <small class="text-muted">Completed</small>
                                            @elseif($daysOverdue > 0)
                                                <span class="text-danger"><strong>+{{ $daysOverdue }}</strong></span>
                                            @else
                                                <small class="text-muted">{{ abs($daysOverdue) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($balance > 0)
                                                <button type="button" class="btn btn-success btn-sm" 
                                                        onclick="openRepayModal({{ $schedule->id }}, '{{ $schedule->due_date }}', {{ $balance }})"
                                                        title="Record Payment">
                                                    <i class="mdi mdi-cash"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">
                                                    <i class="mdi mdi-check-circle"></i>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">TOTALS:</th>
                                    <th class="text-end">{{ number_format($schedules->sum('principal_amount'), 0) }}</th>
                                    <th class="text-end">{{ number_format($schedules->sum('interest_amount'), 0) }}</th>
                                    <th class="text-end">{{ number_format($schedules->sum('due_amount'), 0) }}</th>
                                    <th class="text-end">{{ number_format($schedules->sum('penalty_amount'), 0) }}</th>
                                    <th class="text-end text-success">{{ number_format($schedules->sum('amount_paid'), 0) }}</th>
                                    <th class="text-end text-primary">{{ number_format($schedules->sum(function($s) { return ($s->due_amount + $s->penalty_amount) - $s->amount_paid; }), 0) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Repayment Modal -->
<div class="modal fade" id="repaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Loan Repayment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="repaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="schedule_id" name="schedule_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Loan Code</label>
                            <input type="text" class="form-control" value="{{ $loan->loan_code }}" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="text" class="form-control" id="due_date_display" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="payment_amount" name="amount" 
                                   step="0.01" min="1" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="network_div" style="display: none;">
                            <label class="form-label">Mobile Network <span class="text-danger">*</span></label>
                            <select class="form-select" id="network" name="network">
                                <option value="">Select Network</option>
                                <option value="MTN">MTN Money</option>
                                <option value="AIRTEL">Airtel Money</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" 
                                   value="{{ $loan->phone_number }}" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number"
                                   placeholder="Transaction reference">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="payment_notes" name="notes" rows="2"
                                      placeholder="Additional payment notes..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Mobile Money Processing -->
                    <div id="mobile_money_section" style="display: none;">
                        <hr>
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="mdi mdi-information me-2"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <strong>Mobile Money Collection</strong>
                                    <p class="mb-0">This will initiate a mobile money collection request to the borrower.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_collect" name="auto_collect">
                            <label class="form-check-label" for="auto_collect">
                                Automatically initiate mobile money collection
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-cash-check me-1"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Partial Payment Modal -->
<div class="modal fade" id="partialPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Partial Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="partialPaymentForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <small>Partial payments will be applied to the oldest outstanding installment first.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="1" required>
                        <div class="form-text">Outstanding Balance: UGX {{ number_format($loan->outstanding_balance, 0) }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Partial Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Schedule filtering
    $('input[name="scheduleFilter"]').change(function() {
        var filter = $(this).val();
        
        $('.schedule-row').hide();
        
        if (filter === 'all') {
            $('.schedule-row').show();
        } else {
            $('.schedule-row[data-filter="' + filter + '"]').show();
        }
    });
    
    // Payment method change handler
    $('#payment_method').change(function() {
        if ($(this).val() === 'mobile_money') {
            $('#network_div, #mobile_money_section').show();
            $('#network').prop('required', true);
        } else {
            $('#network_div, #mobile_money_section').hide();
            $('#network').prop('required', false);
        }
    });
    
    // Auto-detect network from phone number
    $('#phone_number').on('input', function() {
        if ($('#payment_method').val() === 'mobile_money') {
            var phone = $(this).val().replace(/[^0-9]/g, '');
            
            if (phone.length >= 9) {
                if (phone.match(/^256(77|78|76)/)) {
                    $('#network').val('MTN');
                } else if (phone.match(/^256(70|75|74|71)/)) {
                    $('#network').val('AIRTEL');
                }
            }
        }
    });
});

function openRepayModal(scheduleId, dueDate, amount) {
    $('#schedule_id').val(scheduleId);
    $('#due_date_display').val(dueDate);
    $('#payment_amount').val(amount);
    $('#repaymentModal').modal('show');
}

// Handle repayment form submission
$('#repaymentForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('loan_id', {{ $loan->id }});
    formData.append('_token', '{{ csrf_token() }}');
    
    $.ajax({
        url: '{{ route("admin.loans.repayments.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#repaymentModal').modal('hide');
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

// Handle partial payment form submission
$('#partialPaymentForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('loan_id', {{ $loan->id }});
    formData.append('_token', '{{ csrf_token() }}');
    
    $.ajax({
        url: '{{ route("admin.loans.repayments.partial") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#partialPaymentModal').modal('hide');
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
</script>
@endpush
@endsection