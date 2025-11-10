@extends('layouts.admin')

@section('title', 'Apply for Group Loan')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Apply for Group Loan</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.groups.show', $group->id) }}" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i> Back to Group
            </a>
        </div>
    </div>

    <!-- Group Information Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="mdi mdi-account-group"></i> Group Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Group Name:</strong><br>
                            <span class="text-muted">{{ $group->name }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Group Code:</strong><br>
                            <span class="text-muted">{{ $group->group_code }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Total Members:</strong><br>
                            <span class="text-muted">{{ $group->members->count() }}/{{ \App\Models\Group::MAX_MEMBERS }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Loan Eligibility:</strong><br>
                            @if($group->isEligibleForGroupLoan())
                                <span class="badge badge-success">Eligible</span>
                            @else
                                <span class="badge badge-warning">Not Eligible</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$group->isEligibleForGroupLoan())
    <!-- Eligibility Warning -->
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <i class="mdi mdi-alert-circle-outline"></i>
                <strong>Group Not Eligible</strong>
                <p class="mb-0">{{ $group->getLoanEligibilityStatus()['message'] }}</p>
            </div>
        </div>
    </div>
    @else
    <!-- Loan Application Form -->
    <form action="{{ route('admin.group-loans.store') }}" method="POST" id="groupLoanForm">
        @csrf
        <input type="hidden" name="group_id" value="{{ $group->id }}">
        
        <div class="row">
            <!-- Loan Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="mdi mdi-cash-multiple"></i> Loan Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="loan_amount" class="required">Loan Amount (UGX)</label>
                                    <input type="number" name="loan_amount" id="loan_amount" 
                                           class="form-control @error('loan_amount') is-invalid @enderror" 
                                           value="{{ old('loan_amount') }}" min="50000" step="1000" required>
                                    @error('loan_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Minimum loan amount: UGX 50,000</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="loan_period" class="required">Loan Period (Months)</label>
                                    <select name="loan_period" id="loan_period" 
                                            class="form-control @error('loan_period') is-invalid @enderror" required>
                                        <option value="">Select period...</option>
                                        <option value="3" {{ old('loan_period') == '3' ? 'selected' : '' }}>3 Months</option>
                                        <option value="6" {{ old('loan_period') == '6' ? 'selected' : '' }}>6 Months</option>
                                        <option value="12" {{ old('loan_period') == '12' ? 'selected' : '' }}>12 Months</option>
                                        <option value="18" {{ old('loan_period') == '18' ? 'selected' : '' }}>18 Months</option>
                                        <option value="24" {{ old('loan_period') == '24' ? 'selected' : '' }}>24 Months</option>
                                    </select>
                                    @error('loan_period')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="interest_rate" class="required">Interest Rate (%)</label>
                                    <input type="number" name="interest_rate" id="interest_rate" 
                                           class="form-control @error('interest_rate') is-invalid @enderror" 
                                           value="{{ old('interest_rate', '15') }}" min="1" max="30" step="0.1" required>
                                    @error('interest_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Annual interest rate</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_frequency" class="required">Payment Frequency</label>
                                    <select name="payment_frequency" id="payment_frequency" 
                                            class="form-control @error('payment_frequency') is-invalid @enderror" required>
                                        <option value="">Select frequency...</option>
                                        <option value="weekly" {{ old('payment_frequency') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ old('payment_frequency') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                    @error('payment_frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="purpose">Loan Purpose</label>
                                    <textarea name="purpose" id="purpose" rows="3" 
                                              class="form-control @error('purpose') is-invalid @enderror" 
                                              placeholder="Describe the purpose of this group loan...">{{ old('purpose') }}</textarea>
                                    @error('purpose')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loan Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="mdi mdi-calculator"></i> Loan Summary</h5>
                    </div>
                    <div class="card-body">
                        <div id="loanSummary">
                            <div class="text-center text-muted">
                                <i class="mdi mdi-calculator" style="font-size: 48px;"></i>
                                <p>Enter loan details to see summary</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Group Members -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="mdi mdi-account-multiple"></i> Group Members</h5>
                    </div>
                    <div class="card-body">
                        @foreach($group->members as $member)
                        <div class="d-flex align-items-center mb-2">
                            <div class="mr-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="width: 32px; height: 32px; font-size: 12px;">
                                    {{ strtoupper(substr($member->fname, 0, 1) . substr($member->lname, 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $member->fname }} {{ $member->lname }}</div>
                                <small class="text-muted">{{ $member->pm_code }}</small>
                            </div>
                            <div>
                                <span class="badge badge-success">Active</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="mdi mdi-file-document-outline"></i> Terms and Conditions</h5>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="terms_accepted" name="terms_accepted" required>
                            <label class="custom-control-label" for="terms_accepted">
                                I acknowledge that all group members have agreed to the terms and conditions of this group loan
                            </label>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Important Notes:</strong>
                                <ul class="mb-0">
                                    <li>All group members are jointly liable for loan repayment</li>
                                    <li>Default by any member affects the entire group's credit rating</li>
                                    <li>Group meetings must be maintained throughout the loan period</li>
                                    <li>Changes to group membership require approval during loan period</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.groups.show', $group->id) }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check"></i> Submit Loan Application
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Calculate loan summary when inputs change
    $('#loan_amount, #loan_period, #interest_rate, #payment_frequency').on('input change', function() {
        calculateLoanSummary();
    });

    // Form validation
    $('#groupLoanForm').on('submit', function(e) {
        if (!$('#terms_accepted').is(':checked')) {
            e.preventDefault();
            showAlert('warning', 'Please accept the terms and conditions to proceed');
            return false;
        }
    });
});

function calculateLoanSummary() {
    var amount = parseFloat($('#loan_amount').val()) || 0;
    var period = parseInt($('#loan_period').val()) || 0;
    var rate = parseFloat($('#interest_rate').val()) || 0;
    var frequency = $('#payment_frequency').val();

    if (amount > 0 && period > 0 && rate > 0 && frequency) {
        // Simple interest calculation
        var totalInterest = (amount * rate * period) / (12 * 100);
        var totalAmount = amount + totalInterest;
        
        var installments;
        var installmentAmount;
        
        if (frequency === 'weekly') {
            installments = period * 4; // Approximate weeks per month
            installmentAmount = totalAmount / installments;
        } else {
            installments = period;
            installmentAmount = totalAmount / installments;
        }

        var html = `
            <div class="loan-summary">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Principal Amount:</span>
                            <strong>UGX ${formatNumber(amount)}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Interest:</span>
                            <strong>UGX ${formatNumber(totalInterest)}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span><strong>Total Payable:</strong></span>
                            <strong class="text-primary">UGX ${formatNumber(totalAmount)}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Number of ${frequency} payments:</span>
                            <strong>${installments}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>${frequency.charAt(0).toUpperCase() + frequency.slice(1)} payment:</span>
                            <strong class="text-success">UGX ${formatNumber(installmentAmount)}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#loanSummary').html(html);
    } else {
        $('#loanSummary').html(`
            <div class="text-center text-muted">
                <i class="mdi mdi-calculator" style="font-size: 48px;"></i>
                <p>Enter loan details to see summary</p>
            </div>
        `);
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-UG').format(Math.round(num));
}

function showAlert(type, message) {
    var alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        $('.alert').first().alert('close');
    }, 5000);
}
</script>
@endpush