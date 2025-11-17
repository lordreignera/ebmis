@extends('layouts.admin')

@section('title', 'Create New Member')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Create New Member</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.members.index') }}">Members</a></li>
                        <li class="breadcrumb-item active">Create New</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Member Information</h4>
                <p class="card-description">Fill in the member details below</p>

                <form action="{{ route('admin.members.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Personal Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Personal Information</h5>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="code" class="required">Account Code</label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                       id="code" name="code" value="{{ old('code', $accountCode) }}" readonly 
                                       style="background-color: #f8f9fa;">
                                <small class="form-text text-muted">Auto-generated account code</small>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="branch_id" class="required">Branch</label>
                                <select class="form-control @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="member_type" class="required">Member Type</label>
                                <select class="form-control @error('member_type') is-invalid @enderror" id="member_type" name="member_type" required onchange="toggleGroupFields()">
                                    <option value="">Select Member Type</option>
                                    @foreach($memberTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('member_type') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('member_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3" id="group_field" style="display: none;">
                            <div class="form-group">
                                <label for="group_id">Group</label>
                                <select class="form-control @error('group_id') is-invalid @enderror" id="group_id" name="group_id">
                                    <option value="">Select Group</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fname" class="required">First Name</label>
                                <input type="text" class="form-control @error('fname') is-invalid @enderror" 
                                       id="fname" name="fname" value="{{ old('fname') }}" required>
                                @error('fname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mname">Middle Name</label>
                                <input type="text" class="form-control @error('mname') is-invalid @enderror" 
                                       id="mname" name="mname" value="{{ old('mname') }}">
                                @error('mname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lname" class="required">Last Name</label>
                                <input type="text" class="form-control @error('lname') is-invalid @enderror" 
                                       id="lname" name="lname" value="{{ old('lname') }}" required>
                                @error('lname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="gender" class="required">Gender</label>
                                <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dob" class="required">Date of Birth</label>
                                <input type="date" class="form-control @error('dob') is-invalid @enderror" 
                                       id="dob" name="dob" value="{{ old('dob') }}" required>
                                @error('dob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="nin">National ID Number</label>
                                <input type="text" class="form-control @error('nin') is-invalid @enderror" 
                                       id="nin" name="nin" value="{{ old('nin') }}" onblur="checkDuplicate('nin', this.value)">
                                <div class="duplicate-warning" id="nin-warning" style="display: none;"></div>
                                @error('nin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Contact Information</h5>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="contact" class="required">Primary Contact</label>
                                <input type="text" class="form-control @error('contact') is-invalid @enderror" 
                                       id="contact" name="contact" value="{{ old('contact') }}" required 
                                       placeholder="256700000000" onblur="checkDuplicate('contact', this.value)">
                                <div class="duplicate-warning" id="contact-warning" style="display: none;"></div>
                                @error('contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="alt_contact">Alternative Contact</label>
                                <input type="text" class="form-control @error('alt_contact') is-invalid @enderror" 
                                       id="alt_contact" name="alt_contact" value="{{ old('alt_contact') }}" 
                                       placeholder="256700000000">
                                @error('alt_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" 
                                       placeholder="member@example.com" onblur="checkDuplicate('email', this.value)">
                                <div class="duplicate-warning" id="email-warning" style="display: none;"></div>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fixed_line">Fixed Line</label>
                                <input type="text" class="form-control @error('fixed_line') is-invalid @enderror" 
                                       id="fixed_line" name="fixed_line" value="{{ old('fixed_line') }}">
                                @error('fixed_line')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="mobile_pin">Mobile PIN</label>
                                <input type="text" class="form-control" 
                                       id="mobile_pin" name="mobile_pin" value="{{ old('mobile_pin') }}" 
                                       placeholder="4-digit PIN" maxlength="10">
                                <small class="form-text text-muted">Mobile money PIN if applicable</small>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Place of Residence</h5>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="plot_no">Plot Number</label>
                                <input type="text" class="form-control @error('plot_no') is-invalid @enderror" 
                                       id="plot_no" name="plot_no" value="{{ old('plot_no') }}">
                                @error('plot_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="village">Village</label>
                                <input type="text" class="form-control @error('village') is-invalid @enderror" 
                                       id="village" name="village" value="{{ old('village') }}">
                                @error('village')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="parish">Parish</label>
                                <input type="text" class="form-control @error('parish') is-invalid @enderror" 
                                       id="parish" name="parish" value="{{ old('parish') }}">
                                @error('parish')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="subcounty">Sub County</label>
                                <input type="text" class="form-control @error('subcounty') is-invalid @enderror" 
                                       id="subcounty" name="subcounty" value="{{ old('subcounty') }}">
                                @error('subcounty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="county">County</label>
                                <input type="text" class="form-control @error('county') is-invalid @enderror" 
                                       id="county" name="county" value="{{ old('county') }}">
                                @error('county')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="country_id" class="required">Country</label>
                                <select class="form-control @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                                    <option value="">Select Country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Place of Birth -->
                    <div class="row mt-4">
                        <div class="col-md-12 d-flex justify-content-between align-items-center">
                            <h5 class="mb-3 text-primary">Place of Birth</h5>
                            <div>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="copyResidenceToBirth()">
                                    <i class="mdi mdi-content-copy"></i> Copy from Residence
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyBirthToResidence()">
                                    <i class="mdi mdi-content-copy"></i> Copy to Residence
                                </button>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="birth_plot_no">Plot Number</label>
                                <input type="text" class="form-control @error('birth_plot_no') is-invalid @enderror" 
                                       id="birth_plot_no" name="birth_plot_no" value="{{ old('birth_plot_no') }}">
                                @error('birth_plot_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="birth_village">Village</label>
                                <input type="text" class="form-control @error('birth_village') is-invalid @enderror" 
                                       id="birth_village" name="birth_village" value="{{ old('birth_village') }}">
                                @error('birth_village')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="birth_parish">Parish</label>
                                <input type="text" class="form-control @error('birth_parish') is-invalid @enderror" 
                                       id="birth_parish" name="birth_parish" value="{{ old('birth_parish') }}">
                                @error('birth_parish')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="birth_subcounty">Sub County</label>
                                <input type="text" class="form-control @error('birth_subcounty') is-invalid @enderror" 
                                       id="birth_subcounty" name="birth_subcounty" value="{{ old('birth_subcounty') }}">
                                @error('birth_subcounty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="birth_county">County</label>
                                <input type="text" class="form-control @error('birth_county') is-invalid @enderror" 
                                       id="birth_county" name="birth_county" value="{{ old('birth_county') }}">
                                @error('birth_county')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birth_country_id">Country</label>
                                <select class="form-control @error('birth_country_id') is-invalid @enderror" id="birth_country_id" name="birth_country_id">
                                    <option value="">Select Country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ old('birth_country_id') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('birth_country_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Document Uploads -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Document Uploads</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pp_file">Passport Photo</label>
                                <input type="file" class="form-control @error('pp_file') is-invalid @enderror" 
                                       id="pp_file" name="pp_file" accept="image/*">
                                <small class="form-text text-muted">Upload member's passport photo (JPG, PNG, GIF)</small>
                                @error('pp_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_file">ID Document</label>
                                <input type="file" class="form-control @error('id_file') is-invalid @enderror" 
                                       id="id_file" name="id_file" accept="image/*,application/pdf">
                                <small class="form-text text-muted">Upload ID copy (JPG, PNG, PDF)</small>
                                @error('id_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="mdi mdi-check"></i> Create Member
                                </button>
                                <a href="{{ route('admin.members.index') }}" class="btn btn-light">
                                    <i class="mdi mdi-close"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.required:after {
    content: ' *';
    color: red;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 8px;
}

.card-title {
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.text-primary {
    color: #007bff !important;
}

.form-control {
    border-radius: 5px;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.duplicate-warning {
    margin-top: 5px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.duplicate-warning.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.duplicate-warning.checking {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.duplicate-info {
    margin-top: 5px;
    font-size: 0.8rem;
    color: #6c757d;
}

.readonly-field {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

#group_field {
    display: none;
}
</style>
@endpush

@push('scripts')
<script>
function toggleGroupFields() {
    const memberType = document.getElementById('member_type').value;
    const groupField = document.getElementById('group_field');
    
    // Show group field only for group member type (ID 2 based on seeder)
    if (memberType == '2') {
        groupField.style.display = 'block';
        document.getElementById('group_id').required = true;
    } else {
        groupField.style.display = 'none';
        document.getElementById('group_id').required = false;
        document.getElementById('group_id').value = '';
    }
}

function checkDuplicate(field, value) {
    if (!value || value.trim() === '') {
        hideWarning(field);
        return;
    }
    
    // Show checking indicator
    showChecking(field);
    
    $.ajax({
        url: '{{ route("admin.members.check-duplicate") }}',
        method: 'POST',
        data: {
            field: field,
            value: value.trim(),
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            hideChecking(field);
            
            if (response.exists) {
                showDuplicateWarning(field, response);
            } else {
                hideWarning(field);
            }
        },
        error: function() {
            hideChecking(field);
        }
    });
}

function showChecking(field) {
    const warningDiv = document.getElementById(field + '-warning');
    warningDiv.className = 'duplicate-warning checking';
    warningDiv.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Checking for duplicates...';
    warningDiv.style.display = 'block';
}

function hideChecking(field) {
    const warningDiv = document.getElementById(field + '-warning');
    warningDiv.classList.remove('checking');
}

function showDuplicateWarning(field, response) {
    const warningDiv = document.getElementById(field + '-warning');
    const fieldInput = document.getElementById(field);
    
    warningDiv.className = 'duplicate-warning error';
    warningDiv.innerHTML = `
        <strong>⚠️ Duplicate Found!</strong><br>
        This ${response.field_name} is already registered to:<br>
        <strong>${response.member.name}</strong> (Code: ${response.member.code})<br>
        Branch: ${response.member.branch}
    `;
    warningDiv.style.display = 'block';
    
    // Add error styling to input
    fieldInput.classList.add('is-invalid');
}

function hideWarning(field) {
    const warningDiv = document.getElementById(field + '-warning');
    const fieldInput = document.getElementById(field);
    
    warningDiv.style.display = 'none';
    fieldInput.classList.remove('is-invalid');
}

$(document).ready(function() {
    // Initialize group field visibility
    toggleGroupFields();
    
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        let duplicatesFound = false;
        
        // Check for visible duplicate warnings
        $('.duplicate-warning.error:visible').each(function() {
            duplicatesFound = true;
        });
        
        if (duplicatesFound) {
            e.preventDefault();
            alert('Please resolve duplicate member conflicts before submitting.');
            return false;
        }
        
        // Check required fields
        $('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Remove validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
    // Phone number formatting
    $('input[name="contact"], input[name="alt_contact"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        
        // Remove leading zero
        if (value.startsWith('0')) {
            value = value.substring(1);
        }
        
        // Only add 256 if not already present
        if (value.length > 0 && !value.startsWith('256')) {
            value = '256' + value;
        }
        
        $(this).val(value);
    });
    
    // Auto-format NIN (remove spaces and special characters)
    $('input[name="nin"]').on('input', function() {
        let value = $(this).val().replace(/[^A-Z0-9]/gi, '').toUpperCase();
        $(this).val(value);
    });
});

// Copy residence details to birth place
function copyResidenceToBirth() {
    if (confirm('This will copy all residence details to place of birth. Continue?')) {
        // Copy all residence fields to birth fields
        document.getElementById('birth_plot_no').value = document.getElementById('plot_no').value;
        document.getElementById('birth_village').value = document.getElementById('village').value;
        document.getElementById('birth_parish').value = document.getElementById('parish').value;
        document.getElementById('birth_subcounty').value = document.getElementById('subcounty').value;
        document.getElementById('birth_county').value = document.getElementById('county').value;
        document.getElementById('birth_country_id').value = document.getElementById('country_id').value;
        
        // Show success message
        showNotification('Residence details copied to place of birth successfully!', 'success');
    }
}

// Copy birth place details to residence
function copyBirthToResidence() {
    if (confirm('This will copy all place of birth details to residence. Continue?')) {
        // Copy all birth fields to residence fields
        document.getElementById('plot_no').value = document.getElementById('birth_plot_no').value;
        document.getElementById('village').value = document.getElementById('birth_village').value;
        document.getElementById('parish').value = document.getElementById('birth_parish').value;
        document.getElementById('subcounty').value = document.getElementById('birth_subcounty').value;
        document.getElementById('county').value = document.getElementById('birth_county').value;
        document.getElementById('country_id').value = document.getElementById('birth_country_id').value;
        
        // Show success message
        showNotification('Place of birth details copied to residence successfully!', 'success');
    }
}

// Show notification (you can customize this based on your notification system)
function showNotification(message, type) {
    // Create a simple Bootstrap alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}
</script>
@endpush
@endsection