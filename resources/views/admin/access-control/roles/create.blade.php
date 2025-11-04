@extends('layouts.admin')

@section('title', 'Create Role')

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
                                <i class="mdi mdi-shield-plus me-2"></i>Create New Role
                            </h2>
                            <p class="text-muted mb-0">Define a new role and assign permissions</p>
                        </div>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Roles
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Role Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.roles.store') }}" method="POST">
                        @csrf
                        
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
                                value="{{ old('name') }}" 
                                placeholder="e.g., Branch Manager, Cashier, Teller"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Enter a descriptive name for this role</small>
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
                                                                {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}
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
                                <i class="mdi mdi-check me-1"></i>Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="mdi mdi-information me-2"></i>Role Guidelines
                    </h6>
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="text-primary mb-2">
                            <i class="mdi mdi-lightbulb-on me-1"></i>Best Practices
                        </h6>
                        <ul class="small text-muted ps-3">
                            <li>Use clear, descriptive role names</li>
                            <li>Assign minimum required permissions</li>
                            <li>Review permissions regularly</li>
                            <li>Document role purposes</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-primary mb-2">
                            <i class="mdi mdi-account-group me-1"></i>Common Roles
                        </h6>
                        <ul class="small text-muted ps-3">
                            <li><strong>Branch Manager:</strong> Full branch access</li>
                            <li><strong>Cashier:</strong> Transaction processing</li>
                            <li><strong>Teller:</strong> Customer service</li>
                            <li><strong>Accountant:</strong> Financial reports</li>
                            <li><strong>Loan Officer:</strong> Loan management</li>
                        </ul>
                    </div>

                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="mdi mdi-information me-1"></i>
                            <strong>Note:</strong> You can edit role permissions anytime after creation.
                        </small>
                    </div>
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
                        <span class="badge bg-success" id="selectedCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Categories:</span>
                        <span class="badge bg-info">{{ $permissions->count() }}</span>
                    </div>
                </div>
            </div>
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
