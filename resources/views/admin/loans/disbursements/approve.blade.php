@extends('layouts.admin')

@section('title', 'Approve Loan Disbursement')

@push('styles')
<style>
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    #network_detected {
        animation: fadeIn 0.5s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    }
    
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
        padding: 10px 24px;
        font-weight: 600;
    }
    
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
</style>
@endpush

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
                            <div class="card border-start border-primary border-3 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="mdi mdi-file-document text-primary me-2" style="font-size: 24px;"></i>
                                        <h6 class="text-muted mb-0">Loan Details</h6>
                                    </div>
                                    <p class="mb-2"><strong>Code:</strong> <span class="text-primary">{{ $loan->loan_code }}</span></p>
                                    <p class="mb-2"><strong>Amount:</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
                                    <p class="mb-0"><strong>Product:</strong> {{ $loan->product_name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-info border-3 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="mdi mdi-account text-info me-2" style="font-size: 24px;"></i>
                                        <h6 class="text-muted mb-0">Borrower Information</h6>
                                    </div>
                                    <p class="mb-2"><strong>Name:</strong> {{ $loan->borrower_name }}</p>
                                    <p class="mb-2"><strong>Phone:</strong> <span class="text-info">{{ $loan->phone_number }}</span></p>
                                    <p class="mb-0"><strong>Branch:</strong> {{ $loan->branch_name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-warning border-3 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="mdi mdi-calendar-clock text-warning me-2" style="font-size: 24px;"></i>
                                        <h6 class="text-muted mb-0">Loan Terms</h6>
                                    </div>
                                    <p class="mb-2"><strong>Interest:</strong> {{ $loan->interest_rate }}%</p>
                                    <p class="mb-2"><strong>Period:</strong> {{ $loan->loan_term }} {{ $loan->period_type }}</p>
                                    <p class="mb-0"><strong>Applied:</strong> {{ $loan->created_at->format('Y-m-d') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-success border-3 shadow-sm h-100 bg-light">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="mdi mdi-cash-multiple text-success me-2" style="font-size: 24px;"></i>
                                        <h6 class="text-muted mb-0">Net Disbursement</h6>
                                    </div>
                                    <p class="mb-2"><strong>Gross Amount:</strong> UGX {{ number_format($loan->principal_amount, 0) }}</p>
                                    @if($loan->processing_fee > 0)
                                    <p class="mb-2"><strong>Processing Fee:</strong> <span class="text-danger">- UGX {{ number_format($loan->processing_fee, 0) }}</span></p>
                                    @endif
                                    <p class="mb-0"><strong class="text-success">Net Amount:</strong> <span class="text-success fs-5">UGX {{ number_format($loan->disbursement_amount, 0) }}</span></p>
                                </div>
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
                                    <label for="disbursement_date" class="form-label">
                                        <i class="mdi mdi-calendar me-1"></i>Disbursement Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="disbursement_date" name="disbursement_date" 
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <!-- Investment Account -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="investment_id" class="form-label">
                                        <i class="mdi mdi-bank me-1"></i>Investment Account <span class="text-danger">*</span>
                                    </label>
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
                                    <label for="payment_type" class="form-label">
                                        <i class="mdi mdi-cash-multiple me-1"></i>Payment Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="payment_type" name="payment_type" required>
                                        <option value="">Select Payment Type</option>
                                        <option value="mobile_money" selected>📱 Mobile Money</option>
                                        <option value="bank_transfer">🏦 Bank Transfer</option>
                                        <option value="cash">💵 Cash</option>
                                        <option value="cheque">📝 Cheque</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Account Number/Phone -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">
                                        <i class="mdi mdi-phone me-1"></i>Account Number/Phone Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="{{ $loan->phone_number }}" required placeholder="256XXXXXXXXX">
                                    <div class="form-text">Phone number for mobile money, account number for bank transfer</div>
                                    <div id="network_detected" class="form-text text-success mt-1" style="display: none;">
                                        <i class="mdi mdi-check-circle me-1"></i>
                                        <strong>Network Detected: <span id="detected_network_name"></span></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Money Network (auto-detected, hidden) -->
                            <div class="col-md-6" id="network_div" style="display: none;">
                                <div class="mb-3">
                                    <label for="network" class="form-label">
                                        <i class="mdi mdi-cellphone-wireless me-1"></i>Mobile Money Network <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="network" name="network" disabled>
                                        <option value="">Detecting network...</option>
                                        <option value="MTN">MTN Money</option>
                                        <option value="AIRTEL">Airtel Money</option>
                                    </select>
                                    <div class="form-text">Automatically detected based on phone number</div>
                                </div>
                            </div>

                            <!-- Staff Assignment -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="assigned_to" class="form-label">
                                        <i class="mdi mdi-account-tie me-1"></i>Assign to Staff Member
                                    </label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Select Staff Member</option>
                                        @foreach($staff_members as $staff)
                                            <option value="{{ $staff->id }}" {{ $loan->assigned_to == $staff->id ? 'selected' : '' }}>
                                                {{ $staff->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Assign this loan to a staff member for follow-up</div>
                                </div>
                            </div>

                            <!-- Comments -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="comments" class="form-label">
                                        <i class="mdi mdi-comment-text me-1"></i>Disbursement Comments
                                    </label>
                                    <textarea class="form-control" id="comments" name="comments" rows="3" 
                                              placeholder="Enter any comments about this disbursement..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Safety Warning for Mobile Money -->
                        <div id="mobile_money_warning" class="alert alert-warning border-start border-warning border-4" style="display: none;">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="mdi mdi-alert-outline" style="font-size: 24px;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="alert-heading mb-2">
                                        <i class="mdi mdi-shield-alert me-1"></i>Mobile Money Disbursement
                                    </h6>
                                    <p class="mb-2">
                                        <strong>⚠️ Important:</strong> This will initiate a <strong>real money transfer</strong> to the borrower's mobile money account.
                                    </p>
                                    <ul class="mb-0 ps-3">
                                        <li>Network is <strong>automatically detected</strong> from phone number</li>
                                        <li>Verify phone number is correct before proceeding</li>
                                        <li>Transaction <strong>cannot be reversed</strong> once submitted</li>
                                        <li>Borrower will receive USSD prompt to confirm receipt</li>
                                    </ul>
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
    // Auto-detect network based on phone number
    function detectNetwork(phone) {
        // Remove non-numeric characters
        var cleanPhone = phone.replace(/[^0-9]/g, '');
        
        // Check if phone has enough digits
        if (cleanPhone.length >= 9) {
            // Check MTN prefixes: 77, 78, 76
            if (cleanPhone.match(/^(256)?(77|78|76)/)) {
                return {detected: true, network: 'MTN', name: 'MTN Money'};
            }
            // Check Airtel prefixes: 70, 75, 74, 71
            else if (cleanPhone.match(/^(256)?(70|75|74|71)/)) {
                return {detected: true, network: 'AIRTEL', name: 'Airtel Money'};
            }
        }
        return {detected: false, network: null, name: null};
    }

    // Update network display
    function updateNetworkDisplay(detection) {
        if (detection.detected) {
            $('#network').val(detection.network);
            $('#detected_network_name').text(detection.name);
            $('#network_detected').show();
            $('#network').prop('disabled', true); // Keep it disabled since auto-detected
        } else {
            $('#network').val('');
            $('#network_detected').hide();
            $('#network').prop('disabled', false); // Allow manual selection if not detected
        }
    }

    // Handle payment type change
    $('#payment_type').change(function() {
        var paymentType = $(this).val();
        
        if (paymentType === 'mobile_money') {
            $('#network_div').show();
            $('#network').prop('required', true);
            $('#account_number').attr('placeholder', '256XXXXXXXXX (e.g., 256782743720)');
            $('#mobile_money_warning').show();
            
            // Auto-fill phone number if available and detect network
            if ('{{ $loan->phone_number }}') {
                $('#account_number').val('{{ $loan->phone_number }}');
                var detection = detectNetwork('{{ $loan->phone_number }}');
                updateNetworkDisplay(detection);
            }
        } else {
            $('#network_div').hide();
            $('#network').prop('required', false);
            $('#mobile_money_warning').hide();
            $('#network_detected').hide();
            
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

    // Auto-detect network on phone number input
    $('#account_number').on('input', function() {
        if ($('#payment_type').val() === 'mobile_money') {
            var phone = $(this).val();
            var detection = detectNetwork(phone);
            updateNetworkDisplay(detection);
        }
    });

    // Form submission handling with simple button state
    var isSubmitting = false;
    
    $('#approveForm').on('submit', function(e) {
        // Prevent double submission
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        if (!this.checkValidity()) {
            e.preventDefault();
            return false;
        }
        
        var paymentType = $('#payment_type').val();
        var accountNumber = $('#account_number').val();
        
        // Validate mobile money network is selected
        if (paymentType === 'mobile_money') {
            var network = $('#network').val();
            if (!network) {
                alert('⚠️ Unable to detect mobile money network.\n\nPlease check the phone number format:\n• Should start with 256\n• MTN: 2567XX, 2568XX, 2567XX\n• Airtel: 2567XX, 2567XX, 2567XX, 2567XX');
                e.preventDefault();
                return false;
            }
            // Re-enable network field before submission so value is sent
            $('#network').prop('disabled', false);
        }
        
        // Confirm action
        var confirmMsg = 'Are you sure you want to disburse UGX {{ number_format($loan->disbursement_amount, 0) }}';
        if (paymentType === 'mobile_money') {
            var networkName = $('#detected_network_name').text() || $('#network').val();
            confirmMsg += ' to ' + accountNumber + ' via ' + networkName;
        }
        confirmMsg += '?\n\nThis action cannot be undone.';
        
        if (!confirm(confirmMsg)) {
            e.preventDefault();
            // Re-disable network if user cancels
            if (paymentType === 'mobile_money') {
                $('#network').prop('disabled', true);
            }
            return false;
        }
        
        // Set flag to prevent double submission
        isSubmitting = true;
        
        // Change button to processing state
        var btn = $('#approveBtn');
        btn.prop('disabled', true);
        btn.html('<i class="mdi mdi-loading mdi-spin me-1"></i> Processing...');
        
        // Allow form to submit normally
        return true;
    });

    // Trigger initial payment type change to show mobile money by default
    $('#payment_type').trigger('change');
});
</script>
@endpush
@endsection