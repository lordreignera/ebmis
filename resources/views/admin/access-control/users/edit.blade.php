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
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" minlength="8">
                                <small class="text-muted">Leave blank to keep current password</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation">
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
                            <div class="col-md-6 mb-3" id="branch_field" style="display: {{ old('user_type', $user->user_type) === 'branch' ? 'block' : 'none' }};">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger" id="branch_required">*</span></label>
                                <select class="form-control @error('branch_id') is-invalid @enderror" 
                                        id="branch_id" name="branch_id">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
    const branchSelect = document.getElementById('branch_id');

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
            branchSelect.required = true;
        } else {
            branchField.style.display = 'none';
            branchSelect.required = false;
            branchSelect.value = '';
        }
    }

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
</style>
@endpush
