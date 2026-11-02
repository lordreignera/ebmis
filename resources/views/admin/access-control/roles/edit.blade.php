@extends('layouts.admin')

@section('title', 'Edit Role')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h2 class="h4 mb-1 text-primary">
                                <i class="mdi mdi-pencil me-2"></i>Edit Role: {{ $role->name }}
                            </h2>
                            <p class="text-muted mb-0">Update role details or manage its permissions by module.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.permissions.index', ['role' => $role->id]) }}" class="btn btn-primary">
                                <i class="mdi mdi-key-chain me-1"></i>Manage Permissions
                            </a>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Back to Roles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                <i class="mdi mdi-shield-account me-1"></i>Role Name
                            </label>
                            <input
                                type="text"
                                class="form-control form-control-lg @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name', $role->name) }}"
                                required
                                {{ $role->name === 'Super Administrator' ? 'readonly' : '' }}
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($role->name === 'Super Administrator')
                                <small class="text-warning"><i class="mdi mdi-lock me-1"></i>This protected role name cannot be changed.</small>
                            @else
                                <small class="text-muted">Use a clear name that describes what this role does.</small>
                            @endif
                        </div>

                        <div class="role-permission-callout p-4 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="role-creation-step-icon"><i class="mdi mdi-table-key"></i></div>
                                <div>
                                    <h5 class="mb-1">Permissions are managed in the matrix</h5>
                                    <p class="text-muted mb-3">
                                        Choose the modules and permissions this role can use. You can select a whole module at once or choose individual permissions.
                                    </p>
                                    <a href="{{ route('admin.permissions.index', ['role' => $role->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-open-in-new me-1"></i>Open Permission Manager
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-close me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check me-1"></i>Save Role Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($role->name !== 'Super Administrator')
                <div class="card border-danger mt-4">
                    <div class="card-body">
                        <h6 class="card-title text-danger"><i class="mdi mdi-alert me-2"></i>Danger Zone</h6>
                        <hr>
                        <p class="small text-muted mb-3">Deleting a role removes its assignments and cannot be undone.</p>
                        <form action="{{ route('admin.roles.delete', $role->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this role? This action cannot be undone.')">
                                <i class="mdi mdi-delete me-1"></i>Delete Role
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title"><i class="mdi mdi-information me-2"></i>Role Information</h6>
                    <hr>
                    <div class="mb-3"><small class="text-muted">Role Name</small><p class="mb-0 fw-bold">{{ $role->name }}</p></div>
                    <div class="mb-3"><small class="text-muted">Users with this role</small><p class="mb-0 fw-bold">{{ $role->users_count }}</p></div>
                    <div class="mb-3"><small class="text-muted">Assigned permissions</small><p class="mb-0 fw-bold">{{ $role->permissions->count() }}</p></div>
                    <div><small class="text-muted">Created</small><p class="mb-0">{{ optional($role->created_at)->format('M d, Y') }}</p></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0"><i class="mdi mdi-key-variant me-2"></i>Current Permissions</h6>
                        <span class="access-control-badge blue">{{ $role->permissions->count() }}</span>
                    </div>
                    @if($role->permissions->isEmpty())
                        <p class="text-muted small mb-0">No permissions assigned yet. Open the matrix to add access.</p>
                    @else
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($role->permissions->sortBy('name')->take(12) as $permission)
                                <span class="access-control-badge dark">{{ $permission->name }}</span>
                            @endforeach
                            @if($role->permissions->count() > 12)
                                <span class="access-control-badge slate">+{{ $role->permissions->count() - 12 }} more</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.role-permission-callout {
    background: #f8fafc;
    border: 1px solid #dbeafe;
    border-radius: 0.75rem;
}

.role-creation-step-icon {
    align-items: center;
    background: #dbeafe;
    border-radius: 0.65rem;
    color: #2563eb;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 1.35rem;
    height: 44px;
    justify-content: center;
    width: 44px;
}
</style>
@endpush
