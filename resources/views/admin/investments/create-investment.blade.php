@extends('layouts.admin')

@section('title', 'Add Investment - ' . $investor->full_name)

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Add Investment</h4>
                <p class="text-muted mb-0">Enter the investment details for {{ $investor->full_name }}</p>
            </div>
            <div>
                <a href="{{ route('admin.investments.show-investor', $investor->id) }}" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to Investor
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Investment Form -->
    <div class="col-lg-8">
        <div class="card card-bordered">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.investments.store-investment', $investor->id) }}" method="POST" id="investmentForm">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">Enter Unique Name to Help You Identify Your Investment</label>
                        <input type="text" class="form-control" name="inv_name" value="{{ old('inv_name') }}" required 
                               placeholder="e.g., My First Investment, Retirement Fund, etc.">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Choose Quick Amount to Invest</label>
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <input type="radio" class="btn-check" name="quick_amount" id="amt_1000" value="1000" onchange="setAmount(this.value)">
                                <label class="btn btn-outline-primary w-100" for="amt_1000">$1,000</label>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="radio" class="btn-check" name="quick_amount" id="amt_2500" value="2500" onchange="setAmount(this.value)">
                                <label class="btn btn-outline-primary w-100" for="amt_2500">$2,500</label>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="radio" class="btn-check" name="quick_amount" id="amt_5000" value="5000" onchange="setAmount(this.value)">
                                <label class="btn btn-outline-primary w-100" for="amt_5000">$5,000</label>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="radio" class="btn-check" name="quick_amount" id="amt_10000" value="10000" onchange="setAmount(this.value)">
                                <label class="btn btn-outline-primary w-100" for="amt_10000">$10,000</label>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="radio" class="btn-check" name="quick_amount" id="amt_15000" value="15000" onchange="setAmount(this.value)">
                                <label class="btn btn-outline-primary w-100" for="amt_15000">$15,000</label>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="radio" class="btn-check" name="quick_amount" id="amt_20000" value="20000" onchange="setAmount(this.value)">
                                <label class="btn btn-outline-primary w-100" for="amt_20000">$20,000</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Or Enter Your Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">USD</span>
                            </div>
                            <input type="number" class="form-control" id="inv_amt" name="inv_amt" 
                                   value="{{ old('inv_amt') }}" min="1000" step="0.01" required
                                   onchange="calculateReturns()" onkeyup="calculateReturns()" 
                                   placeholder="Enter investment amount">
                        </div>
                        <small class="form-text text-muted">Note: Minimum investment to start cycle is $1,000</small>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Enter Investment Cycle Period</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="inv_period" name="inv_period" 
                                           value="{{ old('inv_period') }}" min="1" max="7" required
                                           onchange="calculateReturns()" onkeyup="calculateReturns()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Years</span>
                                    </div>
                                </div>
                                <small class="form-text text-danger" id="period_error"></small>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="form-label">Choose Investment Terms</label>
                                <select class="form-control" id="inv_term" name="inv_term" onchange="calculateReturns()" required>
                                    <option value="">Select...</option>
                                    <option value="1" {{ old('inv_term') == '1' ? 'selected' : '' }}>Standard Interest</option>
                                    <option value="2" {{ old('inv_term') == '2' ? 'selected' : '' }}>Compounding Interest</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Investment Areas of Interest</label>
                        <select class="form-control" multiple name="areas[]" required style="height: 150px;">
                            <option value="Micro Business Groups" {{ in_array('Micro Business Groups', old('areas', [])) ? 'selected' : '' }}>Micro Business Groups</option>
                            <option value="Individual micro businesses" {{ in_array('Individual micro businesses', old('areas', [])) ? 'selected' : '' }}>Individual micro businesses</option>
                            <option value="Livestock trading services" {{ in_array('Livestock trading services', old('areas', [])) ? 'selected' : '' }}>Livestock trading services</option>
                            <option value="Education" {{ in_array('Education', old('areas', [])) ? 'selected' : '' }}>Education</option>
                            <option value="Metal fabrication" {{ in_array('Metal fabrication', old('areas', [])) ? 'selected' : '' }}>Metal fabrication</option>
                            <option value="Housing Microfinance" {{ in_array('Housing Microfinance', old('areas', [])) ? 'selected' : '' }}>Housing Microfinance</option>
                            <option value="Farming" {{ in_array('Farming', old('areas', [])) ? 'selected' : '' }}>Farming</option>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple areas</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Investment Payment Details</label>
                        <textarea class="form-control" name="details" rows="4" required
                                  placeholder="Describe how you are making payments for your investment for efficient follow-up...">{{ old('details') }}</textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="mdi mdi-check"></i> Confirm & Proceed
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Investment Summary -->
    <div class="col-xl-4 col-lg-4">
        <div class="card card-bordered">
            <div class="card-body">
                <h6 class="card-title mb-3">Investment Details</h6>
                
                <!-- Investor Info -->
                <div class="mb-3 p-3 bg-light rounded">
                    <h6 class="mb-2">Investor Information</h6>
                    <div class="d-flex justify-content-between">
                        <span>Name:</span>
                        <span class="fw-bold">{{ $investor->full_name }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Country:</span>
                        <span>{{ $investor->country->name ?? 'Unknown' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Email:</span>
                        <span>{{ $investor->email }}</span>
                    </div>
                </div>

                <!-- Investment Summary -->
                <div id="investment_summary" style="display: none;">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Investment Type</span>
                            <span id="summary_type">&nbsp;</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Cycle Period</span>
                            <span id="summary_period">&nbsp;</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Annual profit</span>
                            <span id="summary_annual">&nbsp;</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Annual profit %</span>
                            <span id="summary_rate">&nbsp;</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total net profit</span>
                            <span class="text-success fw-bold" id="summary_interest">&nbsp;</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Return</span>
                            <span class="text-primary fw-bold" id="summary_total">&nbsp;</span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Term start at</span>
                            <span id="summary_start">{{ now()->format('F d, Y') }}</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Term end at</span>
                            <span id="summary_end">&nbsp;</span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Amount to invest</span>
                            <span id="summary_amount">&nbsp;</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Conversion Fee (0.5%)</span>
                            <span id="summary_fee">&nbsp;</span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total Charge</span>
                            <span class="text-primary fw-bold" id="summary_charge">&nbsp;</span>
                        </div>
                    </div>
                </div>

                <div id="no_calculation" class="text-center text-muted py-4">
                    <i class="mdi mdi-calculator" style="font-size: 48px;"></i>
                    <p>Enter investment details to see calculation</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setAmount(value) {
    document.getElementById('inv_amt').value = value;
    calculateReturns();
}

function calculateReturns() {
    const amount = parseFloat(document.getElementById('inv_amt').value);
    const period = parseInt(document.getElementById('inv_period').value);
    const term = parseInt(document.getElementById('inv_term').value);
    
    if (!amount || !period || !term) {
        document.getElementById('investment_summary').style.display = 'none';
        document.getElementById('no_calculation').style.display = 'block';
        return;
    }

    if (period > 7) {
        document.getElementById('period_error').innerHTML = "Period can't be more than 7 years";
        return;
    } else {
        document.getElementById('period_error').innerHTML = "";
    }

    // Make AJAX request to calculate returns
    $.ajax({
        url: '{{ route("admin.investments.calculate-returns") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            amount: amount,
            period: period,
            type: term
        },
        success: function(response) {
            document.getElementById('investment_summary').style.display = 'block';
            document.getElementById('no_calculation').style.display = 'none';
            
            document.getElementById('summary_type').innerHTML = response.type_name;
            document.getElementById('summary_period').innerHTML = response.period;
            document.getElementById('summary_rate').innerHTML = response.rate;
            document.getElementById('summary_annual').innerHTML = response.formatted.annual_profit;
            document.getElementById('summary_interest').innerHTML = response.formatted.total_interest;
            document.getElementById('summary_total').innerHTML = response.formatted.total_return;
            document.getElementById('summary_start').innerHTML = response.start_date;
            document.getElementById('summary_end').innerHTML = response.end_date;
            document.getElementById('summary_amount').innerHTML = response.formatted.amount;
            document.getElementById('summary_fee').innerHTML = response.formatted.conversion_fee;
            document.getElementById('summary_charge').innerHTML = response.formatted.total_charge;
        },
        error: function(xhr) {
            console.error('Error calculating returns:', xhr.responseJSON);
        }
    });
}

// Validate compound interest period
document.getElementById('inv_term').addEventListener('change', function() {
    const period = parseInt(document.getElementById('inv_period').value);
    const term = parseInt(this.value);
    
    if (term === 2 && period < 3) {
        document.getElementById('period_error').innerHTML = "Period for compounding interest not less than 3 yrs";
    } else {
        document.getElementById('period_error').innerHTML = "";
    }
    
    calculateReturns();
});
</script>
@endpush
@endsection