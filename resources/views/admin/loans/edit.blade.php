@extends('layouts.admin')

@section('title', 'Edit ' . ucfirst($loanType) . ' Loan')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Edit {{ ucfirst($loanType) }} Loan - {{ $loan->code }}</h4>
                
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.index') }}?type={{ $loanType }}">{{ ucfirst($loanType) }} Loans</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.show', ['id' => $loan->id]) }}?type={{ $loanType }}">Loan Details</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
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

                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Note:</strong> Only pending loans can be edited. Once approved or disbursed, loans cannot be modified.
                    </div>

                    <form action="{{ route('admin.loans.update', $loan->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="loan_type" value="{{ $loanType }}">

                        <div class="row">
                            <!-- Loan Code (Read-only) -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Loan Code</label>
                                    <input type="text" class="form-control" value="{{ $loan->code }}" readonly style="background-color: #f8f9fa;">
                                </div>
                            </div>

                            <!-- Branch -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select name="branch_id" id="branch_id" class="form-select" required>
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ $loan->branch_id == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Product -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="product_type" class="form-label">Loan Product <span class="text-danger">*</span></label>
                                    <select name="product_type" id="product_type" class="form-select" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ $loan->product_type == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }} ({{ $product->interest }}% - Max: {{ number_format($product->max_amt) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Principal Amount -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="principal" class="form-label">Principal Amount (UGX) <span class="text-danger">*</span></label>
                                    <input type="number" name="principal" id="principal" class="form-control" 
                                           value="{{ old('principal', $loan->principal) }}" step="0.01" min="0" required>
                                </div>
                            </div>

                            <!-- Interest Rate -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="interest" class="form-label">Interest Rate (%) <span class="text-danger">*</span></label>
                                    <input type="number" name="interest" id="interest" class="form-control" 
                                           value="{{ old('interest', $loan->interest) }}" step="0.01" min="0" max="100" 
                                           readonly style="background-color: #f8f9fa;" required>
                                    <small class="form-text text-muted">Interest rate is set by the selected product</small>
                                </div>
                            </div>

                            <!-- Loan Period -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="period" class="form-label">Loan Period (Months) <span class="text-danger">*</span></label>
                                    <input type="number" name="period" id="period" class="form-control" 
                                           value="{{ old('period', $loan->period) }}" min="1" required>
                                </div>
                            </div>
                        </div>

                        @if($loanType === 'personal' && isset($loan->installment))
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="installment" class="form-label">Installment Amount</label>
                                    <input type="number" name="installment" id="installment" class="form-control" 
                                           value="{{ old('installment', $loan->installment) }}" step="0.01">
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Loan Purpose</label>
                                    <textarea name="purpose" id="purpose" class="form-control" rows="3">{{ old('purpose', $loan->purpose ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i> Update Loan
                            </button>
                            <a href="{{ route('admin.loans.show', ['id' => $loan->id]) }}?type={{ $loanType }}" class="btn btn-secondary">
                                <i class="mdi mdi-cancel me-1"></i> Cancel
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
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_type');
    const interestInput = document.getElementById('interest');
    const principalInput = document.getElementById('principal');
    
    // Store product data
    const products = @json($products->keyBy('id'));
    
    // Update interest rate and validate principal when product changes
    productSelect.addEventListener('change', function() {
        const productId = this.value;
        
        if (productId && products[productId]) {
            const product = products[productId];
            
            // Update interest rate (readonly, so user can't edit it)
            interestInput.value = product.interest;
            
            // Set max amount validation
            principalInput.max = product.max_amt;
            
            // Show warning if current principal exceeds max
            const currentPrincipal = parseFloat(principalInput.value);
            if (currentPrincipal > parseFloat(product.max_amt)) {
                alert(`Warning: The principal amount (UGX ${currentPrincipal.toLocaleString()}) exceeds the maximum for this product (UGX ${parseFloat(product.max_amt).toLocaleString()}). Please adjust.`);
                principalInput.value = product.max_amt;
            }
        }
    });
    
    // Validate principal amount on input
    principalInput.addEventListener('input', function() {
        const productId = productSelect.value;
        if (productId && products[productId]) {
            const maxAmount = parseFloat(products[productId].max_amt);
            const currentAmount = parseFloat(this.value);
            
            if (currentAmount > maxAmount) {
                this.setCustomValidity(`Maximum amount for this product is UGX ${maxAmount.toLocaleString()}`);
            } else {
                this.setCustomValidity('');
            }
        }
    });
});
</script>
@endpush

@endsection
