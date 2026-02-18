@extends('layouts.admin')

@section('title', 'Edit System Account')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Edit System Account</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.system-accounts') }}">System Accounts</a></li>
                        <li class="breadcrumb-item active">Edit Account</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Edit Account Form -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Edit System Account</h4>
                        
                        <!-- Current Values Display -->
                        <div class="alert alert-info mb-4" style="background-color: #e7f3ff; border-left: 4px solid #0d6efd;">
                            <h6 class="mb-2" style="color: #084298; font-weight: bold;">
                                <i class="mdi mdi-information"></i> Current Account Details
                            </h6>
                            <div style="color: #052c65; font-size: 0.9rem;">
                                <p class="mb-1"><strong>Code:</strong> {{ $account->code }}</p>
                                <p class="mb-1"><strong>Sub Code:</strong> {{ $account->sub_code ?? '-' }}</p>
                                <p class="mb-1"><strong>Parent Account:</strong> {{ $account->parent ? $account->parent->name : '-' }}</p>
                                <p class="mb-1"><strong>Name:</strong> {{ $account->name }}</p>
                                <p class="mb-1"><strong>Account Type:</strong> {{ $account->accountType ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Category:</strong> 
                                    @if($account->category == 'Asset')
                                        <span class="badge badge-info">Asset</span>
                                    @elseif($account->category == 'Liability')
                                        <span class="badge badge-warning">Liability</span>
                                    @elseif($account->category == 'Equity')
                                        <span class="badge badge-primary">Equity</span>
                                    @elseif($account->category == 'Income')
                                        <span class="badge badge-success">Income</span>
                                    @elseif($account->category == 'Expense')
                                        <span class="badge badge-danger">Expense</span>
                                    @else
                                        {{ $account->category ?? 'N/A' }}
                                    @endif
                                </p>
                                <p class="mb-1"><strong>Currency:</strong> {{ $account->currency ?? 'UGX' }}</p>
                                <p class="mb-0"><strong>Status:</strong> 
                                    @if($account->status == 1)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        <form id="editAccountForm">
                            @csrf
                            
                            <div class="form-group mb-4">
                                <label for="parent_account" class="form-label">Parent Account</label>
                                <select class="form-control form-control-lg" id="parent_account" name="parent_account">
                                    <option value="">None (Top-level account)</option>
                                    @foreach($parentAccounts as $acc)
                                        <option value="{{ $acc->Id }}" data-code="{{ $acc->code }}" {{ $account->parent_account == $acc->Id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select a parent to make this a sub-account, or leave as "None" for a top-level account</small>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="code" name="code" required value="{{ $account->code }}">
                                <small class="form-text text-muted" id="code-hint">{{ $account->parent_account ? 'Code is inherited from parent account' : 'Enter account code for top-level account' }}</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" required value="{{ $account->name }}">
                            </div>

                            <div class="form-group mb-4">
                                <label for="accountType" class="form-label">Account Type</label>
                                <select class="form-control form-control-lg" id="accountType" name="accountType">
                                    <option value="">Select Account Type</option>
                                    <option value="Bank" {{ $account->accountType == 'Bank' ? 'selected' : '' }}>Bank</option>
                                    <option value="Other" {{ $account->accountType == 'Other' ? 'selected' : '' }}>Other</option>
                                    <option value="Accounts Receivable (A/R)" {{ $account->accountType == 'Accounts Receivable (A/R)' ? 'selected' : '' }}>Accounts Receivable (A/R)</option>
                                    <option value="Fixed Assets" {{ $account->accountType == 'Fixed Assets' ? 'selected' : '' }}>Fixed Assets</option>
                                    <option value="Current Liability" {{ $account->accountType == 'Current Liability' ? 'selected' : '' }}>Current Liability</option>
                                    <option value="Other Current Assets" {{ $account->accountType == 'Other Current Assets' ? 'selected' : '' }}>Other Current Assets</option>
                                    <option value="Other Current Liabilities" {{ $account->accountType == 'Other Current Liabilities' ? 'selected' : '' }}>Other Current Liabilities</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-control form-control-lg" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Asset" {{ $account->category == 'Asset' ? 'selected' : '' }}>Asset</option>
                                    <option value="Liability" {{ $account->category == 'Liability' ? 'selected' : '' }}>Liability</option>
                                    <option value="Equity" {{ $account->category == 'Equity' ? 'selected' : '' }}>Equity</option>
                                    <option value="Income" {{ $account->category == 'Income' ? 'selected' : '' }}>Income</option>
                                    <option value="Expense" {{ $account->category == 'Expense' ? 'selected' : '' }}>Expense</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="accountSubType" class="form-label">Account Sub Type</label>
                                <input type="text" class="form-control form-control-lg" id="accountSubType" name="accountSubType" value="{{ $account->accountSubType }}">
                            </div>

                            <div class="form-group mb-4">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-control form-control-lg" id="currency" name="currency">
                                    <option value="UGX" {{ $account->currency == 'UGX' ? 'selected' : '' }}>UGX</option>
                                    <option value="USD" {{ $account->currency == 'USD' ? 'selected' : '' }}>USD</option>
                                    <option value="EUR" {{ $account->currency == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="GBP" {{ $account->currency == 'GBP' ? 'selected' : '' }}>GBP</option>
                                </select>
                            </div>

                            <div class="form-group mb-4" id="subCodeWrapper" style="{{ $account->sub_code ? '' : 'display: none;' }}">
                                <label for="sub_code" class="form-label">Sub Code</label>
                                <input type="text" class="form-control form-control-lg" id="sub_code" name="sub_code" value="{{ $account->sub_code }}" placeholder="Auto-generated">
                                <small class="form-text text-muted">Auto-generated when you select a parent account</small>
                            </div>

                            <div class="form-group mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ $account->description }}</textarea>
                            </div>

                            <div class="form-group mb-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control form-control-lg" id="status" name="status" required>
                                    <option value="1" {{ $account->status == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $account->status == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg">Update Account</button>
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
    const accountId = {{ $account->Id }};

    // Get elements
    const parentSelect = document.getElementById('parent_account');
    const codeInput = document.getElementById('code');
    const codeHint = document.getElementById('code-hint');
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
        if (!parentSelect) return;
        
        const val = parentSelect.value;

        if (val === '') {
            // No parent - top-level account
            if (subCodeWrapper) subCodeWrapper.style.display = 'none';
            codeInput.removeAttribute('readonly');
            codeInput.style.backgroundColor = '';
            codeHint.textContent = 'Enter account code for top-level account';
            if (subCodeInput) subCodeInput.value = '';
        } else {
            // Has parent - sub-account
            if (subCodeWrapper) subCodeWrapper.style.display = '';
            
            const opt = parentSelect.options[parentSelect.selectedIndex];
            const parentCode = opt && opt.dataset && opt.dataset.code ? opt.dataset.code.trim() : (opt ? (opt.text.split(' - ')[0] || '').trim() : '');
            
            codeInput.value = parentCode;
            codeInput.setAttribute('readonly', 'readonly');
            codeInput.style.backgroundColor = '#e9ecef';
            codeHint.textContent = 'Code is inherited from parent account';
            
            if (subCodeInput) fetchSuggestedSubCode(val, subCodeInput);
        }
    }
    
    if (parentSelect) {
        parentSelect.addEventListener('change', handleParentChange);
        // Set initial state
        handleParentChange();
    }

    // Form submission
    document.getElementById('editAccountForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(`/admin/settings/system-accounts/update/${accountId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Server error');
                });
            }
            return response.json();
        })
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
                let errorMessage = data.message || 'Failed to update account';
                if (data.errors) {
                    errorMessage += '\n\n';
                    Object.values(data.errors).forEach(errors => {
                        errorMessage += errors.join('\n') + '\n';
                    });
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message || 'An error occurred while updating the account'
            });
        });
    });
</script>
@endpush
