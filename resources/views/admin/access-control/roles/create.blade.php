@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h2 class="h4 mb-1 text-primary">
                                <i class="mdi mdi-shield-plus me-2"></i>Create New Role
                            </h2>
                            <p class="text-muted mb-0">Create the role first, then assign access from the permission manager.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-primary">
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

    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('admin.roles.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                <i class="mdi mdi-shield-account me-1"></i>Role Name
                            </label>
                            <input
                                type="text"
                                class="form-control form-control-lg @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="e.g. Branch Manager, Cashier, Teller"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Use a clear name that describes what this role does.</small>
                        </div>

                        <div class="role-creation-next-step p-4 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="role-creation-step-icon"><i class="mdi mdi-table-key"></i></div>
                                <div>
                                    <h5 class="mb-1">Assign permissions after creation</h5>
                                    <p class="text-muted mb-3">
                                        After saving this role, open Manage Permissions, choose this role, and select the access it needs by module.
                                    </p>
                                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-open-in-new me-1"></i>Preview Permission Manager
                                    </a>
                                </div>
                            </div>
                        </div>

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
    </div>
</div>
@endsection

@push('styles')
<style>
.role-creation-next-step {
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
