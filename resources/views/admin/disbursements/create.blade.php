@extends('layouts.admin')

@section('title', 'Create Disbursement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Disbursement</h3>
                </div>
                
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.disbursements.store') }}" method="POST" id="disbursementForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="loan_id">Select Loan <span class="text-danger">*</span></label>
                                    <select name="loan_id" id="loan_id" class="form-control" required>
                                        <option value="">-- Select Loan --</option>
                                        @foreach($loans as $loan)
                                            <option value="{{ $loan->id }}" 
                                                    data-member="{{ $loan->member->fname }} {{ $loan->member->lname }}"
                                                    data-phone="{{ $loan->member->contact }}"
                                                    data-product="{{ $loan->product->name }}"
                                                    data-principal="{{ $loan->principal }}"
                                                    {{ old('loan_id') == $loan->id ? 'selected' : '' }}>
                                                {{ $loan->code }} - {{ $loan->member->fname }} {{ $loan->member->lname }} 
                                                ({{ $loan->product->name }}) - UGX {{ number_format($loan->principal, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="disbursement_date">Disbursement Date <span class="text-danger">*</span></label>
                                    <input type="date" name="disbursement_date" id="disbursement_date" 
                                           class="form-control" value="{{ old('disbursement_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Loan Details Section -->
                        <div id="loanDetailsSection" style="display: none;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <h5><i class="fas fa-info-circle"></i> Loan Details & Charge Breakdown</h5>
                                        <div id="loanDetailsContent">
                                            <!-- Populated via AJAX -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_type">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_type" id="payment_type" class="form-control" required>
                                        <option value="">-- Select Payment Method --</option>
                                        <option value="1" {{ old('payment_type') == '1' ? 'selected' : '' }}>Mobile Money</option>
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>Bank Transfer/Cheque</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6" id="paymentMediumDiv" style="display: none;">
                                <div class="form-group">
                                    <label for="payment_medium">Mobile Money Network</label>
                                    <select name="payment_medium" id="payment_medium" class="form-control">
                                        <option value="">-- Select Network --</option>
                                        <option value="1" {{ old('payment_medium') == '1' ? 'selected' : '' }}>Airtel Money</option>
                                        <option value="2" {{ old('payment_medium') == '2' ? 'selected' : '' }}>MTN Mobile Money</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_number" id="accountLabel">Phone Number / Account Number <span class="text-danger">*</span></label>
                                    <input type="text" name="account_number" id="account_number" 
                                           class="form-control" value="{{ old('account_number') }}" 
                                           placeholder="Enter phone number or account number" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="investment_id">Investment Account <span class="text-danger">*</span></label>
                                    <select name="investment_id" id="investment_id" class="form-control" required>
                                        <option value="">-- Select Investment Account --</option>
                                        @foreach($investments as $investment)
                                            <option value="{{ $investment->id }}" 
                                                    data-balance="{{ $investment->amount }}"
                                                    {{ old('investment_id') == $investment->id ? 'selected' : '' }}>
                                                {{ $investment->name }} - UGX {{ number_format($investment->amount, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assigned_to">Assign To (Optional)</label>
                                    <select name="assigned_to" id="assigned_to" class="form-control">
                                        <option value="">-- Auto Assignment --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">Notes (Optional)</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                                              placeholder="Additional notes or instructions">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-paper-plane"></i> Process Disbursement
                            </button>
                            <a href="{{ route('admin.disbursements.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
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
    // Handle loan selection
    $('#loan_id').on('change', function() {
        const loanId = $(this).val();
        
        if (loanId) {
            // Show loading
            $('#loanDetailsContent').html('<i class="fas fa-spinner fa-spin"></i> Loading loan details...');
            $('#loanDetailsSection').show();
            
            // Fetch loan details via AJAX
            $.get(`{{ url('admin/disbursements/loan-details') }}/${loanId}`)
                .done(function(response) {
                    if (response.success) {
                        const loan = response.loan;
                        let detailsHtml = `
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Member:</strong> ${loan.member_name}<br>
                                    <strong>Phone:</strong> ${loan.member_phone}<br>
                                    <strong>Product:</strong> ${loan.product_name}
                                </div>
                                <div class="col-md-6">
                                    <strong>Principal:</strong> UGX ${parseFloat(loan.principal).toLocaleString('en-US', {minimumFractionDigits: 2})}<br>
                                    <strong>Interest:</strong> ${loan.interest}%<br>
                                    <strong>Period:</strong> ${loan.period} ${loan.period_type}
                                </div>
                            </div>
                        `;
                        
                        if (loan.detailed_breakdown) {
                            detailsHtml += `
                                <hr>
                                <h6><i class="fas fa-calculator"></i> Charge Breakdown:</h6>
                                <pre class="bg-light p-3 rounded" style="white-space: pre-wrap; font-size: 12px;">${loan.detailed_breakdown}</pre>
                            `;
                        }
                        
                        $('#loanDetailsContent').html(detailsHtml);
                        
                        // Auto-populate account number with member phone
                        if (loan.member_phone) {
                            $('#account_number').val(loan.member_phone);
                        }
                    } else {
                        $('#loanDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${response.message}
                            </div>
                        `);
                    }
                })
                .fail(function() {
                    $('#loanDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Error loading loan details. Please try again.
                        </div>
                    `);
                });
        } else {
            $('#loanDetailsSection').hide();
        }
    });
    
    // Handle payment type change
    $('#payment_type').on('change', function() {
        const paymentType = $(this).val();
        
        if (paymentType === '1') { // Mobile Money
            $('#paymentMediumDiv').show();
            $('#payment_medium').prop('required', true);
            $('#accountLabel').text('Phone Number *');
            $('#account_number').attr('placeholder', 'Enter phone number (e.g., 256701234567)');
        } else if (paymentType === '2') { // Bank Transfer
            $('#paymentMediumDiv').hide();
            $('#payment_medium').prop('required', false);
            $('#accountLabel').text('Account Number *');
            $('#account_number').attr('placeholder', 'Enter bank account number');
        } else {
            $('#paymentMediumDiv').hide();
            $('#payment_medium').prop('required', false);
            $('#accountLabel').text('Phone Number / Account Number *');
            $('#account_number').attr('placeholder', 'Enter phone number or account number');
        }
    });
    
    // Trigger change event if value is already selected (for form errors)
    $('#payment_type').trigger('change');
    $('#loan_id').trigger('change');
    
    // Form validation before submit
    $('#disbursementForm').on('submit', function(e) {
        const paymentType = $('#payment_type').val();
        const accountNumber = $('#account_number').val();
        
        if (paymentType === '1' && accountNumber) {
            // Validate phone number format for mobile money
            const phoneRegex = /^256[0-9]{9}$/;
            if (!phoneRegex.test(accountNumber)) {
                e.preventDefault();
                alert('Please enter a valid phone number in format: 256XXXXXXXXX');
                return false;
            }
        }
        
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    });
});
</script>
@endpush
@endsection