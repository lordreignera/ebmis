@extends('layouts.admin')

@section('title', 'Create Group Daily Loan Account')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create Group Daily Loan Account</h4>
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
                        <input type="hidden" name="repay_period" value="daily">

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
                                    <input type="text" class="form-control" name="loan_code" value="GDLOAN{{ time() }}" readonly required>
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
                                    <label class="form-label">Period (No. of installments) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="period" id="period" min="1" max="365" required>
                                    <small class="text-muted">Enter number of days (1-365)</small>
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
                                    <input type="number" class="form-control" name="total_amount" id="total_amount" step="0.01" min="1000">
                                    <small class="text-muted">Total amount to be shared equally among all members</small>
                                </div>
                            </div>

                            <!-- Principal Amount (for individual amounts) -->
                            <div class="col-md-4" id="principal_div">
                                <div class="form-group mb-3">
                                    <label class="form-label">Principal Amount per Member <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="principal" id="principal" step="0.01" min="500" required>
                                    <small class="text-muted">Amount per member</small>
                                </div>
                            </div>

                            <!-- Max Installment -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Daily Installment Amount</label>
                                    <input type="text" class="form-control" name="max_installment" id="max_installment" readonly>
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
    // Initialize Select2 for enhanced group selection with search
    $('#group_id').select2({
        placeholder: 'Search for verified group...',
        allowClear: true,
        width: '100%',
        templateResult: function(group) {
            if (group.loading) {
                return group.text;
            }
            
            // Get member count from data attribute
            var $option = $('#group_id option[value="' + group.id + '"]');
            var memberCount = $option.data('members') || 0;
            
            var $container = $(
                "<div class='select2-result-group clearfix'>" +
                    "<div class='select2-result-group__meta'>" +
                        "<div class='select2-result-group__title'></div>" +
                        "<div class='select2-result-group__description'></div>" +
                    "</div>" +
                "</div>"
            );
            
            $container.find('.select2-result-group__title').text(group.text);
            $container.find('.select2-result-group__description').text('Members: ' + memberCount);
            
            return $container;
        },
        templateSelection: function(group) {
            return group.text || group.placeholder;
        }
    });

    // Initialize Select2 for product selection
    $('#product_type').select2({
        placeholder: 'Select loan product...',
        allowClear: true,
        width: '100%'
    });

    // Auto-fill interest rate when loan product changes
    $('#product_type').change(function() {
        var selectedOption = $(this).find('option:selected');
        var interest = selectedOption.data('interest');
        $('#interest').val(interest || '');
        calculateInstallment();
    });

    // Auto-select branch when group changes (with Select2 compatibility)
    $('#group_id').on('select2:select', function() {
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
        var productType = $('#product_type').val();
        var isEqualSharing = $('#equal_sharing').val() === 'yes';
        var amount = 0;

        if (isEqualSharing) {
            var totalAmount = parseFloat($('#total_amount').val()) || 0;
            var groupMembers = $('#group_id').find('option:selected').data('members') || 1;
            amount = totalAmount / groupMembers;
        } else {
            amount = parseFloat($('#principal').val()) || 0;
        }

        if (amount > 0 && period > 0 && productType) {
            // Use AJAX to calculate installment on server side
            // Interest rate comes from the selected product, not user input
            $.ajax({
                url: '{{ route("admin.loans.calculate") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    principal: amount,
                    product_type: productType,
                    period: period,
                    repay_period: 'daily'
                },
                success: function(response) {
                    if (response.success) {
                        $('#max_installment').val(response.installment);
                        
                        // Show calculation details (optional)
                        console.log('Group Daily Loan Calculation:', {
                            principal: amount,
                            interest: interest + '%',
                            period: period + ' days',
                            installment: response.installment + ' per day',
                            total_payable: response.total_payable,
                            total_interest: response.total_interest
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Calculation error:', error);
                }
            });
        }
    }
});
</script>
@endpush