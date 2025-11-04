@extends('layouts.admin')

@section('title', 'Create New User')

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
                                <i class="mdi mdi-account-plus me-2"></i>Create New User
                            </h2>
                            <p class="text-muted mb-0">Add a new system user with specific roles and permissions</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Creation Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>User Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Basic Information</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="designation" class="form-label">Job Title/Designation</label>
                                <input type="text" class="form-control @error('designation') is-invalid @enderror" 
                                       id="designation" name="designation" value="{{ old('designation') }}" 
                                       placeholder="e.g., Branch Manager, Cashier, Loan Officer">
                                @error('designation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" name="address" rows="2">{{ old('address') }}</textarea>
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
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="user_type" class="form-label">User Type *</label>
                                <select class="form-control @error('user_type') is-invalid @enderror" 
                                        id="user_type" name="user_type" required>
                                    <option value="">Select User Type</option>
                                    <option value="super_admin" {{ old('user_type') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                    <option value="branch" {{ old('user_type') === 'branch' ? 'selected' : '' }}>Branch User</option>
                                    <option value="school" {{ old('user_type') === 'school' ? 'selected' : '' }}>School User</option>
                                </select>
                                @error('user_type')
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
                                                   {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
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
                                <i class="fas fa-save me-2"></i>Create User
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
            
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>Common Roles</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>Branch Manager:</strong> Full branch access</li>
                        <li class="mb-2"><strong>Loan Officer:</strong> Loan management</li>
                        <li class="mb-2"><strong>Cashier:</strong> Transaction processing</li>
                        <li class="mb-2"><strong>School Administrator:</strong> Full school access</li>
                        <li class="mb-2"><strong>School Teacher:</strong> Limited school access</li>
                        <li class="mb-0"><strong>Regional HR:</strong> Multi-branch management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');

    function validatePassword() {
        if (password.value != confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords don't match");
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
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