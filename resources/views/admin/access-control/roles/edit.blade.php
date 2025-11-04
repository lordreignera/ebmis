@extends('layouts.admin')

@section('title', 'Edit Role')

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
                                <i class="mdi mdi-pencil me-2"></i>Edit Role: {{ $role->name }}
                            </h2>
                            <p class="text-muted mb-0">Update role details and permissions</p>
                        </div>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Roles
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Role Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                <i class="mdi mdi-shield-account me-1"></i>Role Name
                            </label>
                            <input 
                                type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name', $role->name) }}" 
                                placeholder="e.g., Branch Manager, Cashier, Teller"
                                required
                                {{ $role->name === 'Super Administrator' ? 'readonly' : '' }}
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($role->name === 'Super Administrator')
                                <small class="text-warning">
                                    <i class="mdi mdi-alert me-1"></i>Super Administrator role name cannot be changed
                                </small>
                            @else
                                <small class="text-muted">Enter a descriptive name for this role</small>
                            @endif
                        </div>

                        <!-- Permissions Section -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="mdi mdi-key-variant me-1"></i>Assign Permissions
                            </label>
                            <small class="text-muted d-block mb-3">Select the permissions this role should have</small>
                            
                            @if($permissions->isEmpty())
                                <div class="alert alert-warning">
                                    <i class="mdi mdi-alert me-2"></i>No permissions available. Please create permissions first.
                                </div>
                            @else
                                <!-- Select All/None -->
                                <div class="mb-3 p-3 bg-light rounded">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                        <label class="form-check-label fw-bold" for="selectAll">
                                            Select All Permissions
                                        </label>
                                    </div>
                                </div>

                                <!-- Permissions by Category -->
                                @foreach($permissions as $category => $categoryPermissions)
                                    <div class="card mb-3">
                                        <div class="card-header bg-gradient-primary text-white">
                                            <h6 class="mb-0">
                                                <i class="mdi mdi-folder-key me-2"></i>{{ $category }}
                                                <span class="badge bg-light text-dark ms-2">{{ $categoryPermissions->count() }}</span>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach($categoryPermissions as $permission)
                                                    <div class="col-md-6 col-lg-4 mb-2">
                                                        <div class="form-check">
                                                            <input 
                                                                class="form-check-input permission-checkbox" 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                value="{{ $permission->name }}" 
                                                                id="permission_{{ $permission->id }}"
                                                                {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                                            >
                                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                {{ ucwords(str_replace(['-', '_'], ' ', $permission->name)) }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-close me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check me-1"></i>Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="mdi mdi-information me-2"></i>Role Information
                    </h6>
                    <hr>
                    
                    <div class="mb-3">
                        <small class="text-muted">Role Name</small>
                        <p class="mb-0 fw-bold">{{ $role->name }}</p>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Users with this Role</small>
                        <p class="mb-0 fw-bold">{{ $role->users()->count() }}</p>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Current Permissions</small>
                        <p class="mb-0 fw-bold">{{ $role->permissions->count() }}</p>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Created</small>
                        <p class="mb-0">{{ $role->created_at->format('M d, Y') }}</p>
                    </div>

                    @if($role->updated_at != $role->created_at)
                    <div class="mb-3">
                        <small class="text-muted">Last Updated</small>
                        <p class="mb-0">{{ $role->updated_at->format('M d, Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Permission Summary -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="mdi mdi-chart-box me-2"></i>Permission Summary
                    </h6>
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Total Permissions:</span>
                        <span class="badge bg-primary">{{ $permissions->flatten()->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Selected:</span>
                        <span class="badge bg-success" id="selectedCount">{{ $role->permissions->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Categories:</span>
                        <span class="badge bg-info">{{ $permissions->count() }}</span>
                    </div>
                </div>
            </div>

            @if($role->name !== 'Super Administrator')
            <!-- Danger Zone -->
            <div class="card border-danger mt-3">
                <div class="card-body">
                    <h6 class="card-title text-danger">
                        <i class="mdi mdi-alert me-2"></i>Danger Zone
                    </h6>
                    <hr>
                    <p class="small text-muted mb-3">Once you delete a role, there is no going back. Please be certain.</p>
                    <form action="{{ route('admin.roles.delete', $role->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button 
                            type="submit" 
                            class="btn btn-danger btn-sm w-100"
                            onclick="return confirm('Are you sure you want to delete this role? This action cannot be undone.')">
                            <i class="mdi mdi-delete me-1"></i>Delete Role
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.form-check-input:checked {
    background-color: #4fd1c7;
    border-color: #4fd1c7;
}

.card {
    transition: all 0.3s ease;
}

.form-check-label {
    cursor: pointer;
    user-select: none;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');

    // Update selected count
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.permission-checkbox:checked').length;
        selectedCountSpan.textContent = checkedCount;
        
        // Update select all checkbox state
        const allChecked = checkedCount === permissionCheckboxes.length;
        const someChecked = checkedCount > 0;
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
    }

    // Select/Deselect all
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    // Update count on individual checkbox change
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Initial count
    updateSelectedCount();
});
</script>
@endpush
