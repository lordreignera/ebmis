@extends('admin.layout')

@section('title', 'Restructure Loan - ' . $loan->code)

@push('styles')
<style>
    .info-box {
        background: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        margin-bottom: 20px;
    }
    .comparison-table th {
        background: #e9ecef;
        font-weight: 600;
    }
    .old-value {
        color: #dc3545;
        text-decoration: line-through;
    }
    .new-value {
        color: #198754;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.active') }}">Active Loans</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}">Loan {{ $loan->code }}</a></li>
                        <li class="breadcrumb-item active">Restructure</li>
                    </ol>
                </div>
                <h4 class="page-title">Restructure Loan - {{ $loan->code }}</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Restructure Form -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0 text-white">
                        <i class="mdi mdi-account-convert me-2"></i>Loan Restructuring
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <h6><i class="mdi mdi-information-outline me-2"></i>What is Loan Restructuring?</h6>
                        <p class="mb-0">Loan restructuring allows you to modify the terms of an existing loan for borrowers experiencing financial difficulties. This creates a new loan with adjusted terms while maintaining a link to the original loan.</p>
                    </div>

                    <form action="{{ route('admin.loans.restructure.store', $loan->id) }}" method="POST" id="restructureForm">
                        @csrf

                        <!-- Reason for Restructuring -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Restructuring <span class="text-danger">*</span></label>
                            <select class="form-select @error('reason') is-invalid @enderror" id="reason" name="reason" required>
                                <option value="">Select reason...</option>
                                <option value="financial_hardship">Financial Hardship</option>
                                <option value="business_loss">Business Loss/Failure</option>
                                <option value="medical_emergency">Medical Emergency</option>
                                <option value="natural_disaster">Natural Disaster/Force Majeure</option>
                                <option value="temporary_cash_flow">Temporary Cash Flow Issues</option>
                                <option value="other">Other</option>
                            </select>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Restructure Comments -->
                        <div class="mb-3">
                            <label for="comments" class="form-label">Detailed Comments <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('comments') is-invalid @enderror" id="comments" name="comments" rows="3" required placeholder="Explain the circumstances and justification for restructuring..."></textarea>
                            @error('comments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">New Loan Terms</h5>

                        <div class="row">
                            <!-- New Principal Amount -->
                            <div class="col-md-6 mb-3">
                                <label for="new_principal" class="form-label">New Principal Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('new_principal') is-invalid @enderror" 
                                       id="new_principal" name="new_principal" 
                                       value="{{ old('new_principal', $loan->outstanding_balance) }}" 
                                       min="1000" step="100" required>
                                <small class="form-text text-muted">Current outstanding: UGX {{ number_format($loan->outstanding_balance, 0) }}</small>
                                @error('new_principal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Interest Rate -->
                            <div class="col-md-6 mb-3">
                                <label for="new_interest" class="form-label">New Interest Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('new_interest') is-invalid @enderror" 
                                       id="new_interest" name="new_interest" 
                                       value="{{ old('new_interest', $loan->interest_rate) }}" 
                                       min="0" max="100" step="0.1" required>
                                <small class="form-text text-muted">Current rate: {{ $loan->interest_rate }}%</small>
                                @error('new_interest')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- New Loan Period -->
                            <div class="col-md-6 mb-3">
                                <label for="new_period" class="form-label">New Loan Period <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('new_period') is-invalid @enderror" 
                                       id="new_period" name="new_period" 
                                       value="{{ old('new_period', $loan->loan_term) }}" 
                                       min="1" max="260" required>
                                <small class="form-text text-muted">Current period: {{ $loan->loan_term }} {{ $loan->period_type_name ?? 'installments' }}</small>
                                @error('new_period')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Grace Period (Optional) -->
                            <div class="col-md-6 mb-3">
                                <label for="grace_period" class="form-label">Grace Period (Optional)</label>
                                <input type="number" class="form-control" id="grace_period" name="grace_period" 
                                       value="{{ old('grace_period', 0) }}" min="0" max="12">
                                <small class="form-text text-muted">Number of periods before first payment (0 = no grace period)</small>
                            </div>
                        </div>

                        <!-- Waive Late Fees -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="waive_late_fees" name="waive_late_fees" value="1" {{ old('waive_late_fees') ? 'checked' : '' }}>
                                <label class="form-check-label" for="waive_late_fees">
                                    Waive all accumulated late fees (UGX {{ number_format($loan->total_late_fees ?? 0, 0) }})
                                </label>
                            </div>
                        </div>

                        <!-- Confirmation -->
                        <div class="alert alert-warning">
                            <h6><i class="mdi mdi-alert-outline me-2"></i>Important</h6>
                            <ul class="mb-0">
                                <li>The original loan will be marked as "Restructured" and closed</li>
                                <li>A new loan will be created with the modified terms</li>
                                <li>All payments made on the original loan will be recorded</li>
                                <li>The borrower must sign a new loan agreement</li>
                                <li>This action requires management approval</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm" name="confirm" value="1" required>
                                <label class="form-check-label" for="confirm">
                                    I confirm that I have reviewed the borrower's situation and this restructuring is justified
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-warning" id="submitBtn">
                                <i class="mdi mdi-check me-1"></i> Submit for Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Current Loan Details -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="card-title mb-0 text-white">Current Loan Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Loan Code:</th>
                            <td>{{ $loan->code }}</td>
                        </tr>
                        <tr>
                            <th>Borrower:</th>
                            <td>{{ $loan->borrower_name }}</td>
                        </tr>
                        <tr>
                            <th>Product:</th>
                            <td>{{ $loan->product_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Original Principal:</th>
                            <td>UGX {{ number_format($loan->principal_amount, 0) }}</td>
                        </tr>
                        <tr>
                            <th>Interest Rate:</th>
                            <td>{{ number_format($loan->interest_rate, 2) }}%</td>
                        </tr>
                        <tr>
                            <th>Loan Period:</th>
                            <td>{{ $loan->loan_term }} {{ $loan->period_type_name ?? 'periods' }}</td>
                        </tr>
                        <tr>
                            <th>Disbursement Date:</th>
                            <td>{{ $loan->disbursement_date ? date('M d, Y', strtotime($loan->disbursement_date)) : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Total Payable:</th>
                            <td>UGX {{ number_format($loan->total_payable, 0) }}</td>
                        </tr>
                        <tr>
                            <th>Amount Paid:</th>
                            <td class="text-success">UGX {{ number_format($loan->amount_paid, 0) }}</td>
                        </tr>
                        <tr>
                            <th>Outstanding Balance:</th>
                            <td class="text-danger"><strong>UGX {{ number_format($loan->outstanding_balance, 0) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Days Overdue:</th>
                            <td class="{{ $loan->days_overdue > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $loan->days_overdue }} {{ $loan->days_overdue == 1 ? 'day' : 'days' }}
                            </td>
                        </tr>
                        @if($loan->total_late_fees > 0)
                        <tr>
                            <th>Late Fees:</th>
                            <td class="text-danger">UGX {{ number_format($loan->total_late_fees, 0) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Repayment History Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Repayment History</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Installments:</strong> {{ $loan->loan_term }}</p>
                    <p><strong>Paid Installments:</strong> {{ $paidCount ?? 0 }}</p>
                    <p><strong>Pending Installments:</strong> {{ $pendingCount ?? 0 }}</p>
                    <p><strong>Overdue Installments:</strong> <span class="text-danger">{{ $overdueCount ?? 0 }}</span></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Calculate new installment on input change
    $('#new_principal, #new_interest, #new_period').on('input', function() {
        calculateNewInstallment();
    });

    function calculateNewInstallment() {
        const principal = parseFloat($('#new_principal').val()) || 0;
        const rate = parseFloat($('#new_interest').val()) || 0;
        const period = parseInt($('#new_period').val()) || 1;

        if (principal > 0 && period > 0) {
            // Simple calculation: (Principal + Interest) / Period
            const totalInterest = principal * (rate / 100);
            const totalPayable = principal + totalInterest;
            const installment = totalPayable / period;

            // Show preview (you can add a preview section if needed)
            console.log('Estimated installment:', installment);
        }
    }

    // Form validation
    $('#restructureForm').on('submit', function(e) {
        const newPrincipal = parseFloat($('#new_principal').val());
        const outstanding = {{ $loan->outstanding_balance }};

        if (newPrincipal < outstanding * 0.5) {
            if (!confirm('The new principal is significantly lower than the outstanding balance. Continue?')) {
                e.preventDefault();
                return false;
            }
        }

        $('#submitBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i> Processing...');
    });
});
</script>
@endpush
