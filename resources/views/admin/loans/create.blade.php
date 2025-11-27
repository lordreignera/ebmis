@extends('layouts.admin')

@section('title', 'Create ' . ucfirst($loanType) . ' Loan (' . ucfirst($repayPeriod) . ')')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Create {{ ucfirst($loanType) }} {{ ucfirst($repayPeriod) }} Loan Account</h4>
                
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.index') }}?type={{ $loanType }}&period={{ $repayPeriod }}">{{ ucfirst($loanType) }} Loans ({{ ucfirst($repayPeriod) }})</a></li>
                        <li class="breadcrumb-item active">Create Loan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Success/Error Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Validation Errors:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="mb-3">
                        <p class="text-muted">Please type carefully and fill out the form with the relevant details. Some aspects won't be editable once you have submitted the form.</p>
                    </div>

                    <form action="{{ route('admin.loans.store') }}" method="POST" enctype="multipart/form-data" id="loanForm">
                        @csrf
                        <input type="hidden" name="loan_type" value="{{ $loanType }}">
                        <input type="hidden" name="repay_period" value="{{ $repayPeriod }}">

                        @if($loanType === 'group')
                            <!-- GROUP LOAN SIMPLIFIED FORM -->
                            <div class="row">
                                <!-- Branch Selection -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="branch_id" class="form-label">Select Branch <span class="text-danger">*</span></label>
                                        <select name="branch_id" id="branch_id" class="form-select" required>
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Auto-generated Loan Code -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="loan_code" class="form-label">Loan Code</label>
                                        <input type="text" id="loan_code" class="form-control" value="Auto-generated" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>

                                <!-- Group Selection -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="group_id" class="form-label">Select Group <span class="text-danger">*</span></label>
                                        <select name="group_id" id="group_id" class="form-select" required>
                                            <option value="">Select Group</option>
                                            @foreach($groups ?? [] as $group)
                                                <option value="{{ $group->id }}" data-branch="{{ $group->branch_id }}">
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('group_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Loan Type/Product -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="product_type" class="form-label">Select Loan Type <span class="text-danger">*</span></label>
                                        <select name="product_type" id="product_type" class="form-select" required>
                                            <option value="">Select Loan Type</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" 
                                                        data-interest="{{ $product->interest }}"
                                                        data-period-type="{{ $product->period_type }}">
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('product_type')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Interest Rate -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="interest" class="form-label">Interest (%age)</label>
                                        <input type="number" name="interest" id="interest" class="form-control" step="0.01" min="0" max="100" readonly style="background-color: #f8f9fa;">
                                        @error('interest')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Period -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="period" class="form-label">Period (No. of installments) <span class="text-danger">*</span></label>
                                        <input type="number" name="period" id="period" class="form-control" min="1" required>
                                        @error('period')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Equal Sharing -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="equal_sharing" class="form-label">Are All Members Sharing Loan Equally? <span class="text-danger">*</span></label>
                                        <select name="equal_sharing" id="equal_sharing" class="form-select" required>
                                            <option value="">Select Option</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        @error('equal_sharing')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        @else
                            <!-- PERSONAL/SCHOOL/STAFF/STUDENT LOAN FULL FORM -->
                            <div class="row">
                                <!-- Branch Selection -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="branch_id" class="form-label">Select Branch <span class="text-danger">*</span></label>
                                        <select name="branch_id" id="branch_id" class="form-select" required>
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Auto-generated Loan Code -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="loan_code" class="form-label">Loan Code</label>
                                        <input type="text" id="loan_code" class="form-control" value="Auto-generated" readonly style="background-color: #f8f9fa;">
                                    </div>
                                </div>

                                <!-- Member Selection -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="member_id" class="form-label">Select Individual <span class="text-danger">*</span></label>
                                        <select name="member_id" id="member_id" class="form-select" required>
                                            <option value="">Select Member</option>
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}" 
                                                        data-branch="{{ $member->branch_id }}"
                                                        {{ $selectedMember && $selectedMember->id == $member->id ? 'selected' : '' }}>
                                                    {{ $member->code }} - {{ $member->fname }} {{ $member->lname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('member_id')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Loan Type/Product -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="product_type" class="form-label">Select Loan Type <span class="text-danger">*</span></label>
                                        <select name="product_type" id="product_type" class="form-select" required>
                                            <option value="">Select Loan Type</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" 
                                                        data-interest="{{ $product->interest }}"
                                                        data-max-amount="{{ $product->max_amt }}"
                                                        data-period-type="{{ $product->period_type }}">
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('product_type')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($loanType !== 'group')
                            <!-- BUSINESS FIELDS (Personal/Staff/Student/School loans only) -->
                            <div class="row">
                                <!-- Repayment Strategy -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="repay_strategy" class="form-label">Select Repayment Strategy <span class="text-danger">*</span></label>
                                        <select name="repay_strategy" id="repay_strategy" class="form-select" required>
                                            <option value="{{ $repayPeriod }}" selected>{{ ucfirst($repayPeriod) }} Repayment</option>
                                        </select>
                                        @error('repay_strategy')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Business/Employer Name -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="business_name" class="form-label">Business / Employer name <span class="text-danger">*</span></label>
                                        <input type="text" name="business_name" id="business_name" class="form-control" required>
                                        @error('business_name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Business/Employer Contact -->
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label for="business_contact" class="form-label">Business / Employer Contact Address <span class="text-danger">*</span></label>
                                        <input type="text" name="business_contact" id="business_contact" class="form-control" required>
                                        @error('business_contact')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Interest Rate -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="interest" class="form-label">Interest (%age)</label>
                                        <input type="number" name="interest" id="interest" class="form-control" step="0.01" min="0" max="100" readonly style="background-color: #f8f9fa;">
                                        @error('interest')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Period (Installments) -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="period" class="form-label">Period (No. of installments) <span class="text-danger">*</span></label>
                                        <input type="number" name="period" id="period" class="form-control" min="1" required>
                                        @error('period')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Total Amount (Principal) -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="principal" class="form-label">Total Amount (Principal) <span class="text-danger">*</span></label>
                                        <input type="number" name="principal" id="principal" class="form-control" min="1" step="0.01" required>
                                        @error('principal')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Maximum Installment Amount -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="max_installment" class="form-label">Max. Installment Amount <span class="text-danger">*</span></label>
                                        <input type="number" name="max_installment" id="max_installment" class="form-control" step="0.01" readonly style="background-color: #f8f9fa;">
                                        @error('max_installment')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Supporting Documents -->
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">UPLOAD SUPPORTING DOCUMENTS INFORMATION</h5>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Business Trading License -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="business_license" class="form-label">Business trading License</label>
                                        <div class="input-group">
                                            <input type="file" name="business_license" id="business_license" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                            <button type="button" class="btn btn-outline-secondary">Browse</button>
                                        </div>
                                        @error('business_license')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Bank Statement -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="bank_statement" class="form-label">Bank statement past six months</label>
                                        <div class="input-group">
                                            <input type="file" name="bank_statement" id="bank_statement" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                            <button type="button" class="btn btn-outline-secondary">Browse</button>
                                        </div>
                                        @error('bank_statement')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Business Photos -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="business_photos" class="form-label">Photos of the business location</label>
                                        <div class="input-group">
                                            <input type="file" name="business_photos" id="business_photos" class="form-control" accept=".jpg,.jpeg,.png" multiple>
                                            <button type="button" class="btn btn-outline-secondary">Browse</button>
                                        </div>
                                        @error('business_photos')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Principal and Max Installment for Group Loans -->
                        @if($loanType === 'group')
                            <div class="row">
                                <!-- Repayment Strategy (Hidden but needed) -->
                                <input type="hidden" name="repay_strategy" value="{{ $repayPeriod }}">
                                
                                <!-- Total Amount (Principal) -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="principal" class="form-label">Total Amount (Principal) <span class="text-danger">*</span></label>
                                        <input type="number" name="principal" id="principal" class="form-control" min="1" step="0.01" required>
                                        @error('principal')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Maximum Installment Amount -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_installment" class="form-label">Max. Installment Amount <span class="text-danger">*</span></label>
                                        <input type="number" name="max_installment" id="max_installment" class="form-control" step="0.01" readonly style="background-color: #f8f9fa;">
                                        @error('max_installment')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg">Submit for Approval</button>
                                <a href="{{ route('admin.loans.index') }}?type={{ $loanType }}&period={{ $repayPeriod }}" class="btn btn-secondary btn-lg ms-2">Cancel</a>
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
document.addEventListener('DOMContentLoaded', function() {
    const memberSelect = document.getElementById('member_id');
    const groupSelect = document.getElementById('group_id');
    const branchSelect = document.getElementById('branch_id');
    const productSelect = document.getElementById('product_type');
    const interestInput = document.getElementById('interest');
    const principalInput = document.getElementById('principal');
    const periodInput = document.getElementById('period');
    const maxInstallmentInput = document.getElementById('max_installment');
    const loanCodeInput = document.getElementById('loan_code');

    // Auto-populate branch when member is selected (personal loans)
    if (memberSelect) {
        memberSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const memberBranchId = selectedOption.getAttribute('data-branch');
            
            if (memberBranchId) {
                branchSelect.value = memberBranchId;
            }
            
            generateLoanCode();
        });
    }

    // Auto-populate branch when group is selected (group loans)
    if (groupSelect) {
        groupSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const groupBranchId = selectedOption.getAttribute('data-branch');
            
            if (groupBranchId) {
                branchSelect.value = groupBranchId;
            }
            
            generateLoanCode();
        });
    }

    // Auto-populate interest rate when product is selected
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const interest = selectedOption.getAttribute('data-interest');
        const maxAmount = selectedOption.getAttribute('data-max-amount');
        
        if (interest) {
            interestInput.value = interest;
        }
        
        calculateMaxInstallment();
        generateLoanCode();
    });

    // Calculate maximum installment when principal or period changes
    principalInput.addEventListener('input', calculateMaxInstallment);
    periodInput.addEventListener('input', calculateMaxInstallment);

    function calculateMaxInstallment() {
        const principal = parseFloat(principalInput.value) || 0;
        const period = parseInt(periodInput.value) || 1;
        const productId = productSelect.value;
        
        if (principal > 0 && period > 0 && productId) {
            // Call backend to calculate using half-term interest formula
            fetch('{{ route("admin.loans.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    principal: principal,
                    period: period,
                    product_type: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    maxInstallmentInput.value = parseFloat(data.installment).toFixed(2);
                    // Update interest field if backend returns it
                    if (data.product_interest) {
                        interestInput.value = data.product_interest;
                    }
                } else {
                    console.error('Calculation error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error calculating installment:', error);
            });
        }
    }

    function generateLoanCode() {
        const member = memberSelect.options[memberSelect.selectedIndex];
        const product = productSelect.options[productSelect.selectedIndex];
        
        if (member && member.value && product && product.value) {
            const memberCode = member.text.split(' - ')[0]; // Extract member code
            const currentDate = new Date();
            const timestamp = currentDate.getFullYear().toString().substr(-2) + 
                            String(currentDate.getMonth() + 1).padStart(2, '0') + 
                            String(currentDate.getDate()).padStart(2, '0') + 
                            String(currentDate.getHours()).padStart(2, '0') + 
                            String(currentDate.getMinutes()).padStart(2, '0');
            
            const loanCode = 'PLOAN' + timestamp;
            loanCodeInput.value = loanCode;
        }
    }

    // Auto-populate based on pre-selected member
    @if($selectedMember)
        memberSelect.dispatchEvent(new Event('change'));
    @endif
});
</script>
@endpush
@endsection