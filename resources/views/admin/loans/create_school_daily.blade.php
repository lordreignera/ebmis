@extends('layouts.admin')

@section('title', 'Create School Daily Loan Account')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create School Daily Loan Account</h4>
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

                    <form action="{{ route('admin.school.loans.store') }}" method="POST" enctype="multipart/form-data" id="loanForm">
                        @csrf
                        <input type="hidden" name="loan_type" value="school">
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
                                    <input type="text" class="form-control" name="loan_code" value="SDLOAN{{ time() }}" readonly required>
                                </div>
                            </div>

                            <!-- Select School/Institution -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select School/Institution <span class="text-danger">*</span></label>
                                    <select class="form-select" name="member_id" id="member_id" required>
                                        <option value="">Search for verified schools...</option>
                                        @foreach($members as $school)
                                            <option value="{{ $school->id }}" 
                                                data-school-name="{{ $school->school_name }}"
                                                data-school-code="{{ $school->school_code }}"
                                                data-school-contact="{{ $school->physical_address }}"
                                                {{ $selectedMember && $selectedMember->id == $school->id ? 'selected' : '' }}>
                                                {{ $school->school_name }} ({{ $school->school_code }})
                                                @if($school->district) - {{ $school->district }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Only approved schools without active loans are shown</small>
                                </div>
                            </div>

                            <!-- Select Loan Product -->
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
                                        <option value="1">School Fees Collection</option>
                                        <option value="2">School Account</option>
                                    </select>
                                </div>
                            </div>

                            <!-- School Name -->
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">School/Institution Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="business_name" required>
                                </div>
                            </div>

                            <!-- School Contact -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">School Contact Address <span class="text-danger">*</span></label>
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

                            <!-- School License -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">School Registration/License <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="business_license" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max: 25MB)</small>
                                </div>
                            </div>

                            <!-- Bank Statement -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Bank Statement (Past 6 Months) <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="bank_statement" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max: 25MB)</small>
                                </div>
                            </div>

                            <!-- School Photos -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">Photos of School Premises <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="business_photos" required>
                                    <small class="text-muted">Accepted formats: JPG, PNG (Max: 25MB)</small>
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

    // Auto-fill school details when school changes
    $('#member_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var schoolName = selectedOption.data('school-name');
        var schoolContact = selectedOption.data('school-contact');
        
        // Auto-fill school name field
        if (schoolName) {
            $('input[name="business_name"]').val(schoolName);
        }
        
        // Auto-fill school contact field
        if (schoolContact) {
            $('input[name="business_contact"]').val(schoolContact);
        }
        
        // Show confirmation
        if (schoolName) {
            var $nameInput = $('input[name="business_name"]');
            var originalBorder = $nameInput.css('border');
            $nameInput.css('border', '2px solid #28a745');
            setTimeout(function() {
                $nameInput.css('border', originalBorder);
            }, 1500);
        }
    });

    // Calculate installment when principal or period changes
    $('#principal, #period').on('input', function() {
        calculateInstallment();
    });

    // Initialize Select2 for enhanced school selection with search
    $('#member_id').select2({
        placeholder: 'Search for verified school...',
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
