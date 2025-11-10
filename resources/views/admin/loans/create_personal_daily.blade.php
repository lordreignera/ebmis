@extends('layouts.admin')

@section('title', 'Create Personal Daily Loan Account')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create Personal Daily Loan Account</h4>
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
                        <input type="hidden" name="loan_type" value="personal">
                        <input type="hidden" name="repay_period" value="daily">

                        <div class="row">
                            <!-- Branch Selection -->
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Loan Code</label>
                                    <input type="text" class="form-control" name="loan_code" value="PDLOAN{{ time() }}" readonly required>
                                </div>
                            </div>

                            <!-- Select Individual -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Individual <span class="text-danger">*</span></label>
                                    <select class="form-select" name="member_id" id="member_id" required>
                                        <option value="">Select verified member...</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->id }}" 
                                                data-branch="{{ $member->branch_id }}"
                                                data-branch-name="{{ $member->branch->name ?? 'Unknown Branch' }}"
                                                {{ $selectedMember && $selectedMember->id == $member->id ? 'selected' : '' }}>
                                                {{ $member->fname }} {{ $member->lname }} 
                                                @if($member->branch) - {{ $member->branch->name }} @endif
                                                ({{ $member->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Only verified and approved members are shown</small>
                                </div>
                            </div>

                            <!-- Select Loan Type -->
                            <div class="col-md-3">
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

                            <!-- Repayment Strategy -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Repayment Strategy <span class="text-danger">*</span></label>
                                    <select class="form-select" name="repay_strategy" required>
                                        <option value="">Select...</option>
                                        <option value="1">Employed</option>
                                        <option value="2">Business Owner</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Business/Employer Name -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Business / Employer Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="business_name" required>
                                </div>
                            </div>

                            <!-- Business/Employer Contact -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Business / Employer Contact Address <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="business_contact" required>
                                </div>
                            </div>

                            <!-- Interest Rate -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Interest (%age)</label>
                                    <input type="text" class="form-control" name="interest" id="interest" readonly required>
                                </div>
                            </div>

                            <!-- Period -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Period (No. of installments) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="period" id="period" required>
                                </div>
                            </div>

                            <!-- Principal Amount -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Total Amount (Principal) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="principal" id="principal" step="0.01" min="1000" required>
                                </div>
                            </div>

                            <!-- Max Installment -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Max. Installment Amount <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="max_installment" id="max_installment" readonly required>
                                </div>
                            </div>
                        </div>

                        <!-- Supporting Documents Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">Upload Supporting Documents</h5>
                            </div>

                            <!-- Business Trading License -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Business Trading License <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="business_license" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max: 5MB)</small>
                                </div>
                            </div>

                            <!-- Bank Statement -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Bank Statement (Past 6 Months) <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="bank_statement" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max: 5MB)</small>
                                </div>
                            </div>

                            <!-- Business Photos -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Photos of Business Location <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="business_photos" accept=".jpg,.jpeg,.png" required>
                                    <small class="text-muted">Accepted formats: JPG, PNG (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit for Approval
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

    // Auto-select branch when member changes
    $('#member_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var branchId = selectedOption.data('branch');
        var branchName = selectedOption.data('branch-name');
        
        if (branchId) {
            $('select[name="branch_id"]').val(branchId);
            
            // Show a subtle confirmation that branch was auto-selected
            if (branchName) {
                var $branchSelect = $('select[name="branch_id"]');
                var originalBorder = $branchSelect.css('border');
                $branchSelect.css('border', '2px solid #28a745');
                setTimeout(function() {
                    $branchSelect.css('border', originalBorder);
                }, 1500);
            }
        }
    });

    // Calculate installment when principal or period changes
    $('#principal, #period').on('input', function() {
        calculateInstallment();
    });

    // Initialize Select2 for enhanced member selection with search
    $('#member_id').select2({
        placeholder: 'Search for verified member...',
        allowClear: true,
        width: '100%',
        templateResult: function(member) {
            if (member.loading) {
                return member.text;
            }
            
            if (!member.id) {
                return member.text;
            }
            
            // Custom formatting for search results
            var memberText = member.text;
            var nameMatch = memberText.match(/^([^-]+)/);
            var branchMatch = memberText.match(/- ([^(]+)/);
            var codeMatch = memberText.match(/\(([^)]+)\)$/);
            
            var name = nameMatch ? nameMatch[1].trim() : memberText;
            var branch = branchMatch ? branchMatch[1].trim() : 'No Branch';
            var code = codeMatch ? codeMatch[1] : '';
            
            var $container = $(
                "<div class='select2-result-member'>" +
                    "<div class='select2-result-member__title'>" + name + " (" + code + ")</div>" +
                    "<div class='select2-result-member__description'>Branch: " + branch + "</div>" +
                "</div>"
            );
            
            return $container;
        },
        templateSelection: function(member) {
            if (!member.id) {
                return member.text;
            }
            
            // Clean formatting for selected display
            var memberText = member.text;
            var nameMatch = memberText.match(/^([^-]+)/);
            var codeMatch = memberText.match(/\(([^)]+)\)$/);
            
            var name = nameMatch ? nameMatch[1].trim() : memberText;
            var code = codeMatch ? codeMatch[1] : '';
            
            return name + (code ? ' (' + code + ')' : '');
        },
        escapeMarkup: function(markup) {
            return markup;
        }
    });

    // Initialize Select2 for product selection
    $('#product_type').select2({
        placeholder: 'Select loan product...',
        allowClear: true,
        width: '100%'
    });

    function calculateInstallment() {
        var principal = parseFloat($('#principal').val()) || 0;
        var period = parseInt($('#period').val()) || 0;
        var interest = parseFloat($('#interest').val()) || 0;

        if (principal > 0 && period > 0 && interest > 0) {
            // Calculate installment with interest
            var monthlyInterest = (interest / 100);
            var totalAmount = principal + (principal * monthlyInterest);
            var installment = totalAmount / period;
            
            $('#max_installment').val(installment.toFixed(2));
        }
    }
});
</script>
@endpush