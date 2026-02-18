@extends('layouts.admin')

@section('title', 'Add System Account')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Add System Account</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.system-accounts') }}">System Accounts</a></li>
                        <li class="breadcrumb-item active">Add Account</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Add Account Form -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Create New System Account</h4>
                        
                        <form id="addAccountForm">
                            @csrf
                            
                            <div class="form-group mb-4">
                                <label for="parent_account" class="form-label">Parent Account <span class="text-danger">*</span></label>
                                <select class="form-control form-control-lg" id="parent_account" name="parent_account" required>
                                    <option value="">-- Select parent or add new --</option>
                                    <option value="add_new">+ Add new parent account</option>
                                    @foreach($parentAccounts as $acc)
                                        <option value="{{ $acc->Id }}" data-code="{{ $acc->code }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Choose an existing parent to add a sub-account, or select "Add new parent account" to create a parent.</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="code" name="code" required placeholder="Auto-filled from parent or enter new code">
                            </div>

                            <div class="form-group mb-4">
                                <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Account name">
                            </div>

                            <div class="form-group mb-4">
                                <label for="accountType" class="form-label">Account Type</label>
                                <select class="form-control form-control-lg" id="accountType" name="accountType">
                                    <option value="">Select Account Type</option>
                                    <option value="Bank">Bank</option>
                                    <option value="Other">Other</option>
                                    <option value="Accounts Receivable (A/R)">Accounts Receivable (A/R)</option>
                                    <option value="Fixed Assets">Fixed Assets</option>
                                    <option value="Current Liability">Current Liability</option>
                                    <option value="Other Current Assets">Other Current Assets</option>
                                    <option value="Other Current Liabilities">Other Current Liabilities</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-control form-control-lg" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Asset">Asset</option>
                                    <option value="Liability">Liability</option>
                                    <option value="Equity">Equity</option>
                                    <option value="Income">Income</option>
                                    <option value="Expense">Expense</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="accountSubType" class="form-label">Account Sub Type</label>
                                <input type="text" class="form-control form-control-lg" id="accountSubType" name="accountSubType">
                            </div>

                            <div class="form-group mb-4">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-control form-control-lg" id="currency" name="currency">
                                    <option value="UGX">UGX</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>

                            <div class="form-group mb-4" id="subCodeWrapper">
                                <label for="sub_code" class="form-label">Sub Code</label>
                                <input type="text" class="form-control form-control-lg" id="sub_code" name="sub_code" placeholder="Auto-generated">
                                <small class="form-text text-muted">Optional. Leave empty to auto-generate a sub code.</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="form-group mb-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control form-control-lg" id="status" name="status" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                                <a href="{{ route('admin.settings.system-accounts') }}" class="btn btn-secondary btn-lg">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Get elements
    const addParentSelect = document.getElementById('parent_account');
    const subCodeInput = document.getElementById('sub_code');
    const subCodeWrapper = document.getElementById('subCodeWrapper');
    
    // Fetch suggested sub_code
    function fetchSuggestedSubCode(parentId, targetInput) {
        if (!parentId) {
            targetInput.value = '';
            return;
        }

        fetch(`/admin/settings/system-accounts/suggest-sub-code?parent_id=${parentId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(resp => resp.json())
        .then(json => {
            if (json.success && json.sub_code) {
                targetInput.value = json.sub_code;
            } else {
                targetInput.value = '';
            }
        })
        .catch(err => {
            console.error('Failed to fetch suggested sub code', err);
            targetInput.value = '';
        });
    }
    
    // Handle parent selection changes
    function handleParentChange() {
        if (!addParentSelect) return;
        
        const val = addParentSelect.value;
        const codeInput = document.getElementById('code');
        const nameInput = document.getElementById('name');

        if (!codeInput || !nameInput) return;

        if (val === 'add_new' || val === '') {
            // Adding new parent
            if (subCodeWrapper) subCodeWrapper.style.display = 'none';
            codeInput.removeAttribute('readonly');
            codeInput.value = '';
            codeInput.style.backgroundColor = '';
            nameInput.placeholder = 'Parent account name';
            if (subCodeInput) subCodeInput.value = '';
        } else {
            // Adding child under existing parent
            if (subCodeWrapper) subCodeWrapper.style.display = '';
            
            const opt = addParentSelect.options[addParentSelect.selectedIndex];
            const parentCode = opt && opt.dataset && opt.dataset.code ? opt.dataset.code.trim() : (opt ? (opt.text.split(' - ')[0] || '').trim() : '');
            
            codeInput.value = parentCode;
            codeInput.setAttribute('readonly', 'readonly');
            codeInput.style.backgroundColor = '#e9ecef';
            nameInput.placeholder = 'Sub-account name';
            
            if (subCodeInput) fetchSuggestedSubCode(val, subCodeInput);
        }
    }
    
    if (addParentSelect) {
        addParentSelect.addEventListener('change', handleParentChange);
        // Set initial state
        handleParentChange();
    }

    // Form submission
    document.getElementById('addAccountForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value || null;
        });

        // Normalize parent_account and sub_code
        if (data.parent_account === 'add_new' || data.parent_account === '') {
            data.parent_account = null;
            data.sub_code = null;
        } else {
            data.parent_account = data.parent_account ? data.parent_account : null;
            if (!data.sub_code) data.sub_code = null;
        }
        
        fetch('/admin/settings/system-accounts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '{{ route("admin.settings.system-accounts") }}';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to create account'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while creating the account'
            });
        });
    });
</script>
@endpush
