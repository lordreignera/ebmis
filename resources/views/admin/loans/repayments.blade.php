@extends('admin.layout')

@section('title', 'Loan Repayments')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-receipt"></i>
                        Loan Repayments
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#repayment-modal">
                            <i class="fas fa-plus"></i> Record Payment
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ number_format($statistics['total_collected_today'], 0) }}</h3>
                                    <p>Collected Today (UGX)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ number_format($statistics['total_collected_month'], 0) }}</h3>
                                    <p>Collected This Month (UGX)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $statistics['loans_due_today'] }}</h3>
                                    <p>Loans Due Today</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $statistics['overdue_loans'] }}</h3>
                                    <p>Overdue Loans</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Controls -->
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Filters</h5>
                        </div>
                        <div class="card-body">
                            <form id="filter-form">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="loan_type">Loan Type</label>
                                            <select class="form-control" name="loan_type" id="loan_type">
                                                <option value="">All Loan Types</option>
                                                <option value="personal">Personal Loans</option>
                                                <option value="group">Group Loans</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="payment_status">Payment Status</label>
                                            <select class="form-control" name="payment_status" id="payment_status">
                                                <option value="">All Statuses</option>
                                                <option value="current">Current</option>
                                                <option value="overdue">Overdue</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="date_from">From Date</label>
                                            <input type="date" class="form-control" name="date_from" id="date_from">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="date_to">To Date</label>
                                            <input type="date" class="form-control" name="date_to" id="date_to">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <button type="button" class="btn btn-secondary" id="reset-filters">Reset</button>
                            </form>
                        </div>
                    </div>

                    <!-- Active Loans with Payment Due -->
                    <div class="card card-outline card-warning mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Loans Requiring Payment</h5>
                            <div class="card-tools">
                                <span class="badge badge-warning">{{ count($loans_requiring_payment) }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($loans_requiring_payment) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="active-loans-table">
                                    <thead>
                                        <tr>
                                            <th>Loan Code</th>
                                            <th>Member/Group</th>
                                            <th>Principal</th>
                                            <th>Balance</th>
                                            <th>Next Payment</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($loans_requiring_payment as $loan)
                                        <tr>
                                            <td>{{ $loan['code'] }}</td>
                                            <td>{{ $loan['member_name'] }}</td>
                                            <td>UGX {{ number_format($loan['principal'], 2) }}</td>
                                            <td>UGX {{ number_format($loan['balance'], 2) }}</td>
                                            <td>UGX {{ number_format($loan['next_payment_amount'], 2) }}</td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($loan['next_due_date'])->format('d-M-Y') }}
                                                @if(\Carbon\Carbon::parse($loan['next_due_date'])->isPast())
                                                    <span class="badge badge-danger">Overdue</span>
                                                @elseif(\Carbon\Carbon::parse($loan['next_due_date'])->isToday())
                                                    <span class="badge badge-warning">Due Today</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($loan['status'] === 'overdue')
                                                    <span class="badge badge-danger">Overdue</span>
                                                @elseif($loan['status'] === 'current')
                                                    <span class="badge badge-success">Current</span>
                                                @else
                                                    <span class="badge badge-info">{{ ucfirst($loan['status']) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm payment-btn" 
                                                        data-loan-id="{{ $loan['id'] }}"
                                                        data-loan-type="{{ $loan['type'] }}"
                                                        data-member="{{ $loan['member_name'] }}"
                                                        data-balance="{{ $loan['balance'] }}"
                                                        data-next-payment="{{ $loan['next_payment_amount'] }}">
                                                    <i class="fas fa-money-bill"></i> Pay
                                                </button>
                                                <button type="button" class="btn btn-info btn-sm view-schedule-btn" 
                                                        data-loan-id="{{ $loan['id'] }}"
                                                        data-loan-type="{{ $loan['type'] }}">
                                                    <i class="fas fa-calendar"></i> Schedule
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No loans require payment at this time.
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h5 class="card-title">Recent Payments</h5>
                        </div>
                        <div class="card-body">
                            @if(count($recent_payments) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="recent-payments-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Loan Code</th>
                                            <th>Member/Group</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recent_payments as $payment)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($payment['payment_date'])->format('d-M-Y H:i') }}</td>
                                            <td>{{ $payment['loan_code'] }}</td>
                                            <td>{{ $payment['member_name'] }}</td>
                                            <td>UGX {{ number_format($payment['amount'], 2) }}</td>
                                            <td>
                                                @if($payment['method'] === 'cash')
                                                    <span class="badge badge-primary">Cash</span>
                                                @elseif($payment['method'] === 'mobile_money')
                                                    <span class="badge badge-success">Mobile Money</span>
                                                @else
                                                    <span class="badge badge-info">{{ ucfirst($payment['method']) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment['reference'] ?? 'N/A' }}</td>
                                            <td>{{ $payment['processed_by'] ?? 'System' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No recent payments found.
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Recording Modal -->
<div class="modal fade" id="repayment-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Record Loan Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="payment-form">
                @csrf
                <input type="hidden" name="loan_id" id="loan_id">
                <input type="hidden" name="loan_type" id="loan_type_hidden">
                
                <div class="modal-body">
                    <!-- Loan Selection -->
                    <div class="form-group" id="loan-selection">
                        <label for="search_loan">Search Loan *</label>
                        <input type="text" class="form-control" id="search_loan" placeholder="Enter loan code or member name...">
                        <div id="loan-results" class="mt-2" style="display: none;">
                            <!-- Search results will be populated here -->
                        </div>
                    </div>
                    
                    <!-- Selected Loan Info -->
                    <div id="selected-loan-info" style="display: none;">
                        <div class="alert alert-info">
                            <h6>Selected Loan:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Member:</strong> <span id="selected-member"></span><br>
                                    <strong>Principal:</strong> UGX <span id="selected-principal"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Balance:</strong> UGX <span id="selected-balance"></span><br>
                                    <strong>Next Payment:</strong> UGX <span id="selected-next-payment"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_amount">Payment Amount *</label>
                                <input type="number" class="form-control" name="payment_amount" id="payment_amount" 
                                       step="0.01" min="1000" required placeholder="Enter payment amount">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_method">Payment Method *</label>
                                <select class="form-control" name="payment_method" id="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="check">Check</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Money Fields -->
                    <div id="mobile-money-payment-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number">Phone Number *</label>
                                    <input type="text" class="form-control" name="phone_number" id="phone_number" 
                                           placeholder="256777123456">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_id">Transaction ID</label>
                                    <input type="text" class="form-control" name="transaction_id" id="transaction_id" 
                                           placeholder="Mobile money transaction reference">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank Transfer Fields -->
                    <div id="bank-payment-fields" style="display: none;">
                        <div class="form-group">
                            <label for="bank_reference">Bank Reference Number</label>
                            <input type="text" class="form-control" name="bank_reference" id="bank_reference" 
                                   placeholder="Bank transaction reference">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_date">Payment Date *</label>
                                <input type="date" class="form-control" name="payment_date" id="payment_date" 
                                       value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="auto_waive">Auto-waive Charges</label>
                                <select class="form-control" name="auto_waive" id="auto_waive">
                                    <option value="0">No</option>
                                    <option value="1">Yes - waive late fees if applicable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" 
                                  placeholder="Any additional notes about this payment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Schedule Modal -->
<div class="modal fade" id="schedule-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">Payment Schedule</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="schedule-content">
                    <!-- Schedule will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#active-loans-table, #recent-payments-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[5, 'asc']] // Sort by due date for active loans
    });
    
    // Handle payment method change
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        $('#mobile-money-payment-fields, #bank-payment-fields').hide();
        
        if (method === 'mobile_money') {
            $('#mobile-money-payment-fields').show();
            $('#phone_number').prop('required', true);
        } else if (method === 'bank_transfer') {
            $('#bank-payment-fields').show();
        } else {
            $('#phone_number').prop('required', false);
        }
    });
    
    // Loan search functionality
    $('#search_loan').on('input', function() {
        const query = $(this).val();
        if (query.length >= 3) {
            searchLoans(query);
        } else {
            $('#loan-results').hide();
        }
    });
    
    function searchLoans(query) {
        $.ajax({
            url: '{{ route("admin.loan-management.search") }}',
            type: 'GET',
            data: { query: query },
            success: function(response) {
                if (response.loans && response.loans.length > 0) {
                    let html = '<div class="list-group">';
                    response.loans.forEach(loan => {
                        html += `<a href="#" class="list-group-item list-group-item-action loan-select" 
                                   data-loan-id="${loan.id}" 
                                   data-loan-type="${loan.type}"
                                   data-member="${loan.member_name}"
                                   data-principal="${loan.principal}"
                                   data-balance="${loan.balance}"
                                   data-next-payment="${loan.next_payment}">
                                   <strong>${loan.code}</strong> - ${loan.member_name} 
                                   <br><small>Balance: UGX ${new Intl.NumberFormat().format(loan.balance)}</small>
                                 </a>`;
                    });
                    html += '</div>';
                    $('#loan-results').html(html).show();
                } else {
                    $('#loan-results').html('<div class="alert alert-warning">No loans found</div>').show();
                }
            }
        });
    }
    
    // Handle loan selection
    $(document).on('click', '.loan-select', function(e) {
        e.preventDefault();
        
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        const member = $(this).data('member');
        const principal = $(this).data('principal');
        const balance = $(this).data('balance');
        const nextPayment = $(this).data('next-payment');
        
        $('#loan_id').val(loanId);
        $('#loan_type_hidden').val(loanType);
        $('#selected-member').text(member);
        $('#selected-principal').text(new Intl.NumberFormat().format(principal));
        $('#selected-balance').text(new Intl.NumberFormat().format(balance));
        $('#selected-next-payment').text(new Intl.NumberFormat().format(nextPayment));
        $('#payment_amount').val(nextPayment);
        
        $('#loan-selection').hide();
        $('#selected-loan-info').show();
        $('#loan-results').hide();
    });
    
    // Handle payment button click
    $('.payment-btn').on('click', function() {
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        const member = $(this).data('member');
        const balance = $(this).data('balance');
        const nextPayment = $(this).data('next-payment');
        
        $('#loan_id').val(loanId);
        $('#loan_type_hidden').val(loanType);
        $('#selected-member').text(member);
        $('#selected-balance').text(new Intl.NumberFormat().format(balance));
        $('#selected-next-payment').text(new Intl.NumberFormat().format(nextPayment));
        $('#payment_amount').val(nextPayment);
        
        $('#loan-selection').hide();
        $('#selected-loan-info').show();
        $('#repayment-modal').modal('show');
    });
    
    // View schedule button
    $('.view-schedule-btn').on('click', function() {
        const loanId = $(this).data('loan-id');
        const loanType = $(this).data('loan-type');
        
        $('#schedule-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading schedule...</div>');
        $('#schedule-modal').modal('show');
        
        $.ajax({
            url: '{{ route("admin.loan-management.schedule") }}',
            type: 'GET',
            data: { loan_id: loanId, loan_type: loanType },
            success: function(response) {
                $('#schedule-content').html(response.html);
            },
            error: function() {
                $('#schedule-content').html('<div class="alert alert-danger">Error loading schedule</div>');
            }
        });
    });
    
    // Handle payment form submission
    $('#payment-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.repayments.process") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Payment recorded successfully!');
                    $('#repayment-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Payment recording failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while processing the payment';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Reset modal when closed
    $('#repayment-modal').on('hidden.bs.modal', function() {
        $('#payment-form')[0].reset();
        $('#loan-selection').show();
        $('#selected-loan-info').hide();
        $('#loan-results').hide();
        $('#mobile-money-payment-fields, #bank-payment-fields').hide();
    });
    
    // Filter form
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        // Apply filters and reload the page with filter parameters
        const formData = $(this).serialize();
        window.location.href = window.location.pathname + '?' + formData;
    });
    
    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#filter-form')[0].reset();
        window.location.href = window.location.pathname;
    });
});
</script>
@endpush