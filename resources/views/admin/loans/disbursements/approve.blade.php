@extends('layouts.admin')

@section('title', 'Approve Loan Disbursement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.disbursements.pending') }}">Disbursements</a></li>
                        <li class="breadcrumb-item active">Approve Disbursement</li>
                    </ol>
                </div>
                <h4 class="page-title">Approve Loan Disbursement</h4>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Complete the disbursement of Loan #{{ $loan->loan_code }}</h5>
                </div>
                <div class="card-body">
                    <!-- Loan Details Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted">Loan Details</h6>
                                <p><strong>Code:</strong> {{ $loan->loan_code }}</p>
                                <p><strong>Amount:</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
                                <p><strong>Product:</strong> {{ $loan->product_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted">Borrower Information</h6>
                                <p><strong>Name:</strong> {{ $loan->borrower_name }}</p>
                                <p><strong>Phone:</strong> {{ $loan->phone_number }}</p>
                                <p><strong>Branch:</strong> {{ $loan->branch_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted">Loan Terms</h6>
                                <p><strong>Interest:</strong> {{ $loan->interest_rate }}%</p>
                                <p><strong>Period:</strong> {{ $loan->loan_term }} {{ $loan->period_type }}</p>
                                <p><strong>Applied:</strong> {{ $loan->created_at->format('Y-m-d') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted">Net Disbursement</h6>
                                <p><strong>Gross Amount:</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
                                @if($loan->processing_fee > 0)
                                <p><strong>Processing Fee:</strong> UGX {{ number_format($loan->processing_fee, 0) }}</p>
                                @endif
                                <p><strong class="text-primary">Net Amount:</strong> UGX {{ number_format($loan->disbursement_amount, 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Disbursement Form -->
                    <form action="{{ route('admin.loans.disbursements.approve', $loan->id) }}" method="POST" class="form-validate" id="approveForm">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                        <input type="hidden" name="disbursement_amount" value="{{ $loan->disbursement_amount }}">

                        <div class="row g-3">
                            <!-- Disbursement Date -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="disbursement_date" class="form-label">Disbursement Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="disbursement_date" name="disbursement_date" 
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <!-- Investment Account -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="investment_id" class="form-label">Investment Account <span class="text-danger">*</span></label>
                                    <select class="form-select" id="investment_id" name="investment_id" required>
                                        <option value="">Select Investment Account</option>
                                        @foreach($investment_accounts as $investment)
                                            <option value="{{ $investment->id }}">{{ $investment->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select the account from which funds will be disbursed</div>
                                </div>
                            </div>

                            <!-- Payment Type -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="payment_type" class="form-label">Payment Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="payment_type" name="payment_type" required>
                                        <option value="">Select Payment Type</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cash">Cash</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Mobile Money Network (hidden by default) -->
                            <div class="col-md-4" id="network_div" style="display: none;">
                                <div class="mb-3">
                                    <label for="network" class="form-label">Mobile Money Network <span class="text-danger">*</span></label>
                                    <select class="form-select" id="network" name="network">
                                        <option value="">Select Network</option>
                                        <option value="MTN">MTN Money</option>
                                        <option value="AIRTEL">Airtel Money</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Account Number/Phone -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Account Number/Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="{{ $loan->phone_number }}" required>
                                    <div class="form-text">Phone number for mobile money, account number for bank transfer</div>
                                </div>
                            </div>

                            <!-- Staff Assignment -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">Assign to Staff Member</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Select Staff Member</option>
                                        @foreach($staff_members as $staff)
                                            <option value="{{ $staff->id }}" {{ $loan->assigned_to == $staff->id ? 'selected' : '' }}>
                                                {{ $staff->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Comments -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="comments" class="form-label">Disbursement Comments</label>
                                    <textarea class="form-control" id="comments" name="comments" rows="3" 
                                              placeholder="Enter any comments about this disbursement..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Safety Warning for Mobile Money -->
                        <div id="mobile_money_warning" class="alert alert-warning" style="display: none;">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="mdi mdi-alert-outline me-2"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6 class="alert-heading">Mobile Money Disbursement</h6>
                                    <p class="mb-0">This will initiate a real money transfer to the borrower's mobile money account. 
                                    Please verify the phone number and network before proceeding.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="text-end">
                                    <a href="{{ route('admin.loans.disbursements.pending') }}" class="btn btn-secondary me-2">
                                        <i class="mdi mdi-arrow-left me-1"></i> Back to List
                                    </a>
                                    <button type="submit" class="btn btn-success" id="approveBtn">
                                        <i class="mdi mdi-check me-1"></i> Approve Disbursement
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Handle payment type change
    $('#payment_type').change(function() {
        var paymentType = $(this).val();
        
        if (paymentType === 'mobile_money') {
            $('#network_div').show();
            $('#network').prop('required', true);
            $('#account_number').attr('placeholder', 'Enter phone number (e.g., 256701234567)');
            $('#mobile_money_warning').show();
            
            // Auto-fill phone number if available
            if ('{{ $loan->phone_number }}') {
                $('#account_number').val('{{ $loan->phone_number }}');
            }
        } else {
            $('#network_div').hide();
            $('#network').prop('required', false);
            $('#mobile_money_warning').hide();
            
            if (paymentType === 'bank_transfer') {
                $('#account_number').attr('placeholder', 'Enter bank account number');
                $('#account_number').val('');
            } else if (paymentType === 'cash') {
                $('#account_number').attr('placeholder', 'Cash disbursement - no account needed');
                $('#account_number').val('CASH_DISBURSEMENT');
            } else if (paymentType === 'cheque') {
                $('#account_number').attr('placeholder', 'Enter cheque number or reference');
                $('#account_number').val('');
            } else {
                $('#account_number').attr('placeholder', 'Enter account details');
                $('#account_number').val('');
            }
        }
    });

    // Form submission handling
    $('#approveForm').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            return;
        }
        
        var paymentType = $('#payment_type').val();
        var amount = '{{ number_format($loan->disbursement_amount, 0) }}';
        var accountNumber = $('#account_number').val();
        
        var confirmMessage = `Approve disbursement of UGX ${amount} via ${paymentType}`;
        if (paymentType === 'mobile_money') {
            var network = $('#network').val();
            confirmMessage += ` (${network}) to ${accountNumber}`;
        }
        confirmMessage += '?\n\nThis action cannot be undone.';
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return;
        }
        
        // Disable button and show loading
        var btn = $('#approveBtn');
        btn.prop('disabled', true);
        btn.html('<i class="mdi mdi-loading mdi-spin me-1"></i> Processing...');
    });

    // Auto-detect network based on phone number
    $('#account_number').on('input', function() {
        if ($('#payment_type').val() === 'mobile_money') {
            var phone = $(this).val().replace(/[^0-9]/g, '');
            
            if (phone.length >= 9) {
                // Check MTN prefixes: 77, 78, 76
                if (phone.match(/^256(77|78|76)/)) {
                    $('#network').val('MTN');
                }
                // Check Airtel prefixes: 70, 75, 74, 71
                else if (phone.match(/^256(70|75|74|71)/)) {
                    $('#network').val('AIRTEL');
                }
            }
        }
    });
});
</script>
@endpush
@endsection