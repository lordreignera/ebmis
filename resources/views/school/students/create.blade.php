@extends('layouts.admin')

@section('title', 'Add New Student')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-account-plus me-2"></i>Add New Student
                    </h2>
                    <p class="text-muted mb-0">Enter student information</p>
                </div>
                <div>
                    <a href="{{ route('school.students.index') }}" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Students
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('school.students.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-account me-2"></i>Personal Information
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label" style="color: #000000;">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="{{ old('first_name') }}" 
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label" style="color: #000000;">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name') }}" 
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="other_names" class="form-label" style="color: #000000;">Other Names</label>
                                <input type="text" 
                                       class="form-control @error('other_names') is-invalid @enderror" 
                                       id="other_names" 
                                       name="other_names" 
                                       value="{{ old('other_names') }}">
                                @error('other_names')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="gender" class="form-label" style="color: #000000;">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('gender') is-invalid @enderror" 
                                        id="gender" 
                                        name="gender" 
                                        required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="date_of_birth" class="form-label" style="color: #000000;">
                                    Date of Birth <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       id="date_of_birth" 
                                       name="date_of_birth" 
                                       value="{{ old('date_of_birth') }}" 
                                       required>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="nationality" class="form-label" style="color: #000000;">Nationality</label>
                                <input type="text" 
                                       class="form-control @error('nationality') is-invalid @enderror" 
                                       id="nationality" 
                                       name="nationality" 
                                       value="{{ old('nationality', 'Ugandan') }}">
                                @error('nationality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="religion" class="form-label" style="color: #000000;">Religion</label>
                                <select class="form-select @error('religion') is-invalid @enderror" 
                                        id="religion" 
                                        name="religion">
                                    <option value="">Select Religion</option>
                                    <option value="Christian" {{ old('religion') == 'Christian' ? 'selected' : '' }}>Christian</option>
                                    <option value="Muslim" {{ old('religion') == 'Muslim' ? 'selected' : '' }}>Muslim</option>
                                    <option value="Hindu" {{ old('religion') == 'Hindu' ? 'selected' : '' }}>Hindu</option>
                                    <option value="Other" {{ old('religion') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('religion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="photo" class="form-label" style="color: #000000;">Student Photo</label>
                                <input type="file" 
                                       class="form-control @error('photo') is-invalid @enderror" 
                                       id="photo" 
                                       name="photo" 
                                       accept="image/*">
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Accepted: jpg, jpeg, png (Max: 2MB)</small>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-map-marker me-2"></i>Contact Information
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label" style="color: #000000;">Address</label>
                                <input type="text" 
                                       class="form-control @error('address') is-invalid @enderror" 
                                       id="address" 
                                       name="address" 
                                       value="{{ old('address') }}">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="district" class="form-label" style="color: #000000;">District</label>
                                <input type="text" 
                                       class="form-control @error('district') is-invalid @enderror" 
                                       id="district" 
                                       name="district" 
                                       value="{{ old('district') }}">
                                @error('district')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="village" class="form-label" style="color: #000000;">Village</label>
                                <input type="text" 
                                       class="form-control @error('village') is-invalid @enderror" 
                                       id="village" 
                                       name="village" 
                                       value="{{ old('village') }}">
                                @error('village')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label" style="color: #000000;">Phone Number</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}" 
                                       placeholder="0700000000">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label" style="color: #000000;">Email Address</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Parent/Guardian Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-account-supervisor me-2"></i>Parent/Guardian Information
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="parent_name" class="form-label" style="color: #000000;">
                                    Parent/Guardian Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('parent_name') is-invalid @enderror" 
                                       id="parent_name" 
                                       name="parent_name" 
                                       value="{{ old('parent_name') }}" 
                                       required>
                                @error('parent_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="parent_phone" class="form-label" style="color: #000000;">
                                    Parent Phone <span class="text-danger">*</span>
                                </label>
                                <input type="tel" 
                                       class="form-control @error('parent_phone') is-invalid @enderror" 
                                       id="parent_phone" 
                                       name="parent_phone" 
                                       value="{{ old('parent_phone') }}" 
                                       required>
                                @error('parent_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="parent_email" class="form-label" style="color: #000000;">Parent Email</label>
                                <input type="email" 
                                       class="form-control @error('parent_email') is-invalid @enderror" 
                                       id="parent_email" 
                                       name="parent_email" 
                                       value="{{ old('parent_email') }}">
                                @error('parent_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="parent_occupation" class="form-label" style="color: #000000;">Parent Occupation</label>
                                <input type="text" 
                                       class="form-control @error('parent_occupation') is-invalid @enderror" 
                                       id="parent_occupation" 
                                       name="parent_occupation" 
                                       value="{{ old('parent_occupation') }}">
                                @error('parent_occupation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-9 mb-3">
                                <label for="parent_address" class="form-label" style="color: #000000;">Parent Address</label>
                                <input type="text" 
                                       class="form-control @error('parent_address') is-invalid @enderror" 
                                       id="parent_address" 
                                       name="parent_address" 
                                       value="{{ old('parent_address') }}">
                                @error('parent_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="relationship" class="form-label" style="color: #000000;">Relationship</label>
                                <select class="form-select @error('relationship') is-invalid @enderror" 
                                        id="relationship" 
                                        name="relationship">
                                    <option value="">Select</option>
                                    <option value="Father" {{ old('relationship') == 'Father' ? 'selected' : '' }}>Father</option>
                                    <option value="Mother" {{ old('relationship') == 'Mother' ? 'selected' : '' }}>Mother</option>
                                    <option value="Guardian" {{ old('relationship') == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                                    <option value="Other" {{ old('relationship') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-phone-alert me-2"></i>Emergency Contact
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact_name" class="form-label" style="color: #000000;">Emergency Contact Name</label>
                                <input type="text" 
                                       class="form-control @error('emergency_contact_name') is-invalid @enderror" 
                                       id="emergency_contact_name" 
                                       name="emergency_contact_name" 
                                       value="{{ old('emergency_contact_name') }}">
                                @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact_phone" class="form-label" style="color: #000000;">Emergency Contact Phone</label>
                                <input type="tel" 
                                       class="form-control @error('emergency_contact_phone') is-invalid @enderror" 
                                       id="emergency_contact_phone" 
                                       name="emergency_contact_phone" 
                                       value="{{ old('emergency_contact_phone') }}">
                                @error('emergency_contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-school me-2"></i>Academic Information
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="class_id" class="form-label" style="color: #000000;">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('class_id') is-invalid @enderror" 
                                        id="class_id" 
                                        name="class_id" 
                                        required>
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', request('class_id')) == $class->id ? 'selected' : '' }}>
                                            {{ $class->class_name }} ({{ $class->current_enrollment }}/{{ $class->capacity }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="admission_number" class="form-label" style="color: #000000;">Admission Number</label>
                                <input type="text" 
                                       class="form-control @error('admission_number') is-invalid @enderror" 
                                       id="admission_number" 
                                       name="admission_number" 
                                       value="{{ old('admission_number') }}">
                                @error('admission_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="admission_date" class="form-label" style="color: #000000;">Admission Date</label>
                                <input type="date" 
                                       class="form-control @error('admission_date') is-invalid @enderror" 
                                       id="admission_date" 
                                       name="admission_date" 
                                       value="{{ old('admission_date', date('Y-m-d')) }}">
                                @error('admission_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="previous_school" class="form-label" style="color: #000000;">Previous School</label>
                                <input type="text" 
                                       class="form-control @error('previous_school') is-invalid @enderror" 
                                       id="previous_school" 
                                       name="previous_school" 
                                       value="{{ old('previous_school') }}">
                                @error('previous_school')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="academic_year" class="form-label" style="color: #000000;">Academic Year</label>
                                <input type="text" 
                                       class="form-control @error('academic_year') is-invalid @enderror" 
                                       id="academic_year" 
                                       name="academic_year" 
                                       value="{{ old('academic_year', date('Y')) }}">
                                @error('academic_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="boarding_status" class="form-label" style="color: #000000;">Boarding Status</label>
                                <select class="form-select @error('boarding_status') is-invalid @enderror" 
                                        id="boarding_status" 
                                        name="boarding_status">
                                    <option value="Day" {{ old('boarding_status') == 'Day' ? 'selected' : '' }}>Day Student</option>
                                    <option value="Boarding" {{ old('boarding_status') == 'Boarding' ? 'selected' : '' }}>Boarding</option>
                                </select>
                                @error('boarding_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Medical Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-medical-bag me-2"></i>Medical Information
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="blood_group" class="form-label" style="color: #000000;">Blood Group</label>
                                <select class="form-select @error('blood_group') is-invalid @enderror" 
                                        id="blood_group" 
                                        name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+" {{ old('blood_group') == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_group') == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_group') == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_group') == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_group') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_group') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_group') == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_group') == 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                                @error('blood_group')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="allergies" class="form-label" style="color: #000000;">Allergies</label>
                                <input type="text" 
                                       class="form-control @error('allergies') is-invalid @enderror" 
                                       id="allergies" 
                                       name="allergies" 
                                       value="{{ old('allergies') }}" 
                                       placeholder="e.g., Peanuts, Penicillin">
                                @error('allergies')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="medical_conditions" class="form-label" style="color: #000000;">Medical Conditions</label>
                                <input type="text" 
                                       class="form-control @error('medical_conditions') is-invalid @enderror" 
                                       id="medical_conditions" 
                                       name="medical_conditions" 
                                       value="{{ old('medical_conditions') }}" 
                                       placeholder="e.g., Asthma, Diabetes">
                                @error('medical_conditions')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-check-circle me-2"></i>Status
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label" style="color: #000000;">Student Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="transferred" {{ old('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                                    <option value="graduated" {{ old('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                                    <option value="expelled" {{ old('status') == 'expelled' ? 'selected' : '' }}>Expelled</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="mdi mdi-content-save me-1"></i>Add Student
                            </button>
                            <a href="{{ route('school.students.index') }}" class="btn btn-light btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
