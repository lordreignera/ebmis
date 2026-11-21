@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h4 mb-1 text-primary">
                                <i class="mdi mdi-account-edit me-2"></i>Edit User: {{ $user->name }}
                            </h2>
                            <p class="text-muted mb-0">Update user information, roles, and permissions</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>User Information</h6>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Basic Information</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="designation" class="form-label">Job Title/Designation</label>
                                <input type="text" class="form-control @error('designation') is-invalid @enderror" 
                                       id="designation" name="designation" value="{{ old('designation', $user->designation) }}" 
                                       placeholder="e.g., Branch Manager, Cashier, Loan Officer">
                                @error('designation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" name="address" rows="2">{{ old('address', $user->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Security Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Security & Access</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                           id="password" name="password" minlength="8">
                                    <button type="button" class="btn btn-outline-secondary password-visibility-toggle"
                                            data-password-target="password" aria-label="Show password" title="Show password">
                                        <i class="mdi mdi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Leave blank to keep current password</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation">
                                    <button type="button" class="btn btn-outline-secondary password-visibility-toggle"
                                            data-password-target="password_confirmation" aria-label="Show password confirmation" title="Show password confirmation">
                                        <i class="mdi mdi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="user_type" class="form-label">User Type *</label>
                                <select class="form-control @error('user_type') is-invalid @enderror" 
                                        id="user_type" name="user_type" required>
                                    <option value="">Select User Type</option>
                                    <option value="super_admin" {{ old('user_type', $user->user_type) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                    <option value="branch" {{ old('user_type', $user->user_type) === 'branch' ? 'selected' : '' }}>Branch User</option>
                                    <option value="school" {{ old('user_type', $user->user_type) === 'school' ? 'selected' : '' }}>School User</option>
                                </select>
                                @error('user_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @php($selectedBranchIds = collect(old('branch_ids', $user->branches->pluck('id')->all() ?: ($user->branch_id ? [$user->branch_id] : [])))->map(fn ($id) => (string) $id)->all())
                            <div class="col-md-6 mb-3" id="branch_field" style="display: {{ old('user_type', $user->user_type) === 'branch' ? 'block' : 'none' }};">
                                <label for="branch_picker_toggle" class="form-label">Branches <span class="text-danger" id="branch_required">*</span></label>
                                <div class="dropdown branch-picker">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center"
                                            id="branch_picker_toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                            aria-expanded="false">
                                        <span id="branch_selection_label">Select branch(es)</span>
                                    </button>
                                    <div class="dropdown-menu branch-picker-menu w-100 p-2" aria-labelledby="branch_picker_toggle">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                            <label class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="select_all_branches">
                                                <span class="form-check-label">Select all</span>
                                            </label>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" id="clear_branch_selection">Clear</button>
                                        </div>
                                        <div class="branch-options" role="group" aria-label="Branches">
                                            @foreach($branches as $branch)
                                                <label class="branch-option">
                                                    <input class="form-check-input branch-checkbox" type="checkbox" name="branch_ids[]"
                                                           value="{{ $branch->id }}" data-branch-name="{{ $branch->name }}"
                                                           {{ in_array((string) $branch->id, $selectedBranchIds, true) ? 'checked' : '' }}>
                                                    <span>{{ $branch->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Choose one or more branches, or use Select all.</small>
                                <div id="branch_validation_message" class="text-danger small mt-1 {{ $errors->has('branch_ids') ? '' : 'd-none' }}">
                                    @error('branch_ids'){{ $message }}@else Select at least one branch.@enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3" id="school_field" style="display: {{ old('user_type', $user->user_type) === 'school' ? 'block' : 'none' }};">
                                <label for="school_id" class="form-label">Approved School <span class="text-danger">*</span></label>
                                <select class="form-control @error('school_id') is-invalid @enderror"
                                        id="school_id" name="school_id">
                                    <option value="">Select Approved School</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ old('school_id', $user->school_id) == $school->id ? 'selected' : '' }}>
                                            {{ $school->school_name ?? $school->name ?? ('School #' . $school->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('school_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">School users can log in only when linked to an approved school.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Account Status *</label>
                                <select class="form-control @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="pending" {{ old('status', $user->status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="active" {{ old('status', $user->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $user->status ?? 'suspended') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="rejected" {{ old('status', $user->status ?? 'rejected') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Role Assignment -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Role Assignment</h6>
                                <div class="alert alert-info">
                                    <small><strong>Note:</strong> Select one or more roles to assign to this user. Roles determine what actions the user can perform.</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row">
                                    @foreach($roles as $role)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="roles[]" value="{{ $role->name }}" 
                                                   id="role_{{ $role->id }}"
                                                   {{ $user->hasRole($role->name) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                <strong>{{ $role->name }}</strong>
                                                <div class="text-muted small">{{ $role->permissions_count ?? $role->permissions->count() }} permissions</div>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update User
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>User ID:</strong> {{ $user->id }}
                    </div>
                    <div class="mb-3">
                        <strong>Created:</strong> {{ $user->created_at ? $user->created_at->format('M d, Y H:i') : 'N/A' }}
                    </div>
                    <div class="mb-3">
                        <strong>Last Updated:</strong> {{ $user->updated_at ? $user->updated_at->format('M d, Y H:i') : 'N/A' }}
                    </div>
                    <div class="mb-3">
                        <strong>Current Roles:</strong>
                        <div class="mt-1">
                            @forelse($user->roles as $role)
                                <span class="badge bg-info me-1">{{ $role->name }}</span>
                            @empty
                                <span class="text-muted">No roles assigned</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>User Types Guide</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-danger">Super Admin</h6>
                        <small class="text-muted">Full system access. Can manage all schools, branches, users, and system settings.</small>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-warning">Branch User</h6>
                        <small class="text-muted">Access to EBIMS modules. Includes Branch Managers, Loan Officers, Cashiers, and Regional HR.</small>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-info">School User</h6>
                        <small class="text-muted">Access to School Portal. Includes School Administrators, Teachers, and Accountants.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Password confirmation validation and branch field toggle
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    const userType = document.getElementById('user_type');
    const branchField = document.getElementById('branch_field');
    const branchCheckboxes = Array.from(document.querySelectorAll('.branch-checkbox'));
    const selectAllBranches = document.getElementById('select_all_branches');
    const clearBranchSelection = document.getElementById('clear_branch_selection');
    const branchSelectionLabel = document.getElementById('branch_selection_label');
    const branchValidationMessage = document.getElementById('branch_validation_message');
    const form = branchField.closest('form');
    const schoolField = document.getElementById('school_field');
    const schoolSelect = document.getElementById('school_id');

    document.querySelectorAll('[data-password-target]').forEach(function(toggle) {
        const input = document.getElementById(toggle.dataset.passwordTarget);
        const icon = toggle.querySelector('i');

        if (!input) {
            return;
        }

        toggle.addEventListener('click', function() {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            toggle.setAttribute('title', isVisible ? 'Show password' : 'Hide password');
            toggle.setAttribute('aria-pressed', String(!isVisible));

            if (icon) {
                icon.classList.toggle('mdi-eye', isVisible);
                icon.classList.toggle('mdi-eye-off', !isVisible);
            }
        });
    });

    function validatePassword() {
        if (password.value && password.value != confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords don't match");
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    // Show/hide branch field based on user type
    function toggleBranchField() {
        if (userType.value === 'branch') {
            branchField.style.display = 'block';
            schoolField.style.display = 'none';
            schoolSelect.required = false;
            schoolSelect.value = '';
        } else if (userType.value === 'school') {
            branchField.style.display = 'none';
            branchCheckboxes.forEach(function(checkbox) { checkbox.checked = false; });
            schoolField.style.display = 'block';
            schoolSelect.required = true;
        } else {
            branchField.style.display = 'none';
            branchCheckboxes.forEach(function(checkbox) { checkbox.checked = false; });
            schoolField.style.display = 'none';
            schoolSelect.required = false;
            schoolSelect.value = '';
        }
        syncBranchPicker();
    }

    function syncBranchPicker() {
        const selected = branchCheckboxes.filter(function(checkbox) { return checkbox.checked; });
        const total = branchCheckboxes.length;

        selectAllBranches.checked = total > 0 && selected.length === total;
        if (selected.length === 0) {
            branchSelectionLabel.textContent = 'Select branch(es)';
        } else if (selected.length === total) {
            branchSelectionLabel.textContent = 'All branches selected';
        } else if (selected.length === 1) {
            branchSelectionLabel.textContent = selected[0].dataset.branchName;
        } else {
            branchSelectionLabel.textContent = selected.length + ' branches selected';
        }
    }

    selectAllBranches.addEventListener('change', function() {
        branchCheckboxes.forEach(function(checkbox) { checkbox.checked = selectAllBranches.checked; });
        syncBranchPicker();
    });

    clearBranchSelection.addEventListener('click', function() {
        branchCheckboxes.forEach(function(checkbox) { checkbox.checked = false; });
        syncBranchPicker();
    });

    branchCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', syncBranchPicker);
    });

    form.addEventListener('submit', function(event) {
        const valid = userType.value !== 'branch' || branchCheckboxes.some(function(checkbox) { return checkbox.checked; });
        branchValidationMessage.classList.toggle('d-none', valid);
        if (!valid) {
            event.preventDefault();
        }
    });

    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
    userType.addEventListener('change', toggleBranchField);
    
    // Initialize on page load
    toggleBranchField();
});
</script>
@endpush

@push('styles')
<style>
/* Make all text darker/black for better readability */
.card-body h2,
.card-body h4,
.card-body h5,
.card-body h6,
.card-body p,
.card-body label,
.card-title,
.text-muted,
.small,
ul li {
    color: #000000 !important;
}

.form-label {
    color: #000000 !important;
    font-weight: 600 !important;
}

.alert {
    color: #000000 !important;
}

.badge {
    font-weight: 500;
}

.branch-picker-menu {
    max-height: 18rem;
    overflow-y: auto;
}

.branch-options {
    max-height: 12rem;
    overflow-y: auto;
}

.branch-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
}

.branch-option:hover {
    background: #f8f9fa;
}
</style>
@endpush
