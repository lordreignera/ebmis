@extends('layouts.admin')

@section('title', 'Add New Staff')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-account-tie-voice me-2"></i>Add New Staff Member
                    </h2>
                    <p class="text-muted mb-0">Enter staff member information</p>
                </div>
                <div>
                    <a href="{{ route('school.staff.index') }}" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Staff
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
                    <form action="{{ route('school.staff.store') }}" method="POST" enctype="multipart/form-data">
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
                                <label for="national_id" class="form-label" style="color: #000000;">National ID</label>
                                <input type="text" 
                                       class="form-control @error('national_id') is-invalid @enderror" 
                                       id="national_id" 
                                       name="national_id" 
                                       value="{{ old('national_id') }}">
                                @error('national_id')
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

                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label" style="color: #000000;">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" 
                                       class="form-control @error('phone_number') is-invalid @enderror" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       value="{{ old('phone_number') }}" 
                                       placeholder="0700000000"
                                       required>
                                @error('phone_number')
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

                            <div class="col-md-6 mb-3">
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
                        </div>

                        <!-- Next of Kin -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-account-supervisor me-2"></i>Next of Kin Information
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="next_of_kin_name" class="form-label" style="color: #000000;">Name</label>
                                <input type="text" 
                                       class="form-control @error('next_of_kin_name') is-invalid @enderror" 
                                       id="next_of_kin_name" 
                                       name="next_of_kin_name" 
                                       value="{{ old('next_of_kin_name') }}">
                                @error('next_of_kin_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="next_of_kin_phone" class="form-label" style="color: #000000;">Phone</label>
                                <input type="tel" 
                                       class="form-control @error('next_of_kin_phone') is-invalid @enderror" 
                                       id="next_of_kin_phone" 
                                       name="next_of_kin_phone" 
                                       value="{{ old('next_of_kin_phone') }}">
                                @error('next_of_kin_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="next_of_kin_relationship" class="form-label" style="color: #000000;">Relationship</label>
                                <input type="text" 
                                       class="form-control @error('next_of_kin_relationship') is-invalid @enderror" 
                                       id="next_of_kin_relationship" 
                                       name="next_of_kin_relationship" 
                                       value="{{ old('next_of_kin_relationship') }}" 
                                       placeholder="e.g., Spouse, Brother, Sister">
                                @error('next_of_kin_relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Employment Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-briefcase me-2"></i>Employment Information
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="staff_type" class="form-label" style="color: #000000;">
                                    Staff Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('staff_type') is-invalid @enderror" 
                                        id="staff_type" 
                                        name="staff_type" 
                                        required>
                                    <option value="">Select Type</option>
                                    <option value="Teaching" {{ old('staff_type') == 'Teaching' ? 'selected' : '' }}>Teaching Staff</option>
                                    <option value="Non-Teaching" {{ old('staff_type') == 'Non-Teaching' ? 'selected' : '' }}>Non-Teaching Staff</option>
                                </select>
                                @error('staff_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="position" class="form-label" style="color: #000000;">
                                    Position <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('position') is-invalid @enderror" 
                                       id="position" 
                                       name="position" 
                                       value="{{ old('position') }}" 
                                       placeholder="e.g., Mathematics Teacher, Accountant"
                                       required>
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="department" class="form-label" style="color: #000000;">Department</label>
                                <input type="text" 
                                       class="form-control @error('department') is-invalid @enderror" 
                                       id="department" 
                                       name="department" 
                                       value="{{ old('department') }}" 
                                       placeholder="e.g., Sciences, Administration">
                                @error('department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="subjects_taught" class="form-label" style="color: #000000;">Subjects Taught</label>
                                <input type="text" 
                                       class="form-control @error('subjects_taught') is-invalid @enderror" 
                                       id="subjects_taught" 
                                       name="subjects_taught" 
                                       value="{{ old('subjects_taught') }}" 
                                       placeholder="e.g., Mathematics, Physics (for teaching staff)">
                                @error('subjects_taught')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank for non-teaching staff</small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="date_joined" class="form-label" style="color: #000000;">
                                    Date Joined <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control @error('date_joined') is-invalid @enderror" 
                                       id="date_joined" 
                                       name="date_joined" 
                                       value="{{ old('date_joined', date('Y-m-d')) }}" 
                                       required>
                                @error('date_joined')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="employee_number" class="form-label" style="color: #000000;">Employee Number</label>
                                <input type="text" 
                                       class="form-control @error('employee_number') is-invalid @enderror" 
                                       id="employee_number" 
                                       name="employee_number" 
                                       value="{{ old('employee_number') }}" 
                                       placeholder="Optional">
                                @error('employee_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="employment_type" class="form-label" style="color: #000000;">
                                    Employment Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('employment_type') is-invalid @enderror" 
                                        id="employment_type" 
                                        name="employment_type" 
                                        required>
                                    <option value="">Select Type</option>
                                    <option value="Full-Time" {{ old('employment_type') == 'Full-Time' ? 'selected' : '' }}>Full-Time</option>
                                    <option value="Part-Time" {{ old('employment_type') == 'Part-Time' ? 'selected' : '' }}>Part-Time</option>
                                    <option value="Contract" {{ old('employment_type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                                    <option value="Temporary" {{ old('employment_type') == 'Temporary' ? 'selected' : '' }}>Temporary</option>
                                </select>
                                @error('employment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Qualifications -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-school me-2"></i>Qualifications
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="highest_qualification" class="form-label" style="color: #000000;">Highest Qualification</label>
                                <select class="form-select @error('highest_qualification') is-invalid @enderror" 
                                        id="highest_qualification" 
                                        name="highest_qualification">
                                    <option value="">Select</option>
                                    <option value="PhD" {{ old('highest_qualification') == 'PhD' ? 'selected' : '' }}>PhD/Doctorate</option>
                                    <option value="Masters" {{ old('highest_qualification') == 'Masters' ? 'selected' : '' }}>Masters Degree</option>
                                    <option value="Bachelors" {{ old('highest_qualification') == 'Bachelors' ? 'selected' : '' }}>Bachelors Degree</option>
                                    <option value="Diploma" {{ old('highest_qualification') == 'Diploma' ? 'selected' : '' }}>Diploma</option>
                                    <option value="Certificate" {{ old('highest_qualification') == 'Certificate' ? 'selected' : '' }}>Certificate</option>
                                    <option value="A-Level" {{ old('highest_qualification') == 'A-Level' ? 'selected' : '' }}>A-Level</option>
                                    <option value="O-Level" {{ old('highest_qualification') == 'O-Level' ? 'selected' : '' }}>O-Level</option>
                                </select>
                                @error('highest_qualification')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="institution_attended" class="form-label" style="color: #000000;">Institution Attended</label>
                                <input type="text" 
                                       class="form-control @error('institution_attended') is-invalid @enderror" 
                                       id="institution_attended" 
                                       name="institution_attended" 
                                       value="{{ old('institution_attended') }}" 
                                       placeholder="University/College name">
                                @error('institution_attended')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="year_of_graduation" class="form-label" style="color: #000000;">Year of Graduation</label>
                                <input type="number" 
                                       class="form-control @error('year_of_graduation') is-invalid @enderror" 
                                       id="year_of_graduation" 
                                       name="year_of_graduation" 
                                       value="{{ old('year_of_graduation') }}" 
                                       min="1950" 
                                       max="{{ date('Y') }}" 
                                       placeholder="{{ date('Y') }}">
                                @error('year_of_graduation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="certifications" class="form-label" style="color: #000000;">Additional Certifications</label>
                                <textarea class="form-control @error('certifications') is-invalid @enderror" 
                                          id="certifications" 
                                          name="certifications" 
                                          rows="2" 
                                          placeholder="List any additional certifications, courses, or training">{{ old('certifications') }}</textarea>
                                @error('certifications')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Salary Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-cash-multiple me-2"></i>Salary Information
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="basic_salary" class="form-label" style="color: #000000;">
                                    Basic Salary (UGX) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('basic_salary') is-invalid @enderror" 
                                       id="basic_salary" 
                                       name="basic_salary" 
                                       value="{{ old('basic_salary') }}" 
                                       step="0.01" 
                                       min="0" 
                                       placeholder="500000"
                                       required>
                                @error('basic_salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="allowances" class="form-label" style="color: #000000;">Allowances (UGX)</label>
                                <input type="number" 
                                       class="form-control @error('allowances') is-invalid @enderror" 
                                       id="allowances" 
                                       name="allowances" 
                                       value="{{ old('allowances', 0) }}" 
                                       step="0.01" 
                                       min="0" 
                                       placeholder="100000">
                                @error('allowances')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Housing, transport, etc.</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="payment_frequency" class="form-label" style="color: #000000;">
                                    Payment Frequency <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('payment_frequency') is-invalid @enderror" 
                                        id="payment_frequency" 
                                        name="payment_frequency" 
                                        required>
                                    <option value="Monthly" {{ old('payment_frequency', 'Monthly') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="Weekly" {{ old('payment_frequency') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="Daily" {{ old('payment_frequency') == 'Daily' ? 'selected' : '' }}>Daily</option>
                                </select>
                                @error('payment_frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bank_name" class="form-label" style="color: #000000;">Bank Name</label>
                                <input type="text" 
                                       class="form-control @error('bank_name') is-invalid @enderror" 
                                       id="bank_name" 
                                       name="bank_name" 
                                       value="{{ old('bank_name') }}" 
                                       placeholder="e.g., Stanbic Bank">
                                @error('bank_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="bank_account_number" class="form-label" style="color: #000000;">Bank Account Number</label>
                                <input type="text" 
                                       class="form-control @error('bank_account_number') is-invalid @enderror" 
                                       id="bank_account_number" 
                                       name="bank_account_number" 
                                       value="{{ old('bank_account_number') }}" 
                                       placeholder="Account number">
                                @error('bank_account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="mobile_money_number" class="form-label" style="color: #000000;">Mobile Money Number</label>
                                <input type="tel" 
                                       class="form-control @error('mobile_money_number') is-invalid @enderror" 
                                       id="mobile_money_number" 
                                       name="mobile_money_number" 
                                       value="{{ old('mobile_money_number') }}" 
                                       placeholder="0700000000">
                                @error('mobile_money_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">For mobile money payments</small>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3" style="color: #000000;">
                                    <i class="mdi mdi-file-document me-2"></i>Documents
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="cv" class="form-label" style="color: #000000;">CV/Resume</label>
                                <input type="file" 
                                       class="form-control @error('cv') is-invalid @enderror" 
                                       id="cv" 
                                       name="cv" 
                                       accept=".pdf,.doc,.docx">
                                @error('cv')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">PDF, DOC, DOCX (Max: 5MB)</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="certificate" class="form-label" style="color: #000000;">Certificates</label>
                                <input type="file" 
                                       class="form-control @error('certificate') is-invalid @enderror" 
                                       id="certificate" 
                                       name="certificate" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('certificate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">PDF or Image (Max: 5MB)</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="id_photo" class="form-label" style="color: #000000;">ID Photo</label>
                                <input type="file" 
                                       class="form-control @error('id_photo') is-invalid @enderror" 
                                       id="id_photo" 
                                       name="id_photo" 
                                       accept="image/*">
                                @error('id_photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Image file (Max: 2MB)</small>
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
                                <label for="status" class="form-label" style="color: #000000;">Employment Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="on_leave" {{ old('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                    <option value="resigned" {{ old('status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="mdi mdi-content-save me-1"></i>Add Staff Member
                            </button>
                            <a href="{{ route('school.staff.index') }}" class="btn btn-light btn-lg">
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
