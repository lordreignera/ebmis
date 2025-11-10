@extends('layouts.admin')

@section('title', 'Create Group Monthly Loan Account')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create Group Monthly Loan Account</h4>
                    <p class="text-muted">Please type carefully and fill out the form with the relevant details. Some aspects won't be editable once you have submitted the form.</p>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.loans.store') }}" method="POST" enctype="multipart/form-data" id="loanForm">
                        @csrf
                        <input type="hidden" name="loan_type" value="group">
                        <input type="hidden" name="repay_period" value="monthly">

                        <div class="row">
                            <!-- Branch Selection -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" name="branch_id" required>
                                        <option value="">Choose...</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ auth()->user()->branch_id == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Loan Code -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Loan Code</label>
                                    <input type="text" class="form-control" name="loan_code" value="GMLOAN{{ time() }}" readonly required>
                                </div>
                            </div>

                            <!-- Select Group -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Group <span class="text-danger">*</span></label>
                                    <select class="form-select" name="group_id" id="group_id" required>
                                        <option value="">Select...</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" 
                                                data-branch="{{ $group->branch_id }}"
                                                data-members="{{ $group->members->count() }}">
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Select Loan Product -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Loan Product <span class="text-danger">*</span></label>
                                    <select class="form-select" name="product_type" id="product_type" required>
                                        <option value="">Select...</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" 
                                                data-interest="{{ $product->interest }}"
                                                data-code="{{ $product->code }}">
                                                {{ $product->name }} ({{ $product->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Interest Rate -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Interest (%age)</label>
                                    <input type="text" class="form-control" name="interest" id="interest" readonly required>
                                </div>
                            </div>

                            <!-- Period -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Period (No. of Months) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="period" id="period" min="1" max="60" required>
                                    <small class="text-muted">Enter number of months (1-60)</small>
                                </div>
                            </div>

                            <!-- Loan Sharing Strategy -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Are All Members Sharing Loan Equally? <span class="text-danger">*</span></label>
                                    <select class="form-select" name="equal_sharing" id="equal_sharing" required>
                                        <option value="no" selected>No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Total Amount (for equal sharing) -->
                            <div class="col-md-4" id="total_amount_div" style="display: none;">
                                <div class="form-group mb-3">
                                    <label class="form-label">Total Loan Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="total_amount" id="total_amount" step="0.01" min="50000">
                                    <small class="text-muted">Total amount to be shared equally among all members (Min: UGX 50,000)</small>
                                </div>
                            </div>

                            <!-- Principal Amount (for individual amounts) -->
                            <div class="col-md-4" id="principal_div">
                                <div class="form-group mb-3">
                                    <label class="form-label">Principal Amount per Member <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="principal" id="principal" step="0.01" min="10000" required>
                                    <small class="text-muted">Amount per member (Min: UGX 10,000)</small>
                                </div>
                            </div>

                            <!-- Max Installment -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Monthly Installment Amount</label>
                                    <input type="text" class="form-control" name="max_installment" id="max_installment" readonly>
                                </div>
                            </div>

                            <!-- Meeting Day -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Group Meeting Day <span class="text-danger">*</span></label>
                                    <select class="form-select" name="meeting_day" required>
                                        <option value="">Select day...</option>
                                        <option value="first_monday">First Monday of Month</option>
                                        <option value="second_monday">Second Monday of Month</option>
                                        <option value="third_monday">Third Monday of Month</option>
                                        <option value="last_monday">Last Monday of Month</option>
                                        <option value="first_tuesday">First Tuesday of Month</option>
                                        <option value="second_tuesday">Second Tuesday of Month</option>
                                        <option value="third_tuesday">Third Tuesday of Month</option>
                                        <option value="last_tuesday">Last Tuesday of Month</option>
                                        <option value="first_wednesday">First Wednesday of Month</option>
                                        <option value="second_wednesday">Second Wednesday of Month</option>
                                        <option value="third_wednesday">Third Wednesday of Month</option>
                                        <option value="last_wednesday">Last Wednesday of Month</option>
                                        <option value="first_thursday">First Thursday of Month</option>
                                        <option value="second_thursday">Second Thursday of Month</option>
                                        <option value="third_thursday">Third Thursday of Month</option>
                                        <option value="last_thursday">Last Thursday of Month</option>
                                        <option value="first_friday">First Friday of Month</option>
                                        <option value="second_friday">Second Friday of Month</option>
                                        <option value="third_friday">Third Friday of Month</option>
                                        <option value="last_friday">Last Friday of Month</option>
                                        <option value="first_saturday">First Saturday of Month</option>
                                        <option value="second_saturday">Second Saturday of Month</option>
                                        <option value="third_saturday">Third Saturday of Month</option>
                                        <option value="last_saturday">Last Saturday of Month</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Meeting Time -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Meeting Time</label>
                                    <input type="time" class="form-control" name="meeting_time">
                                    <small class="text-muted">Preferred time for group meetings</small>
                                </div>
                            </div>

                            <!-- Meeting Location -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Meeting Location</label>
                                    <input type="text" class="form-control" name="meeting_location" placeholder="Group meeting venue">
                                </div>
                            </div>

                            <!-- Group Purpose -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Group Loan Purpose</label>
                                    <select class="form-select" name="loan_purpose">
                                        <option value="">Select purpose...</option>
                                        <option value="agricultural_project">Agricultural Project</option>
                                        <option value="business_expansion">Business Expansion</option>
                                        <option value="group_investment">Group Investment</option>
                                        <option value="community_development">Community Development</option>
                                        <option value="equipment_purchase">Equipment Purchase</option>
                                        <option value="infrastructure">Infrastructure</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">Additional Information</h5>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Guarantor Requirements</label>
                                    <select class="form-select" name="guarantor_required">
                                        <option value="none">No Guarantor Required</option>
                                        <option value="group_guarantee">Group Guarantee Only</option>
                                        <option value="external_guarantee">External Guarantor Required</option>
                                        <option value="both">Both Group and External Guarantee</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Collateral Type</label>
                                    <select class="form-select" name="collateral_type">
                                        <option value="none">No Collateral</option>
                                        <option value="group_savings">Group Savings</option>
                                        <option value="property">Property</option>
                                        <option value="equipment">Equipment</option>
                                        <option value="land">Land Title</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Create Loan Account
                                </button>
                                <a href="{{ route('admin.loans.index') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-fill interest rate when loan product changes
    $('#product_type').change(function() {
        var selectedOption = $(this).find('option:selected');
        var interest = selectedOption.data('interest');
        $('#interest').val(interest || '');
        calculateInstallment();
    });

    // Auto-select branch when group changes
    $('#group_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var branchId = selectedOption.data('branch');
        if (branchId) {
            $('select[name="branch_id"]').val(branchId);
        }
        calculateInstallment();
    });

    // Toggle between equal sharing and individual amounts
    $('#equal_sharing').change(function() {
        var isEqual = $(this).val() === 'yes';
        if (isEqual) {
            $('#total_amount_div').show();
            $('#principal_div').hide();
            $('#total_amount').attr('required', true);
            $('#principal').attr('required', false);
        } else {
            $('#total_amount_div').hide();
            $('#principal_div').show();
            $('#total_amount').attr('required', false);
            $('#principal').attr('required', true);
        }
        calculateInstallment();
    });

    // Calculate installment when values change
    $('#principal, #total_amount, #period').on('input', function() {
        calculateInstallment();
    });

    function calculateInstallment() {
        var period = parseInt($('#period').val()) || 0;
        var interest = parseFloat($('#interest').val()) || 0;
        var isEqualSharing = $('#equal_sharing').val() === 'yes';
        var amount = 0;

        if (isEqualSharing) {
            var totalAmount = parseFloat($('#total_amount').val()) || 0;
            var groupMembers = $('#group_id').find('option:selected').data('members') || 1;
            amount = totalAmount / groupMembers;
        } else {
            amount = parseFloat($('#principal').val()) || 0;
        }

        if (amount > 0 && period > 0 && interest > 0) {
            // Calculate monthly installment with compound interest
            var monthlyRate = (interest / 100) / 12; // Monthly interest rate
            var numPayments = period;
            
            if (monthlyRate > 0) {
                // PMT formula for compound interest
                var installment = amount * (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
                                 (Math.pow(1 + monthlyRate, numPayments) - 1);
            } else {
                // Simple calculation if no interest
                var installment = amount / period;
            }
            
            $('#max_installment').val(installment.toFixed(2));
        }
    }
});
</script>
@endpush