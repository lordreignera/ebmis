@extends('layouts.admin')

@section('title', 'Record New Repayment')

@push('css')
<link href="{{ asset('css/enhanced-tables.css') }}" rel="stylesheet">
<style>
    .loan-search-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }
    
    .loan-details-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border-left: 4px solid #48bb78;
    }
    
    .repayment-form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .loan-info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .loan-info-item:last-child {
        border-bottom: none;
    }
    
    .loan-info-label {
        font-weight: 600;
        color: #4a5568;
    }
    
    .loan-info-value {
        font-weight: 700;
        color: #2d3748;
    }
    
    .amount-breakdown {
        background: #f7fafc;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    .breakdown-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    
    .breakdown-item:last-child {
        margin-bottom: 0;
        border-top: 1px solid #e2e8f0;
        padding-top: 0.5rem;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Record New Repayment</h1>
            <p class="text-muted mb-0">Record a new loan repayment transaction</p>
        </div>
        <a href="{{ route('admin.repayments.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Repayments
        </a>
    </div>

    <form method="POST" action="{{ route('admin.repayments.store') }}" id="repaymentForm">
        @csrf
        
        <div class="row">
            <!-- Loan Search -->
            <div class="col-12 mb-4">
                <div class="card loan-search-card">
                    <div class="card-body">
                        <h5 class="card-title text-white mb-3">
                            <i class="fas fa-search me-2"></i>Select Loan
                        </h5>
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label text-white">Search Loan</label>
                                <select class="form-control" id="loanSelect" name="loan_id" required>
                                    <option value="">Select a loan...</option>
                                    @foreach($loans as $loan)
                                        <option value="{{ $loan->id }}" 
                                                data-member="{{ $loan->member->fname }} {{ $loan->member->lname }}"
                                                data-code="{{ $loan->code }}"
                                                data-principal="{{ $loan->principal }}"
                                                data-balance="{{ $loan->outstanding_balance }}"
                                                {{ request('loan_id') == $loan->id || old('loan_id') == $loan->id ? 'selected' : '' }}>
                                            {{ $loan->code }} - {{ $loan->member->fname }} {{ $loan->member->lname }} 
                                            (Balance: UGX {{ number_format($loan->outstanding_balance) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('loan_id')
                                    <small class="text-warning d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-light" id="loadLoanBtn">
                                    <i class="fas fa-sync-alt me-1"></i>Load Loan Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loan Details -->
            <div class="col-lg-6 mb-4" id="loanDetailsPanel" style="display: none;">
                <div class="card loan-details-card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-success me-2"></i>
                            Loan Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="loanDetailsContent">
                            <!-- Loan details will be loaded here -->
                        </div>
                        
                        <div class="amount-breakdown" id="scheduleDetails" style="display: none;">
                            <h6 class="mb-3">Next Payment Due</h6>
                            <div id="scheduleContent">
                                <!-- Schedule details will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Repayment Form -->
            <div class="col-lg-6 mb-4">
                <div class="card repayment-form-card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave text-primary me-2"></i>
                            Payment Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" 
                                       value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" required>
                                    <option value="">Select method...</option>
                                    @if(auth()->user()->hasRole(['superadmin', 'administrator']))
                                        <option value="1" {{ old('type') == '1' ? 'selected' : '' }}>Cash</option>
                                        <option value="2" {{ old('type') == '2' ? 'selected' : '' }}>Mobile Money</option>
                                        <option value="3" {{ old('type') == '3' ? 'selected' : '' }}>Bank Transfer</option>
                                    @else
                                        <option value="2" {{ old('type') == '2' ? 'selected' : '' }}>Mobile Money</option>
                                    @endif
                                </select>
                                @error('type')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="amount" 
                                       value="{{ old('amount') }}" required placeholder="Enter amount paid" 
                                       id="amountInput">
                                @error('amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Transaction Reference</label>
                                <input type="text" class="form-control" name="txn_id" 
                                       value="{{ old('txn_id') }}" placeholder="Reference number or transaction ID">
                                @error('txn_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Payment Description</label>
                                <textarea class="form-control" name="details" rows="3" 
                                          placeholder="Additional payment details...">{{ old('details') }}</textarea>
                                @error('details')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status" value="1" 
                                           {{ old('status') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        Confirm payment immediately
                                    </label>
                                </div>
                                <small class="text-muted">Uncheck to save as pending for later verification</small>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="{{ route('admin.repayments.index') }}" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Record Payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loanSelect = document.getElementById('loanSelect');
    const loadLoanBtn = document.getElementById('loadLoanBtn');
    const loanDetailsPanel = document.getElementById('loanDetailsPanel');
    const loanDetailsContent = document.getElementById('loanDetailsContent');
    const scheduleDetails = document.getElementById('scheduleDetails');
    const scheduleContent = document.getElementById('scheduleContent');

    // Load loan details when selection changes or button is clicked
    function loadLoanDetails() {
        const selectedOption = loanSelect.selectedOptions[0];
        
        if (!selectedOption || !selectedOption.value) {
            loanDetailsPanel.style.display = 'none';
            return;
        }

        const loanData = {
            member: selectedOption.dataset.member,
            code: selectedOption.dataset.code,
            principal: selectedOption.dataset.principal,
            balance: selectedOption.dataset.balance
        };

        // Show loan details
        loanDetailsContent.innerHTML = `
            <div class="loan-info-item">
                <span class="loan-info-label">Loan Code:</span>
                <span class="loan-info-value">${loanData.code}</span>
            </div>
            <div class="loan-info-item">
                <span class="loan-info-label">Member:</span>
                <span class="loan-info-value">${loanData.member}</span>
            </div>
            <div class="loan-info-item">
                <span class="loan-info-label">Original Amount:</span>
                <span class="loan-info-value text-info">UGX ${parseFloat(loanData.principal).toLocaleString()}</span>
            </div>
            <div class="loan-info-item">
                <span class="loan-info-label">Outstanding Balance:</span>
                <span class="loan-info-value text-warning">UGX ${parseFloat(loanData.balance).toLocaleString()}</span>
            </div>
        `;

        loanDetailsPanel.style.display = 'block';

        // Load schedule details via AJAX if needed
        fetchLoanSchedule(selectedOption.value);
    }

    function fetchLoanSchedule(loanId) {
        fetch(`/admin/repayments/loan-details/${loanId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.loan) {
                    if (data.loan.next_due_date) {
                        scheduleContent.innerHTML = `
                            <div class="breakdown-item">
                                <span>Due Date:</span>
                                <span class="fw-bold">${new Date(data.loan.next_due_date).toLocaleDateString()}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Principal Due:</span>
                                <span>UGX ${parseFloat(data.loan.next_due_amount - data.loan.interest_due).toLocaleString()}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Interest Due:</span>
                                <span>UGX ${parseFloat(data.loan.interest_due).toLocaleString()}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Total Due:</span>
                                <span class="text-primary">UGX ${parseFloat(data.loan.next_due_amount).toLocaleString()}</span>
                            </div>
                        `;
                        scheduleDetails.style.display = 'block';
                        
                        // Pre-fill amount with due amount
                        document.getElementById('amountInput').placeholder = `Due: UGX ${parseFloat(data.loan.next_due_amount).toLocaleString()}`;
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching loan details:', error);
            });
    }

    // Event listeners
    loanSelect.addEventListener('change', loadLoanDetails);
    loadLoanBtn.addEventListener('click', loadLoanDetails);

    // Auto-load if loan is pre-selected
    if (loanSelect.value) {
        loadLoanDetails();
    }

    // Form validation
    document.getElementById('repaymentForm').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amountInput').value);
        const balance = parseFloat(loanSelect.selectedOptions[0]?.dataset.balance || 0);

        if (amount > balance) {
            if (!confirm(`Payment amount (UGX ${amount.toLocaleString()}) exceeds outstanding balance (UGX ${balance.toLocaleString()}). Continue?`)) {
                e.preventDefault();
            }
        }
    });
});
</script>
@endpush
@endsection