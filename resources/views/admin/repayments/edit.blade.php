@extends('layouts.admin')

@section('title', 'Edit Repayment')

@push('css')
<style>
    .edit-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.15);
    }
    
    .form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .loan-summary {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .summary-item:last-child {
        margin-bottom: 0;
        border-top: 1px solid #dee2e6;
        padding-top: 0.5rem;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="card edit-header mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1 text-white">Edit Repayment</h1>
                    <p class="text-white-50 mb-0">Modify repayment details - Transaction: {{ $repayment->txn_id ?? 'N/A' }}</p>
                </div>
                <a href="{{ route('admin.repayments.show', $repayment) }}" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Details
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Loan Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card form-card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Loan Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="loan-summary">
                        <div class="summary-item">
                            <span>Loan Code:</span>
                            <strong>{{ $repayment->loan->code ?? 'N/A' }}</strong>
                        </div>
                        <div class="summary-item">
                            <span>Member:</span>
                            <strong>{{ $repayment->loan->member->fname ?? '' }} {{ $repayment->loan->member->lname ?? '' }}</strong>
                        </div>
                        <div class="summary-item">
                            <span>Principal:</span>
                            <span class="text-info">UGX {{ number_format($repayment->loan->principal ?? 0) }}</span>
                        </div>
                        <div class="summary-item">
                            <span>Current Balance:</span>
                            <span class="text-warning">UGX {{ number_format($repayment->loan->outstanding_balance ?? 0) }}</span>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Editing this repayment will adjust the loan balance accordingly.
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Time Limit:</strong> Repayments can only be edited within 24 hours of creation.
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-8 mb-4">
            <div class="card form-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Edit Payment Details
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.repayments.update', $repayment) }}" id="editForm">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" 
                                       value="{{ old('date', $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('Y-m-d') : '') }}" 
                                       required>
                                @error('date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" required>
                                    <option value="">Select method...</option>
                                    @if(auth()->user()->hasRole(['superadmin', 'administrator']))
                                        <option value="1" {{ old('type', $repayment->type) == '1' ? 'selected' : '' }}>Cash</option>
                                        <option value="2" {{ old('type', $repayment->type) == '2' ? 'selected' : '' }}>Mobile Money</option>
                                        <option value="3" {{ old('type', $repayment->type) == '3' ? 'selected' : '' }}>Bank Transfer</option>
                                    @else
                                        <option value="2" selected>Mobile Money</option>
                                    @endif
                                </select>
                                @error('type')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">UGX</span>
                                    <input type="number" step="0.01" class="form-control" name="amount" 
                                           value="{{ old('amount', $repayment->amount) }}" 
                                           required placeholder="Enter amount paid" id="amountInput">
                                </div>
                                <small class="text-muted">Original amount: UGX {{ number_format($repayment->amount) }}</small>
                                @error('amount')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Transaction Reference</label>
                                <input type="text" class="form-control" name="txn_id" 
                                       value="{{ old('txn_id', $repayment->txn_id) }}" 
                                       placeholder="Reference number or transaction ID">
                                @error('txn_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Payment Description</label>
                                <textarea class="form-control" name="details" rows="3" 
                                          placeholder="Additional payment details...">{{ old('details', $repayment->details) }}</textarea>
                                @error('details')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status" value="1" 
                                           {{ old('status', $repayment->status) == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        Confirm payment status
                                    </label>
                                </div>
                                <small class="text-muted">Check to confirm payment, uncheck to keep as pending</small>
                            </div>

                            <div class="col-12">
                                <hr>
                                <div class="bg-light p-3 rounded">
                                    <h6 class="mb-2">Payment Impact Summary</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Current Loan Balance:</span>
                                        <span>UGX <span id="currentBalance">{{ number_format($repayment->loan->outstanding_balance ?? 0) }}</span></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Original Payment:</span>
                                        <span class="text-danger">-UGX {{ number_format($repayment->amount) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>New Payment:</span>
                                        <span class="text-success">-UGX <span id="newPayment">{{ number_format($repayment->amount) }}</span></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Adjusted Balance:</span>
                                        <span id="adjustedBalance">UGX {{ number_format($repayment->loan->outstanding_balance ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ route('admin.repayments.show', $repayment) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Update Payment
                                        </button>
                                    </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amountInput');
    const newPaymentSpan = document.getElementById('newPayment');
    const adjustedBalanceSpan = document.getElementById('adjustedBalance');
    
    const originalAmount = {{ $repayment->amount }};
    const currentBalance = {{ $repayment->loan->outstanding_balance ?? 0 }};
    
    function updateSummary() {
        const newAmount = parseFloat(amountInput.value) || 0;
        const difference = newAmount - originalAmount;
        const adjustedBalance = currentBalance - difference;
        
        newPaymentSpan.textContent = newAmount.toLocaleString();
        adjustedBalanceSpan.textContent = 'UGX ' + adjustedBalance.toLocaleString();
        
        // Color coding based on balance change
        if (adjustedBalance < 0) {
            adjustedBalanceSpan.className = 'text-danger fw-bold';
        } else if (adjustedBalance === 0) {
            adjustedBalanceSpan.className = 'text-success fw-bold';
        } else {
            adjustedBalanceSpan.className = 'text-warning fw-bold';
        }
    }
    
    amountInput.addEventListener('input', updateSummary);
    
    // Form validation
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const newAmount = parseFloat(amountInput.value);
        const totalBalance = currentBalance + originalAmount; // Original balance before this payment
        
        if (newAmount > totalBalance) {
            if (!confirm(`Payment amount (UGX ${newAmount.toLocaleString()}) exceeds the original loan balance (UGX ${totalBalance.toLocaleString()}). Continue?`)) {
                e.preventDefault();
            }
        }
        
        if (Math.abs(newAmount - originalAmount) > originalAmount * 0.5) {
            if (!confirm(`You're changing the payment amount by more than 50%. Are you sure this is correct?`)) {
                e.preventDefault();
            }
        }
    });
});
</script>
@endpush
@endsection