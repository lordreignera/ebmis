@extends('admin.layout')

@section('title', 'Loan Disbursements')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-money-bill-wave"></i>
                        Loan Disbursements
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="test-connection-btn">
                            <i class="fas fa-wifi"></i> Test Mobile Money Connection
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $statistics['personal_loans']['pending'] }}</h3>
                                    <p>Pending Personal Loans</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $statistics['group_loans']['pending'] }}</h3>
                                    <p>Pending Group Loans</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ number_format($statistics['personal_loans']['total_amount'] + $statistics['group_loans']['total_amount'], 0) }}</h3>
                                    <p>Total Disbursed (UGX)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $statistics['personal_loans']['rejected'] + $statistics['group_loans']['rejected'] }}</h3>
                                    <p>Rejected Disbursements</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-times"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Loans Pending Disbursement -->
                    @if(count($pending_disbursements['personal_loans']) > 0)
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Pending Personal Loan Disbursements</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="personal-disbursements-table">
                                    <thead>
                                        <tr>
                                            <th>Loan Code</th>
                                            <th>Member</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Principal</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pending_disbursements['personal_loans'] as $disbursement)
                                        <tr>
                                            <td>{{ $disbursement['personal_loan']['code'] ?? 'N/A' }}</td>
                                            <td>
                                                @if($disbursement['personal_loan']['member'])
                                                    {{ $disbursement['personal_loan']['member']['fname'] }} {{ $disbursement['personal_loan']['member']['lname'] }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $disbursement['personal_loan']['product']['name'] ?? 'N/A' }}</td>
                                            <td>UGX {{ number_format($disbursement['amount'], 2) }}</td>
                                            <td>UGX {{ number_format($disbursement['personal_loan']['principal'], 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($disbursement['datecreated'])->format('d-M-Y H:i') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm disburse-btn" 
                                                        data-id="{{ $disbursement['id'] }}"
                                                        data-loan-id="{{ $disbursement['loan_id'] }}"
                                                        data-amount="{{ $disbursement['amount'] }}"
                                                        data-member="{{ $disbursement['personal_loan']['member']['fname'] ?? '' }} {{ $disbursement['personal_loan']['member']['lname'] ?? '' }}"
                                                        data-phone="{{ $disbursement['personal_loan']['member']['contact'] ?? '' }}">
                                                    <i class="fas fa-money-bill-wave"></i> Disburse
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm reject-btn" 
                                                        data-id="{{ $disbursement['id'] }}"
                                                        data-loan-id="{{ $disbursement['loan_id'] }}">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Group Loans Pending Disbursement -->
                    @if(count($pending_disbursements['group_loans']) > 0)
                    <div class="card card-outline card-warning mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Pending Group Loan Disbursements</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="group-disbursements-table">
                                    <thead>
                                        <tr>
                                            <th>Group Name</th>
                                            <th>Amount</th>
                                            <th>Principal</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pending_disbursements['group_loans'] as $disbursement)
                                        <tr>
                                            <td>{{ $disbursement->group_name }}</td>
                                            <td>UGX {{ number_format($disbursement->amount, 2) }}</td>
                                            <td>UGX {{ number_format($disbursement->principal, 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($disbursement->datecreated)->format('d-M-Y H:i') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm">
                                                    <i class="fas fa-money-bill-wave"></i> Disburse
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(count($pending_disbursements['personal_loans']) == 0 && count($pending_disbursements['group_loans']) == 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No pending disbursements found. All approved loans have been disbursed.
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disbursement Modal -->
<div class="modal fade" id="disbursement-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Process Loan Disbursement</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="disbursement-form">
                @csrf
                <input type="hidden" name="disbursement_id" id="disbursement_id">
                
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Member:</strong> <span id="member-name"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Amount:</strong> UGX <span id="disbursement-amount"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Disbursement Method *</label>
                        <select class="form-control" name="type" id="type" required>
                            <option value="">Select disbursement method</option>
                            <option value="0">Cash</option>
                            <option value="1">Mobile Money</option>
                            <option value="2">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <!-- Mobile Money Fields -->
                    <div id="mobile-money-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_number">Phone Number *</label>
                                    <input type="text" class="form-control" name="account_number" id="account_number" placeholder="256777123456">
                                    <small class="form-text text-muted">Format: 256777123456 or 0777123456</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="medium">Network</label>
                                    <select class="form-control" name="medium" id="medium">
                                        <option value="2">Auto-detect</option>
                                        <option value="2">MTN</option>
                                        <option value="1">Airtel</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="loan_amt">Amount to Disburse *</label>
                            <input type="number" class="form-control" name="loan_amt" id="loan_amt" step="0.01" min="1000">
                        </div>
                    </div>
                    
                    <!-- Bank Transfer Fields -->
                    <div id="bank-fields" style="display: none;">
                        <div class="form-group">
                            <label for="bank_account_number">Bank Account Number *</label>
                            <input type="text" class="form-control" name="account_number" id="bank_account_number" placeholder="Bank account number">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="d_date">Disbursement Date *</label>
                        <input type="date" class="form-control" name="d_date" id="d_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="inv_id">Investment ID (Optional)</label>
                        <input type="number" class="form-control" name="inv_id" id="inv_id" placeholder="Investment reference ID">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-money-bill-wave"></i> Process Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejection-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Reject Disbursement</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rejection-form">
                @csrf
                <input type="hidden" name="disbursement_id" id="rejection_disbursement_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Reason for Rejection *</label>
                        <textarea class="form-control" name="reason" id="rejection_reason" rows="4" required placeholder="Please provide a reason for rejecting this disbursement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Disbursement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#personal-disbursements-table, #group-disbursements-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[5, 'desc']] // Sort by date created
    });
    
    // Handle disbursement type change
    $('#type').on('change', function() {
        const type = $(this).val();
        $('#mobile-money-fields, #bank-fields').hide();
        
        if (type === '1') {
            $('#mobile-money-fields').show();
            $('#account_number, #loan_amt').prop('required', true);
            $('#bank_account_number').prop('required', false);
        } else if (type === '2') {
            $('#bank-fields').show();
            $('#bank_account_number').prop('required', true);
            $('#account_number, #loan_amt').prop('required', false);
        } else {
            $('#account_number, #loan_amt, #bank_account_number').prop('required', false);
        }
    });
    
    // Handle disburse button click
    $('.disburse-btn').on('click', function() {
        const id = $(this).data('id');
        const loanId = $(this).data('loan-id');
        const amount = $(this).data('amount');
        const member = $(this).data('member');
        const phone = $(this).data('phone');
        
        $('#disbursement_id').val(id);
        $('#member-name').text(member);
        $('#disbursement-amount').text(new Intl.NumberFormat().format(amount));
        $('#account_number').val(phone);
        $('#loan_amt').val(amount);
        
        $('#disbursement-modal').modal('show');
    });
    
    // Handle reject button click
    $('.reject-btn').on('click', function() {
        const id = $(this).data('id');
        $('#rejection_disbursement_id').val(id);
        $('#rejection-modal').modal('show');
    });
    
    // Handle disbursement form submission
    $('#disbursement-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.disbursements.process") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Disbursement processed successfully!');
                    $('#disbursement-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Disbursement processing failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while processing the disbursement';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Test mobile money connection
    $('#test-connection-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Testing...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.mobile-money.test") }}',
            type: 'GET',
            success: function(response) {
                if (response.connection) {
                    toastr.success('Mobile money connection is working!');
                } else {
                    toastr.warning('Mobile money connection issue: ' + response.message);
                }
                btn.html(originalText).prop('disabled', false);
            },
            error: function(xhr) {
                toastr.error('Connection test failed');
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush