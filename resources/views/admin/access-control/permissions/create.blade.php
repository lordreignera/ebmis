@extends('layouts.admin')

@section('title', 'Create Permission')

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
                                <i class="mdi mdi-key-plus me-2"></i>Create New Permission
                            </h2>
                            <p class="text-muted mb-0">Add a new permission to the system</p>
                        </div>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i>Back to Permissions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Permission Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.permissions.store') }}" method="POST">
                        @csrf
                        
                        <!-- Permission Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                <i class="mdi mdi-key-variant me-1"></i>Permission Name
                            </label>
                            <input 
                                type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}" 
                                placeholder="e.g., members-view, loans-create, transactions-approve"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Use kebab-case format (lowercase with hyphens). Example: category-action
                            </small>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-close me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check me-1"></i>Create Permission
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
                        <i class="mdi mdi-information me-2"></i>Permission Guidelines
                    </h6>
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="text-primary mb-2">
                            <i class="mdi mdi-format-text me-1"></i>Naming Convention
                        </h6>
                        <ul class="small text-muted ps-3">
                            <li>Use lowercase letters only</li>
                            <li>Separate words with hyphens (-)</li>
                            <li>Format: <code>category-action</code></li>
                            <li>Be descriptive and specific</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-primary mb-2">
                            <i class="mdi mdi-lightbulb-on me-1"></i>Examples
                        </h6>
                        <div class="small">
                            <p class="mb-1"><code>members-view</code> - View members</p>
                            <p class="mb-1"><code>members-create</code> - Create members</p>
                            <p class="mb-1"><code>members-edit</code> - Edit members</p>
                            <p class="mb-1"><code>members-delete</code> - Delete members</p>
                            <p class="mb-1"><code>loans-approve</code> - Approve loans</p>
                            <p class="mb-1"><code>reports-generate</code> - Generate reports</p>
                            <p class="mb-1"><code>transactions-reverse</code> - Reverse transactions</p>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="mdi mdi-information me-1"></i>
                            <strong>Tip:</strong> Group related permissions using a common prefix (e.g., members-, loans-, transactions-)
                        </small>
                    </div>
                </div>
            </div>

            <!-- Common Categories -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="mdi mdi-folder-key me-2"></i>Common Categories
                    </h6>
                    <hr>
                    
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-primary cursor-pointer" onclick="setPrefix('members-')">Members</span>
                        <span class="badge bg-success cursor-pointer" onclick="setPrefix('loans-')">Loans</span>
                        <span class="badge bg-info cursor-pointer" onclick="setPrefix('savings-')">Savings</span>
                        <span class="badge bg-warning cursor-pointer" onclick="setPrefix('transactions-')">Transactions</span>
                        <span class="badge bg-danger cursor-pointer" onclick="setPrefix('reports-')">Reports</span>
                        <span class="badge bg-secondary cursor-pointer" onclick="setPrefix('users-')">Users</span>
                        <span class="badge bg-dark cursor-pointer" onclick="setPrefix('settings-')">Settings</span>
                    </div>
                    <small class="text-muted d-block mt-2">Click a category to use as prefix</small>
                </div>
            </div>

            <!-- Common Actions -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="mdi mdi-play-circle me-2"></i>Common Actions
                    </h6>
                    <hr>
                    
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-outline-primary cursor-pointer" onclick="setSuffix('view')">view</span>
                        <span class="badge bg-outline-success cursor-pointer" onclick="setSuffix('create')">create</span>
                        <span class="badge bg-outline-info cursor-pointer" onclick="setSuffix('edit')">edit</span>
                        <span class="badge bg-outline-warning cursor-pointer" onclick="setSuffix('delete')">delete</span>
                        <span class="badge bg-outline-danger cursor-pointer" onclick="setSuffix('approve')">approve</span>
                        <span class="badge bg-outline-secondary cursor-pointer" onclick="setSuffix('export')">export</span>
                    </div>
                    <small class="text-muted d-block mt-2">Click an action to add as suffix</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cursor-pointer {
    cursor: pointer;
    transition: all 0.2s ease;
}

.cursor-pointer:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

code {
    font-size: 0.875rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    background-color: rgba(79, 209, 199, 0.1);
    color: #2d3748;
}

.bg-outline-primary {
    background-color: transparent;
    border: 1px solid #4fd1c7;
    color: #4fd1c7;
}

.bg-outline-success {
    background-color: transparent;
    border: 1px solid #48bb78;
    color: #48bb78;
}

.bg-outline-info {
    background-color: transparent;
    border: 1px solid #4299e1;
    color: #4299e1;
}

.bg-outline-warning {
    background-color: transparent;
    border: 1px solid #ed8936;
    color: #ed8936;
}

.bg-outline-danger {
    background-color: transparent;
    border: 1px solid #f56565;
    color: #f56565;
}

.bg-outline-secondary {
    background-color: transparent;
    border: 1px solid #718096;
    color: #718096;
}
</style>
@endpush

@push('scripts')
<script>
function setPrefix(prefix) {
    const nameInput = document.getElementById('name');
    const currentValue = nameInput.value;
    
    // Remove existing prefix if any
    const parts = currentValue.split('-');
    if (parts.length > 1) {
        nameInput.value = prefix + parts.slice(1).join('-');
    } else {
        nameInput.value = prefix + currentValue;
    }
    
    nameInput.focus();
}

function setSuffix(suffix) {
    const nameInput = document.getElementById('name');
    const currentValue = nameInput.value;
    
    if (!currentValue) {
        nameInput.value = suffix;
    } else if (currentValue.endsWith('-')) {
        nameInput.value = currentValue + suffix;
    } else {
        nameInput.value = currentValue + '-' + suffix;
    }
    
    nameInput.focus();
}
</script>
@endpush
