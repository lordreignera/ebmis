@extends('layouts.admin')

@section('title', 'Manage Role Permissions')

@section('content')
<div class="container-fluid permission-manager-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="h4 mb-1 text-primary">
                                <i class="mdi mdi-shield-key me-2"></i>Manage Role Permissions
                            </h2>
                            <p class="text-muted mb-0">Choose a role, select its access, and save.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-shield-account me-1"></i>Manage Roles
                            </a>
                            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-1"></i>Add Role
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($roles->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="mdi mdi-shield-account mdi-48px text-muted mb-3"></i>
                <h5 class="text-muted">Create a role first</h5>
                <p class="text-muted mb-4">Permissions can be assigned after the role is created.</p>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                    <i class="mdi mdi-plus me-1"></i>Create Role
                </a>
            </div>
        </div>
    @else
        @php($activeRoleIsAdministrator = $administratorRole && $activeRole && $activeRole->id === $administratorRole->id)

        <div class="card border-0 shadow-sm mb-4 role-picker-card">
            <div class="card-body">
                <div class="row align-items-end g-3">
                    <div class="col-lg-7">
                        <label for="rolePicker" class="form-label fw-bold mb-2">
                            <i class="mdi mdi-account-key me-1"></i>Role to configure
                        </label>
                        <select id="rolePicker" class="form-select form-select-lg">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected($activeRoleId == $role->id)>
                                    {{ $role->name }} ({{ $role->permissions->count() }} permissions)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <div class="role-summary-box">
                            <div class="text-muted small">Currently editing</div>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <strong>{{ $activeRole->name }}</strong>
                                <span class="access-control-badge blue">{{ $activeRole->permissions->count() }} assigned</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($activeRoleIsAdministrator)
            <div class="alert alert-info d-flex align-items-center gap-2" role="status">
                <i class="mdi mdi-lock-check fs-5"></i>
                <span><strong>{{ $activeRole->name }}</strong> always receives all permissions and cannot be edited.</span>
            </div>
        @else
            <div class="alert alert-primary d-flex align-items-center gap-2" role="status">
                <i class="mdi mdi-lightbulb-on-outline fs-5"></i>
                <span>Permissions are grouped by module. Use the module buttons to select everything in one group.</span>
            </div>
        @endif

        <form method="GET" action="{{ route('admin.permissions.index') }}" class="card border-0 shadow-sm mb-4" id="permissionSearchForm">
            <div class="card-body">
                <input type="hidden" name="role" value="{{ $activeRoleId }}">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="mdi mdi-magnify"></i></span>
                            <input type="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Find a permission...">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <a href="{{ route('admin.permissions.index', ['role' => $activeRoleId]) }}" class="btn btn-outline-secondary w-100" data-permission-navigation>Clear</a>
                    </div>
                    <div class="col-lg-2 text-lg-end">
                        <span class="text-muted small">{{ $permissionsPage->firstItem() ?? 0 }}–{{ $permissionsPage->lastItem() ?? 0 }} of {{ $permissionsPage->total() }}</span>
                    </div>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.permissions.role.store', array_filter(['page' => request('page'), 'search' => request('search')])) }}" id="rolePermissionForm">
            @csrf
            <input type="hidden" name="role_id" value="{{ $activeRoleId }}">
            <input type="hidden" name="page" value="{{ request('page') }}">
            <input type="hidden" name="search" value="{{ request('search') }}">
            @foreach($flatPermissions as $permission)
                <input type="hidden" name="permission_ids[]" value="{{ $permission->id }}">
            @endforeach

            <div class="card border-0 shadow-sm permission-checklist-card">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                    <div>
                        <h5 class="mb-1"><i class="mdi mdi-format-list-checks me-1 text-primary"></i>All Permissions</h5>
                        <div class="text-muted small">{{ $permissionsPage->total() }} permissions, shown 20 at a time</div>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="text-muted small"><span id="selectedPermissionCount">{{ count(array_intersect($assignedPermissionNames, $flatPermissions->pluck('name')->all())) }}</span> selected on this page</span>
                        <label class="select-page-control mb-0 {{ $activeRoleIsAdministrator ? 'disabled' : '' }}">
                            <input type="checkbox" id="selectAllPage" {{ $activeRoleIsAdministrator ? 'disabled' : '' }}>
                            <span>Select page</span>
                        </label>
                        <button type="submit" class="btn btn-primary" {{ $activeRoleIsAdministrator ? 'disabled' : '' }}>
                            <i class="mdi mdi-content-save me-1"></i>Save permissions
                        </button>
                    </div>
                </div>

                @if($flatPermissions->isEmpty())
                    <div class="card-body text-center py-5">
                        <i class="mdi mdi-key-variant mdi-48px text-muted mb-3"></i>
                        <h5 class="text-muted">No permissions found</h5>
                        <p class="text-muted mb-0">Try another search.</p>
                    </div>
                @else
                    <div class="permission-groups-list">
                        @foreach($permissions as $category => $categoryPermissions)
                            @php($permissionGroup = 'permission-group-' . $loop->index)
                            @php($groupSelectedCount = $categoryPermissions->filter(fn ($permission) => in_array($permission->name, $assignedPermissionNames, true))->count())
                            <details class="permission-group" {{ $loop->first || request('search') ? 'open' : '' }}>
                                <summary class="permission-group-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="mdi mdi-chevron-right permission-group-chevron"></i>
                                        <span class="access-control-badge blue">{{ $category }}</span>
                                        <span class="text-muted small">{{ $categoryPermissions->count() }} permissions</span>
                                        <span class="access-control-badge green group-selected-count {{ $groupSelectedCount > 0 ? '' : 'd-none' }}" data-permission-group-count="{{ $permissionGroup }}">{{ $groupSelectedCount }} selected</span>
                                    </div>
                                    <label class="group-select-control {{ $activeRoleIsAdministrator ? 'disabled' : '' }}" title="Select all permissions in {{ $category }}">
                                        <input type="checkbox" class="category-select-checkbox" data-permission-group="{{ $permissionGroup }}" {{ $activeRoleIsAdministrator ? 'disabled' : '' }}>
                                        <span>Select group</span>
                                    </label>
                                </summary>
                                <div class="permission-group-body" data-permission-group-body="{{ $permissionGroup }}">
                                    <div class="permission-checklist-grid">
                                        @foreach($categoryPermissions as $permission)
                                            <div class="permission-option {{ $activeRoleIsAdministrator ? 'is-locked' : '' }}" @if($permission->description) title="{{ $permission->description }}" @endif>
                                                <input
                                                    id="role_permission_{{ $permission->id }}"
                                                    class="form-check-input permission-checkbox"
                                                    data-permission-group="{{ $permissionGroup }}"
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->id }}"
                                                    @checked(in_array($permission->name, $assignedPermissionNames, true))
                                                    {{ $activeRoleIsAdministrator ? 'disabled' : '' }}
                                                >
                                                <label class="permission-option-copy" for="role_permission_{{ $permission->id }}">
                                                    <span class="permission-option-title">{{ $permission->display_name ?: $permission->name }}</span>
                                                    <code>{{ $permission->name }}</code>
                                                    @if($permission->is_route_controlled)
                                                        <span class="access-control-badge green">Route controlled</span>
                                                    @else
                                                        <span class="access-control-badge slate">Assignable</span>
                                                    @endif
                                                </label>
                                                @if($activeRoleIsAdministrator)
                                                    <i class="mdi mdi-lock-check permission-lock" title="Always granted"></i>
                                                @endif
                                                @if(! $permission->is_route_controlled)
                                                    <button type="submit" form="delete-permission-{{ $permission->id }}" class="permission-delete-button" title="Delete permission" onclick="return confirm('Are you sure you want to delete this permission? This will remove it from all roles.')">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif

                @if(! $flatPermissions->isEmpty())
                    <div class="permission-manager-footer">
                        <div class="text-muted small">Save this page before moving to the next page.</div>
                        <div class="permission-manager-footer-actions">
                            <nav class="permission-pagination" aria-label="Permissions pagination">
                                <div class="permission-pagination-summary">
                                    Showing <strong>{{ $permissionsPage->firstItem() ?? 0 }}</strong>
                                    to <strong>{{ $permissionsPage->lastItem() ?? 0 }}</strong>
                                    of <strong>{{ $permissionsPage->total() }}</strong>
                                </div>
                                <div class="permission-pagination-controls">
                                    @if($permissionsPage->onFirstPage())
                                        <span class="permission-pagination-button disabled" aria-disabled="true">
                                            <i class="mdi mdi-chevron-left"></i><span>Previous</span>
                                        </span>
                                    @else
                                        <a class="permission-pagination-button" href="{{ $permissionsPage->previousPageUrl() }}" rel="prev" data-permission-navigation>
                                            <i class="mdi mdi-chevron-left"></i><span>Previous</span>
                                        </a>
                                    @endif

                                    @foreach($permissionsPage->getUrlRange(1, $permissionsPage->lastPage()) as $page => $url)
                                        @if($page == $permissionsPage->currentPage())
                                            <span class="permission-pagination-button active" aria-current="page">{{ $page }}</span>
                                        @else
                                            <a class="permission-pagination-button" href="{{ $url }}" data-permission-navigation>{{ $page }}</a>
                                        @endif
                                    @endforeach

                                    @if($permissionsPage->hasMorePages())
                                        <a class="permission-pagination-button" href="{{ $permissionsPage->nextPageUrl() }}" rel="next" data-permission-navigation>
                                            <span>Next</span><i class="mdi mdi-chevron-right"></i>
                                        </a>
                                    @else
                                        <span class="permission-pagination-button disabled" aria-disabled="true">
                                            <span>Next</span><i class="mdi mdi-chevron-right"></i>
                                        </span>
                                    @endif
                                </div>
                            </nav>
                            <button type="submit" class="btn btn-primary" {{ $activeRoleIsAdministrator ? 'disabled' : '' }}>
                                <i class="mdi mdi-content-save me-1"></i>Save permissions
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </form>

        @foreach($flatPermissions as $permission)
            @if(! $permission->is_route_controlled)
                <form id="delete-permission-{{ $permission->id }}" action="{{ route('admin.permissions.delete', $permission->id) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach
    @endif
</div>
@endsection

@push('styles')
<style>
.permission-manager-page .role-picker-card {
    border-left: 4px solid #2563eb !important;
}

.permission-manager-page .role-summary-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}

.permission-manager-page .permission-checklist-card {
    overflow: hidden;
}

.permission-manager-page .permission-groups-list {
    padding: 0.75rem 1rem;
}

.permission-manager-page .permission-group {
    border: 1px solid #e2e8f0;
    border-radius: 0.6rem;
    margin-bottom: 0.75rem;
    overflow: hidden;
}

.permission-manager-page .permission-group:last-child {
    margin-bottom: 0;
}

.permission-manager-page .permission-group-header {
    align-items: center;
    background: #f8fafc;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    list-style: none;
    padding: 0.7rem 0.9rem;
}

.permission-manager-page .permission-group-header::-webkit-details-marker {
    display: none;
}

.permission-manager-page .permission-group-chevron {
    color: #64748b;
    transition: transform 0.15s ease;
}

.permission-manager-page .permission-group[open] .permission-group-chevron {
    transform: rotate(90deg);
}

.permission-manager-page .permission-group-body {
    border-top: 1px solid #e2e8f0;
    padding: 0.75rem;
}

.permission-manager-page .permission-checklist-grid {
    display: grid;
    gap: 0.55rem 0.75rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.permission-manager-page .permission-option {
    align-items: flex-start;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.45rem;
    cursor: pointer;
    display: flex;
    gap: 0.65rem;
    min-height: 64px;
    padding: 0.65rem 0.7rem;
    position: relative;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.permission-manager-page .permission-option:hover {
    background: #f8fafc;
    border-color: #93c5fd;
}

.permission-manager-page .permission-option:has(.permission-checkbox:checked) {
    background: #eff6ff;
    border-color: #93c5fd;
}

.permission-manager-page .permission-option.is-locked {
    cursor: default;
}

.permission-manager-page .permission-option .permission-checkbox {
    cursor: pointer;
    flex: 0 0 auto;
    height: 1.05rem;
    margin-top: 0.15rem;
    width: 1.05rem;
}

.permission-manager-page .permission-option-copy {
    cursor: pointer;
    display: flex;
    flex: 1 1 auto;
    flex-wrap: wrap;
    gap: 0.25rem 0.45rem;
    min-width: 0;
}

.permission-manager-page .permission-option-title {
    color: #0f172a;
    flex-basis: 100%;
    font-size: 0.84rem;
    font-weight: 700;
}

.permission-manager-page .permission-option-copy code {
    color: #64748b;
    font-size: 0.7rem;
    overflow-wrap: anywhere;
}

.permission-manager-page .permission-lock {
    color: #64748b;
    font-size: 1rem;
}

.permission-manager-page .permission-delete-button {
    background: transparent;
    border: 0;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 0.1rem;
}

.permission-manager-page .permission-delete-button:hover {
    color: #dc2626;
}

.permission-manager-page .select-page-control {
    align-items: center;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    font-size: 0.8rem;
    gap: 0.35rem;
}

.permission-manager-page .select-page-control.disabled {
    cursor: default;
    opacity: 0.65;
}

.permission-manager-page .select-page-control input {
    cursor: pointer;
    height: 1rem;
    width: 1rem;
}

.permission-manager-page .group-select-control {
    align-items: center;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    flex: 0 0 auto;
    font-size: 0.78rem;
    gap: 0.35rem;
    white-space: nowrap;
}

.permission-manager-page .group-select-control.disabled {
    cursor: default;
    opacity: 0.65;
}

.permission-manager-page .group-select-control input {
    cursor: pointer;
    height: 1rem;
    width: 1rem;
}

.permission-manager-footer {
    align-items: center;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 1rem;
}

.permission-manager-footer-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-left: auto;
}

.permission-pagination {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin: 0;
}

.permission-pagination-summary {
    color: #64748b;
    font-size: 0.75rem;
    white-space: nowrap;
}

.permission-pagination-controls {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.permission-pagination-button {
    align-items: center;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 0.375rem;
    color: #334155 !important;
    display: inline-flex;
    font-size: 0.78rem;
    gap: 0.25rem;
    justify-content: center;
    min-height: 32px;
    min-width: 34px;
    padding: 0.35rem 0.55rem;
    text-decoration: none !important;
}

.permission-pagination-button:hover {
    background: #eff6ff !important;
    border-color: #93c5fd !important;
    color: #1d4ed8 !important;
}

.permission-pagination-button.active {
    background: #2563eb !important;
    border-color: #2563eb !important;
    color: #ffffff !important;
}

.permission-pagination-button.disabled {
    background: #f8fafc !important;
    color: #94a3b8 !important;
    cursor: not-allowed;
    opacity: 0.8;
}

@media (max-width: 767px) {
    .permission-manager-page .permission-checklist-grid {
        grid-template-columns: 1fr;
    }

    .permission-manager-footer {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.75rem;
    }

    .permission-manager-footer-actions {
        align-items: flex-start;
        margin-left: 0;
        width: 100%;
    }

    .permission-pagination {
        align-items: flex-start;
        flex-direction: column;
        width: 100%;
    }

    .permission-pagination-controls {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const permissionCheckboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
    const groupSelectors = Array.from(document.querySelectorAll('.category-select-checkbox'));
    const selectAllPage = document.getElementById('selectAllPage');
    const selectedCount = document.getElementById('selectedPermissionCount');
    const permissionForm = document.getElementById('rolePermissionForm');
    const searchForm = document.getElementById('permissionSearchForm');
    let hasUnsavedChanges = false;

    function confirmNavigation(event) {
        if (!hasUnsavedChanges) {
            return;
        }

        if (!window.confirm('You have unsaved permission changes. Leave this page without saving?')) {
            event.preventDefault();
        }
    }

    function updateSelectedCount() {
        if (selectedCount) {
            selectedCount.textContent = permissionCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
        }

        if (selectAllPage && permissionCheckboxes.length > 0) {
            const checkedCount = permissionCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            selectAllPage.checked = checkedCount === permissionCheckboxes.length;
            selectAllPage.indeterminate = checkedCount > 0 && checkedCount < permissionCheckboxes.length;
        }

        groupSelectors.forEach(function (selector) {
            const groupCheckboxes = permissionCheckboxes.filter(function (checkbox) {
                return checkbox.dataset.permissionGroup === selector.dataset.permissionGroup;
            });
            const allChecked = groupCheckboxes.length > 0 && groupCheckboxes.every(function (checkbox) { return checkbox.checked; });
            const checkedCount = groupCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            selector.checked = allChecked;
            selector.indeterminate = checkedCount > 0 && checkedCount < groupCheckboxes.length;

            const countBadge = selector.closest('summary')?.querySelector('[data-permission-group-count]');
            if (countBadge) {
                countBadge.textContent = checkedCount + ' selected';
                countBadge.classList.toggle('d-none', checkedCount === 0);
            }
        });
    }

    if (selectAllPage) {
        selectAllPage.addEventListener('change', function () {
            permissionCheckboxes.forEach(function (checkbox) { checkbox.checked = selectAllPage.checked; });
            hasUnsavedChanges = true;
            updateSelectedCount();
        });
    }

    groupSelectors.forEach(function (selector) {
        selector.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        selector.addEventListener('change', function () {
            const groupCheckboxes = permissionCheckboxes.filter(function (checkbox) {
                return checkbox.dataset.permissionGroup === selector.dataset.permissionGroup;
            });
            groupCheckboxes.forEach(function (checkbox) { checkbox.checked = selector.checked; });
            hasUnsavedChanges = true;
            updateSelectedCount();
        });
    });

    permissionCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            hasUnsavedChanges = true;
            updateSelectedCount();
        });
    });

    document.querySelectorAll('[data-permission-navigation]').forEach(function (link) {
        link.addEventListener('click', confirmNavigation);
    });

    if (searchForm) {
        searchForm.addEventListener('submit', confirmNavigation);
    }

    if (permissionForm) {
        permissionForm.addEventListener('submit', function () {
            hasUnsavedChanges = false;
        });
    }

    window.addEventListener('beforeunload', function (event) {
        if (!hasUnsavedChanges) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    const rolePicker = document.getElementById('rolePicker');
    if (rolePicker) {
        rolePicker.dataset.currentValue = rolePicker.value;
        rolePicker.addEventListener('change', function () {
            if (hasUnsavedChanges && !window.confirm('You have unsaved permission changes. Change roles without saving?')) {
                rolePicker.value = rolePicker.dataset.currentValue;
                return;
            }

            rolePicker.dataset.currentValue = rolePicker.value;
            const url = new URL(window.location.href);
            url.searchParams.set('role', rolePicker.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

    updateSelectedCount();
});
</script>
@endpush
