@extends('layouts.admin')

@section('title', 'Add New Investor')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Add New Investor</h4>
                <p class="text-muted mb-0">Register a new investor to start investment cycles</p>
            </div>
            <div>
                <a href="{{ route('admin.investments.investors') }}" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to Investors
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Investor Registration Form -->
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

                <form action="{{ route('admin.investments.store-investor') }}" method="POST" id="investorForm">
                    @csrf
                    
                    <h6 class="mb-3">Personal Information</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">First Name</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="{{ old('first_name') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Last Name</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="{{ old('last_name') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Phone</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="{{ old('phone') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" 
                                       value="{{ old('dob') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Gender</label>
                                <select class="form-control" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Marital Status</label>
                        <select class="form-control" name="marital_status">
                            <option value="">Select Status</option>
                            <option value="Single" {{ old('marital_status') == 'Single' ? 'selected' : '' }}>Single</option>
                            <option value="Married" {{ old('marital_status') == 'Married' ? 'selected' : '' }}>Married</option>
                            <option value="Divorced" {{ old('marital_status') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="Widowed" {{ old('marital_status') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>

                    <h6 class="mb-3 mt-4">Location Information</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Country</label>
                                <select class="form-control" name="country_id" id="country_id" required>
                                    <option value="">Select Country</option>
                                    @foreach(\App\Models\Country::orderBy('name')->get() as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">State/Province</label>
                                <select class="form-control" name="state_id" id="state_id">
                                    <option value="">Select State</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <select class="form-control" name="city_id" id="city_id">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" 
                                       value="{{ old('postal_code') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3">{{ old('address') }}</textarea>
                    </div>

                    <h6 class="mb-3 mt-4">Investment Profile</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required">Investor Type</label>
                                <select class="form-control" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="local" {{ old('type') == 'local' ? 'selected' : '' }}>Local Investor</option>
                                    <option value="international" {{ old('type') == 'international' ? 'selected' : '' }}>International Investor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Investment Experience</label>
                                <select class="form-control" name="experience">
                                    <option value="">Select Experience</option>
                                    <option value="Beginner" {{ old('experience') == 'Beginner' ? 'selected' : '' }}>Beginner (0-1 year)</option>
                                    <option value="Intermediate" {{ old('experience') == 'Intermediate' ? 'selected' : '' }}>Intermediate (1-5 years)</option>
                                    <option value="Experienced" {{ old('experience') == 'Experienced' ? 'selected' : '' }}>Experienced (5+ years)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Risk Tolerance</label>
                                <select class="form-control" name="risk_tolerance">
                                    <option value="">Select Risk Level</option>
                                    <option value="Conservative" {{ old('risk_tolerance') == 'Conservative' ? 'selected' : '' }}>Conservative</option>
                                    <option value="Moderate" {{ old('risk_tolerance') == 'Moderate' ? 'selected' : '' }}>Moderate</option>
                                    <option value="Aggressive" {{ old('risk_tolerance') == 'Aggressive' ? 'selected' : '' }}>Aggressive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Investment Goal</label>
                                <select class="form-control" name="investment_goal">
                                    <option value="">Select Goal</option>
                                    <option value="Income" {{ old('investment_goal') == 'Income' ? 'selected' : '' }}>Regular Income</option>
                                    <option value="Growth" {{ old('investment_goal') == 'Growth' ? 'selected' : '' }}>Capital Growth</option>
                                    <option value="Retirement" {{ old('investment_goal') == 'Retirement' ? 'selected' : '' }}>Retirement Planning</option>
                                    <option value="Education" {{ old('investment_goal') == 'Education' ? 'selected' : '' }}>Education Fund</option>
                                    <option value="Emergency" {{ old('investment_goal') == 'Emergency' ? 'selected' : '' }}>Emergency Fund</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3 mt-4">Additional Information</h6>

                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" class="form-control" name="occupation" 
                               value="{{ old('occupation') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Annual Income Range</label>
                        <select class="form-control" name="income_range">
                            <option value="">Select Range</option>
                            <option value="Under $25,000" {{ old('income_range') == 'Under $25,000' ? 'selected' : '' }}>Under $25,000</option>
                            <option value="$25,000 - $50,000" {{ old('income_range') == '$25,000 - $50,000' ? 'selected' : '' }}>$25,000 - $50,000</option>
                            <option value="$50,000 - $100,000" {{ old('income_range') == '$50,000 - $100,000' ? 'selected' : '' }}>$50,000 - $100,000</option>
                            <option value="$100,000 - $250,000" {{ old('income_range') == '$100,000 - $250,000' ? 'selected' : '' }}>$100,000 - $250,000</option>
                            <option value="Over $250,000" {{ old('income_range') == 'Over $250,000' ? 'selected' : '' }}>Over $250,000</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Any additional information about the investor...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label">
                                Active Investor
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="mdi mdi-account-plus"></i> Register Investor
                        </button>
                        <a href="{{ route('admin.investments.investors') }}" class="btn btn-secondary btn-lg ml-2">
                            <i class="mdi mdi-close"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Info -->
    <div class="col-xl-4 col-lg-4">
        <div class="card card-bordered">
            <div class="card-body">
                <h6 class="card-title mb-3">Investment Information</h6>
                
                <div class="mb-3">
                    <h6 class="text-primary">Investment Types Available</h6>
                    <ul class="list-unstyled mb-0">
                        <li><i class="mdi mdi-check text-success"></i> Standard Interest (2.56% - 4.84%)</li>
                        <li><i class="mdi mdi-check text-success"></i> Compound Interest (7.5% - 10.55%)</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="text-primary">Investment Areas</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Micro Business Groups</li>
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Individual micro businesses</li>
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Livestock trading services</li>
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Education</li>
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Metal fabrication</li>
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Housing Microfinance</li>
                        <li><i class="mdi mdi-arrow-right text-muted"></i> Farming</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="text-primary">Key Benefits</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="mdi mdi-star text-warning"></i> Minimum investment: $1,000</li>
                        <li><i class="mdi mdi-star text-warning"></i> Flexible investment periods</li>
                        <li><i class="mdi mdi-star text-warning"></i> Regular return calculations</li>
                        <li><i class="mdi mdi-star text-warning"></i> Portfolio tracking</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <h6 class="alert-heading">Next Steps</h6>
                    <p class="mb-0 small">After registration, you can immediately start creating investments for this investor with flexible terms and competitive returns.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Handle country/state/city dropdowns
$('#country_id').change(function() {
    const countryId = $(this).val();
    $('#state_id').empty().append('<option value="">Select State</option>');
    $('#city_id').empty().append('<option value="">Select City</option>');
    
    if (countryId) {
        $.ajax({
            url: '{{ route("api.states") }}',
            method: 'GET',
            data: { country_id: countryId },
            success: function(states) {
                $.each(states, function(index, state) {
                    $('#state_id').append('<option value="' + state.id + '">' + state.name + '</option>');
                });
            }
        });
    }
});

$('#state_id').change(function() {
    const stateId = $(this).val();
    $('#city_id').empty().append('<option value="">Select City</option>');
    
    if (stateId) {
        $.ajax({
            url: '{{ route("api.cities") }}',
            method: 'GET',
            data: { state_id: stateId },
            success: function(cities) {
                $.each(cities, function(index, city) {
                    $('#city_id').append('<option value="' + city.id + '">' + city.name + '</option>');
                });
            }
        });
    }
});

// Form validation
$('#investorForm').on('submit', function(e) {
    let isValid = true;
    let errorMessage = '';
    
    // Check required fields
    $('input[required], select[required]').each(function() {
        if (!$(this).val()) {
            isValid = false;
            errorMessage += 'Please fill in all required fields.\n';
            return false;
        }
    });
    
    // Validate email format
    const email = $('input[name="email"]').val();
    if (email && !isValidEmail(email)) {
        isValid = false;
        errorMessage += 'Please enter a valid email address.\n';
    }
    
    // Validate phone format
    const phone = $('input[name="phone"]').val();
    if (phone && phone.length < 10) {
        isValid = false;
        errorMessage += 'Please enter a valid phone number.\n';
    }
    
    // Validate date of birth (must be at least 18 years old)
    const dob = new Date($('input[name="dob"]').val());
    const today = new Date();
    const age = today.getFullYear() - dob.getFullYear();
    if (age < 18) {
        isValid = false;
        errorMessage += 'Investor must be at least 18 years old.\n';
    }
    
    if (!isValid) {
        e.preventDefault();
        alert(errorMessage);
    }
});

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Restore old values for location dropdowns
@if(old('country_id'))
    $('#country_id').val('{{ old("country_id") }}').trigger('change');
    
    setTimeout(function() {
        @if(old('state_id'))
            $('#state_id').val('{{ old("state_id") }}').trigger('change');
            
            setTimeout(function() {
                @if(old('city_id'))
                    $('#city_id').val('{{ old("city_id") }}');
                @endif
            }, 500);
        @endif
    }, 500);
@endif
</script>
@endpush

@push('styles')
<style>
.required::after {
    content: ' *';
    color: #dc3545;
}
</style>
@endpush
@endsection