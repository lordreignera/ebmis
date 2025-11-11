@extends('layouts.admin')

@section('title', 'Edit Member - ' . $member->fname . ' ' . $member->lname)

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Edit Member</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.members.index') }}">Members</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.members.show', $member->id) }}">{{ $member->fname }} {{ $member->lname }}</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.members.show', $member->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to Details
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
                <p class="card-description">Update member details below</p>

                <form action="{{ route('admin.members.update', $member->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <!-- Personal Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Personal Information</h5>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fname" class="required">First Name</label>
                                <input type="text" class="form-control @error('fname') is-invalid @enderror" 
                                       id="fname" name="fname" value="{{ old('fname', $member->fname) }}" required>
                                @error('fname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mname">Middle Name</label>
                                <input type="text" class="form-control @error('mname') is-invalid @enderror" 
                                       id="mname" name="mname" value="{{ old('mname', $member->mname) }}">
                                @error('mname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lname" class="required">Last Name</label>
                                <input type="text" class="form-control @error('lname') is-invalid @enderror" 
                                       id="lname" name="lname" value="{{ old('lname', $member->lname) }}" required>
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
                                    <option value="Male" {{ old('gender', $member->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $member->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender', $member->gender) == 'Other' ? 'selected' : '' }}>Other</option>
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
                                       id="dob" name="dob" value="{{ old('dob', $member->dob) }}" required>
                                @error('dob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="nin">National ID Number</label>
                                <input type="text" class="form-control @error('nin') is-invalid @enderror" 
                                       id="nin" name="nin" value="{{ old('nin', $member->nin) }}">
                                @error('nin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="member_type" class="required">Member Type</label>
                                <select class="form-control @error('member_type') is-invalid @enderror" id="member_type" name="member_type" required>
                                    <option value="">Select Member Type</option>
                                    @foreach($memberTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('member_type', $member->member_type) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('member_type')
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
                                       id="contact" name="contact" value="{{ old('contact', $member->contact) }}" required 
                                       placeholder="256700000000">
                                @error('contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="alt_contact">Alternative Contact</label>
                                <input type="text" class="form-control @error('alt_contact') is-invalid @enderror" 
                                       id="alt_contact" name="alt_contact" value="{{ old('alt_contact', $member->alt_contact) }}" 
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
                                       id="email" name="email" value="{{ old('email', $member->email) }}" 
                                       placeholder="member@example.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fixed_line">Fixed Line</label>
                                <input type="text" class="form-control @error('fixed_line') is-invalid @enderror" 
                                       id="fixed_line" name="fixed_line" value="{{ old('fixed_line', $member->fixed_line) }}">
                                @error('fixed_line')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="mobile_pin">Mobile PIN</label>
                                <input type="text" class="form-control" 
                                       id="mobile_pin" name="mobile_pin" value="{{ old('mobile_pin', $member->mobile_pin) }}" 
                                       placeholder="4-digit PIN" maxlength="10">
                                <small class="form-text text-muted">Mobile money PIN if applicable</small>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Address Information</h5>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="plot_no">Plot Number</label>
                                <input type="text" class="form-control @error('plot_no') is-invalid @enderror" 
                                       id="plot_no" name="plot_no" value="{{ old('plot_no', $member->plot_no) }}">
                                @error('plot_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="village">Village</label>
                                <input type="text" class="form-control @error('village') is-invalid @enderror" 
                                       id="village" name="village" value="{{ old('village', $member->village) }}">
                                @error('village')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="parish">Parish</label>
                                <input type="text" class="form-control @error('parish') is-invalid @enderror" 
                                       id="parish" name="parish" value="{{ old('parish', $member->parish) }}">
                                @error('parish')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="subcounty">Sub County</label>
                                <input type="text" class="form-control @error('subcounty') is-invalid @enderror" 
                                       id="subcounty" name="subcounty" value="{{ old('subcounty', $member->subcounty) }}">
                                @error('subcounty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="county">County</label>
                                <input type="text" class="form-control @error('county') is-invalid @enderror" 
                                       id="county" name="county" value="{{ old('county', $member->county) }}">
                                @error('county')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="country_id" class="required">Country</label>
                                <select class="form-control @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                                    <option value="">Select Country</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ old('country_id', $member->country_id) == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="branch_id" class="required">Branch</label>
                                <select class="form-control @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $member->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
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
                                @if($member->pp_file)
                                    <div class="mb-2">
                                        <span class="text-success">Current file: {{ basename($member->pp_file) }}</span>
                                    </div>
                                @endif
                                <input type="file" class="form-control @error('pp_file') is-invalid @enderror" 
                                       id="pp_file" name="pp_file" accept="image/*">
                                <small class="form-text text-muted">Upload new passport photo to replace current (JPG, PNG, GIF)</small>
                                @error('pp_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_file">ID Document</label>
                                @if($member->id_file)
                                    <div class="mb-2">
                                        <span class="text-success">Current file: {{ basename($member->id_file) }}</span>
                                    </div>
                                @endif
                                <input type="file" class="form-control @error('id_file') is-invalid @enderror" 
                                       id="id_file" name="id_file" accept="image/*,application/pdf">
                                <small class="form-text text-muted">Upload new ID copy to replace current (JPG, PNG, PDF)</small>
                                @error('id_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3 text-primary">Additional Information</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="verified">Verification Status</label>
                                <select class="form-control @error('verified') is-invalid @enderror" id="verified" name="verified">
                                    <option value="0" {{ old('verified', $member->verified) == '0' ? 'selected' : '' }}>Not Verified</option>
                                    <option value="1" {{ old('verified', $member->verified) == '1' ? 'selected' : '' }}>Verified</option>
                                </select>
                                @error('verified')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                    <option value="pending" {{ old('status', $member->status) == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="approved" {{ old('status', $member->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="suspended" {{ old('status', $member->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="rejected" {{ old('status', $member->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="comments">Comments</label>
                                <textarea class="form-control @error('comments') is-invalid @enderror" 
                                          id="comments" name="comments" rows="3" 
                                          placeholder="Any additional comments about this member">{{ old('comments', $member->comments) }}</textarea>
                                @error('comments')
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
                                    <i class="mdi mdi-check"></i> Update Member
                                </button>
                                <a href="{{ route('admin.members.show', $member->id) }}" class="btn btn-light">
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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
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
        if (value.length > 0 && !value.startsWith('256')) {
            value = '256' + value;
        }
        $(this).val(value);
    });
});
</script>
@endpush
@endsection