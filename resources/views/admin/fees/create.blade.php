@extends('layouts.admin')

@section('title', 'Record Fee Payment')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Record Fee Payment</h4>
                
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.fees.index') }}">Fee Payments</a></li>
                        <li class="breadcrumb-item active">Record Payment</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Fee Payment Details</h4>
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

                    <form action="{{ route('admin.fees.store') }}" method="POST" id="feePaymentForm">
                        @csrf
                        
                        <div class="row">
                            <!-- Member Selection -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="member_id" class="form-label">Member <span class="text-danger">*</span></label>
                                    <select name="member_id" id="member_id" class="form-select" required>
                                        <option value="">Select Member</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->id }}" 
                                                    {{ (request('member_id') == $member->id || old('member_id') == $member->id) ? 'selected' : '' }}>
                                                {{ $member->code }} - {{ $member->fname }} {{ $member->lname }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Fee Type -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="fees_type_id" class="form-label">Fee Type <span class="text-danger">*</span></label>
                                    <select name="fees_type_id" id="fees_type_id" class="form-select" required>
                                        <option value="">Select Fee Type</option>
                                        @foreach($feeTypes as $feeType)
                                            <option value="{{ $feeType->id }}" {{ old('fees_type_id') == $feeType->id ? 'selected' : '' }}>
                                                {{ $feeType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Loan (Optional) -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="loan_id" class="form-label">Related Loan (Optional)</label>
                                    <select name="loan_id" id="loan_id" class="form-select">
                                        <option value="">No Related Loan</option>
                                        @if(isset($loans))
                                            @foreach($loans as $loan)
                                                <option value="{{ $loan->id }}" {{ old('loan_id') == $loan->id ? 'selected' : '' }}>
                                                    Loan #{{ $loan->id }} - UGX {{ number_format($loan->amount) }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <!-- Payment Type -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payment_type" class="form-label">Payment Type <span class="text-danger">*</span></label>
                                    <select name="payment_type" id="payment_type" class="form-select" required>
                                        <option value="">Select Payment Type</option>
                                        <option value="1" {{ old('payment_type') == '1' ? 'selected' : '' }}>Cash</option>
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>Mobile Money</option>
                                        <option value="3" {{ old('payment_type') == '3' ? 'selected' : '' }}>Bank Transfer</option>
                                    </select>
                                    <small class="text-muted">Mobile Money will send USSD prompt to member's phone</small>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="amount" class="form-label">Amount (UGX) <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" class="form-control" 
                                           value="{{ old('amount') }}" required min="0" step="1">
                                    <small class="text-muted" id="amount-hint">Registration fees: 25,000 UGX</small>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="3" 
                                              placeholder="Enter payment description">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.fees.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Record Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Member Info Card -->
            <div class="card" id="memberInfoCard" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Member Information</h5>
                </div>
                <div class="card-body">
                    <div id="memberInfo">
                        <!-- Member details will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Fee Type Info Card -->
            <div class="card" id="feeTypeInfoCard" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Fee Type Information</h5>
                </div>
                <div class="card-body">
                    <div id="feeTypeInfo">
                        <!-- Fee type details will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Recent Fees Card -->
            <div class="card" id="recentFeesCard" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Fees for Member</h5>
                </div>
                <div class="card-body">
                    <div id="recentFees">
                        <!-- Recent fees will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for better dropdown experience
    $('#member_id, #fees_type_id, #loan_id').select2({
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        }
    });

    // Load member loans when member is selected
    $('#member_id').change(function() {
        const memberId = $(this).val();
        
        if (memberId) {
            // Load member info
            loadMemberInfo(memberId);
            
            // Load member loans
            loadMemberLoans(memberId);
            
            // Load recent fees
            loadRecentFees(memberId);
        } else {
            $('#memberInfoCard, #recentFeesCard').hide();
            $('#loan_id').html('<option value="">No Related Loan</option>');
        }
    });

    // Load fee type info when fee type is selected
    $('#fees_type_id').change(function() {
        const feeTypeId = $(this).val();
        const feeTypeName = $(this).find('option:selected').text();
        
        if (feeTypeId) {
            loadFeeTypeInfo(feeTypeId);
            
            // Set default amount for registration fees
            if (feeTypeName === 'Registration fees') {
                $('#amount').val(25000);
                $('#amount-hint').text('Registration fees: 25,000 UGX (default)');
            } else {
                $('#amount').val('');
                $('#amount-hint').text('Enter the fee amount');
            }
        } else {
            $('#feeTypeInfoCard').hide();
            $('#amount').val('');
            $('#amount-hint').text('Enter the fee amount');
        }
    });
    
    // Pre-select member if provided in URL
    @if(request('member_id'))
        loadMemberInfo({{ request('member_id') }});
        loadMemberLoans({{ request('member_id') }});
        loadRecentFees({{ request('member_id') }});
    @endif
});

function loadMemberInfo(memberId) {
    fetch(`{{ url('admin/members') }}/${memberId}/quick-info`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('memberInfo').innerHTML = data.html;
                document.getElementById('memberInfoCard').style.display = 'block';
            }
        })
        .catch(error => console.error('Error loading member info:', error));
}

function loadMemberLoans(memberId) {
    fetch(`{{ url('admin/members') }}/${memberId}/loans`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let options = '<option value="">No Related Loan</option>';
                data.loans.forEach(loan => {
                    options += `<option value="${loan.id}">Loan #${loan.id} - UGX ${loan.amount.toLocaleString()}</option>`;
                });
                document.getElementById('loan_id').innerHTML = options;
            }
        })
        .catch(error => console.error('Error loading member loans:', error));
}

function loadRecentFees(memberId) {
    fetch(`{{ url('admin/members') }}/${memberId}/recent-fees`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('recentFees').innerHTML = data.html;
                document.getElementById('recentFeesCard').style.display = 'block';
            }
        })
        .catch(error => console.error('Error loading recent fees:', error));
}

function loadFeeTypeInfo(feeTypeId) {
    fetch(`{{ url('admin/fee-types') }}/${feeTypeId}/info`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('feeTypeInfo').innerHTML = data.html;
                document.getElementById('feeTypeInfoCard').style.display = 'block';
            }
        })
        .catch(error => console.error('Error loading fee type info:', error));
}
</script>
@endsection