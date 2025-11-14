@extends('layouts.admin')

@section('title', 'System Accounts')

@push('styles')
<style>
    .filter-card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .search-box {
        position: relative;
    }
    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    .search-box input {
        padding-left: 40px;
    }
    
    /* Modern Pagination Styles */
    .modern-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-top: 1px solid #e9ecef;
    }
    
    .pagination-info {
        color: #6c757d;
        font-size: 14px;
    }
    
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pagination-numbers {
        display: flex;
        gap: 5px;
    }
    
    .pagination-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: white;
        color: #495057;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .pagination-btn:hover:not([disabled]) {
        background: #f8f9fa;
        border-color: #0d6efd;
        color: #0d6efd;
    }
    
    .pagination-btn.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: white;
        font-weight: bold;
    }
    
    .pagination-btn[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">System Accounts</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item active">System Accounts</li>
                    </ol>
                </div>
            </div>
        </div>

                        <!-- Page Header Actions -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-bold">System Accounts (Chart of Accounts)</h3>
                        <p class="text-muted mb-0">Manage your chart of accounts and system accounts</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="addAccountBtn">
                            <i class="mdi mdi-plus"></i> Add Account
                        </button>
                    </div>
                </div>
            </div>
        </div>        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="{{ route('admin.settings.system-accounts') }}" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label>Search</label>
                            <div class="search-box">
                                <i class="mdi mdi-magnify"></i>
                                <input type="text" class="form-control" name="search" placeholder="Search by code, name..." value="{{ request('search') }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label>Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label>Currency</label>
                            <select class="form-control" name="currency">
                                <option value="">All Currencies</option>
                                <option value="UGX" {{ request('currency') == 'UGX' ? 'selected' : '' }}>UGX</option>
                                <option value="USD" {{ request('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ request('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ request('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label>Per Page</label>
                            <select class="form-control" name="per_page">
                                <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page', 25) == '25' ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.settings.system-accounts') }}" class="btn btn-secondary btn-block mt-2">
                            <i class="mdi mdi-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Accounts Table -->
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">All System Accounts ({{ $systemAccounts->total() }})</h4>
                            <span class="text-muted">Showing {{ $systemAccounts->firstItem() ?? 0 }} to {{ $systemAccounts->lastItem() ?? 0 }} of {{ $systemAccounts->total() }} entries</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="accountsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Account Type</th>
                                        <th>Account Sub Type</th>
                                        <th>Currency</th>
                                        <th>Running Balance</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($systemAccounts as $account)
                                    <tr>
                                        <td>{{ ($systemAccounts->currentPage() - 1) * $systemAccounts->perPage() + $loop->iteration }}</td>
                                        <td class="font-weight-bold">{{ $account->code }}</td>
                                        <td>{{ $account->name }}</td>
                                        <td>{{ $account->accountType ?? 'N/A' }}</td>
                                        <td>{{ $account->accountSubType ?? 'N/A' }}</td>
                                        <td>{{ $account->currency ?? 'UGX' }}</td>
                                        <td class="text-right">{{ number_format($account->running_balance ?? 0, 2) }}</td>
                                        <td>
                                            @if($account->status == 1)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-info btn-view-account" title="View" data-id="{{ $account->id }}">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary btn-edit-account" title="Edit" 
                                                    data-id="{{ $account->id }}"
                                                    data-code="{{ $account->code }}"
                                                    data-name="{{ $account->name }}"
                                                    data-accounttype="{{ $account->accountType }}"
                                                    data-accountsubtype="{{ $account->accountSubType }}"
                                                    data-currency="{{ $account->currency }}"
                                                    data-description="{{ $account->description }}"
                                                    data-parent_account="{{ $account->parent_account }}"
                                                    data-status="{{ $account->status }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-account" title="Delete" 
                                                    data-id="{{ $account->id }}"
                                                    data-name="{{ $account->name }}">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="mdi mdi-bank mdi-48px"></i>
                                                <h5 class="mt-2">No system accounts found</h5>
                                                <p>Try adjusting your filters</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Modern Pagination -->
                        <div class="modern-pagination mt-4">
                            <div class="pagination-info">
                                Showing {{ $systemAccounts->firstItem() ?? 0 }} to {{ $systemAccounts->lastItem() ?? 0 }} of {{ $systemAccounts->total() }} entries
                            </div>
                            <div class="pagination-controls">
                                @if ($systemAccounts->onFirstPage())
                                    <span class="pagination-btn" disabled>
                                        <i class="mdi mdi-chevron-left"></i>
                                        Previous
                                    </span>
                                @else
                                    <a href="{{ $systemAccounts->appends(request()->query())->previousPageUrl() }}" class="pagination-btn">
                                        <i class="mdi mdi-chevron-left"></i>
                                        Previous
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @php
                                        $currentPage = $systemAccounts->currentPage();
                                        $lastPage = $systemAccounts->lastPage();
                                        $start = max(1, $currentPage - 2);
                                        $end = min($lastPage, $currentPage + 2);
                                        
                                        // Adjust if at the beginning or end
                                        if ($currentPage <= 3) {
                                            $end = min(5, $lastPage);
                                        }
                                        if ($currentPage >= $lastPage - 2) {
                                            $start = max(1, $lastPage - 4);
                                        }
                                    @endphp

                                    @if($start > 1)
                                        <a href="{{ $systemAccounts->appends(request()->query())->url(1) }}" class="pagination-btn">1</a>
                                        @if($start > 2)
                                            <span class="pagination-btn" disabled>...</span>
                                        @endif
                                    @endif

                                    @for ($page = $start; $page <= $end; $page++)
                                        @if ($page == $currentPage)
                                            <span class="pagination-btn active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $systemAccounts->appends(request()->query())->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    @if($end < $lastPage)
                                        @if($end < $lastPage - 1)
                                            <span class="pagination-btn" disabled>...</span>
                                        @endif
                                        <a href="{{ $systemAccounts->appends(request()->query())->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                    @endif
                                </div>

                                @if ($systemAccounts->hasMorePages())
                                    <a href="{{ $systemAccounts->appends(request()->query())->nextPageUrl() }}" class="pagination-btn">
                                        Next
                                        <i class="mdi mdi-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn" disabled>
                                        Next
                                        <i class="mdi mdi-chevron-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="addAccountModalLabel">Add System Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAccountForm">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="code" class="form-label" style="color: #000;">Account Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="name" class="form-label" style="color: #000;">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="accountType" class="form-label" style="color: #000;">Account Type</label>
                        <select class="form-control" id="accountType" name="accountType" style="background-color: white; color: #000;">
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

                    <div class="form-group mb-3">
                        <label for="accountSubType" class="form-label" style="color: #000;">Account Sub Type</label>
                        <input type="text" class="form-control" id="accountSubType" name="accountSubType" style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="currency" class="form-label" style="color: #000;">Currency</label>
                        <select class="form-control" id="currency" name="currency" style="background-color: white; color: #000;">
                            <option value="UGX">UGX</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="parent_account" class="form-label" style="color: #000;">Parent Account</label>
                        <select class="form-control" id="parent_account" name="parent_account" style="background-color: white; color: #000;">
                            <option value="">None</option>
                            @foreach(\App\Models\SystemAccount::where('status', 1)->get() as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label" style="color: #000;">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" style="background-color: white; color: #000;"></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="status" class="form-label" style="color: #000;">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required style="background-color: white; color: #000;">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="editAccountModalLabel">Edit System Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAccountForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_account_id" name="account_id">
                <div class="modal-body" style="background-color: white;">
                    <!-- Current Values Section -->
                    <div class="alert alert-info mb-4" style="background-color: #e7f3ff; border-left: 4px solid #0d6efd;">
                        <h6 class="mb-2" style="color: #084298; font-weight: bold;">
                            <i class="mdi mdi-information"></i> Current Account Details
                        </h6>
                        <div id="currentAccountDetails" style="color: #052c65; font-size: 0.9rem;">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>

                    <h6 class="mb-3" style="color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 5px;">
                        <i class="mdi mdi-pencil"></i> Update Account Information
                    </h6>

                    <div class="form-group mb-3">
                        <label for="edit_code" class="form-label" style="color: #000;">Account Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_code" name="code" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_name" class="form-label" style="color: #000;">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_accountType" class="form-label" style="color: #000;">Account Type</label>
                        <select class="form-control" id="edit_accountType" name="accountType" style="background-color: white; color: #000;">
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

                    <div class="form-group mb-3">
                        <label for="edit_accountSubType" class="form-label" style="color: #000;">Account Sub Type</label>
                        <input type="text" class="form-control" id="edit_accountSubType" name="accountSubType" style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_currency" class="form-label" style="color: #000;">Currency</label>
                        <select class="form-control" id="edit_currency" name="currency" style="background-color: white; color: #000;">
                            <option value="UGX">UGX</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_status" class="form-label" style="color: #000;">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_status" name="status" required style="background-color: white; color: #000;">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_parent_account" class="form-label" style="color: #000;">Parent Account</label>
                        <select class="form-control" id="edit_parent_account" name="parent_account" style="background-color: white; color: #000;">
                            <option value="">None</option>
                            @foreach(\App\Models\SystemAccount::where('status', 1)->get() as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_description" class="form-label" style="color: #000;">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" style="background-color: white; color: #000;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Account Modal -->
<div class="modal fade" id="viewAccountModal" tabindex="-1" aria-labelledby="viewAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0dcaf0; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="viewAccountModalLabel">View Account Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewAccountContent" style="background-color: white;">
                <div class="text-center py-4">
                    <i class="mdi mdi-loading mdi-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Auto-submit filter form when select changes
    document.querySelectorAll('#filterForm select').forEach(function(select) {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });

    // Search on enter key
    document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filterForm').submit();
        }
    });

    // ============= ADD ACCOUNT =============
    document.getElementById('addAccountBtn').addEventListener('click', function(e) {
        e.preventDefault();
        new bootstrap.Modal(document.getElementById('addAccountModal')).show();
    });

    document.getElementById('addAccountForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value || null;
        });
        
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
                    location.reload();
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

    // ============= EDIT ACCOUNT =============
    document.querySelectorAll('.btn-edit-account').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const id = this.getAttribute('data-id');
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            const accountType = this.getAttribute('data-accounttype');
            const accountSubType = this.getAttribute('data-accountsubtype');
            const currency = this.getAttribute('data-currency');
            const description = this.getAttribute('data-description');
            const parentAccount = this.getAttribute('data-parent_account');
            const status = this.getAttribute('data-status');
            
            // Populate current values display
            const currentDetails = `
                <div style="font-size: 0.9rem;">
                    <p class="mb-1"><strong>Code:</strong> ${code || 'N/A'}</p>
                    <p class="mb-1"><strong>Name:</strong> ${name || 'N/A'}</p>
                    <p class="mb-1"><strong>Account Type:</strong> ${accountType || 'N/A'}</p>
                    <p class="mb-1"><strong>Sub Type:</strong> ${accountSubType || 'N/A'}</p>
                    <p class="mb-1"><strong>Currency:</strong> ${currency || 'N/A'}</p>
                    <p class="mb-1"><strong>Status:</strong> ${status == '1' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</p>
                </div>
            `;
            document.getElementById('currentAccountDetails').innerHTML = currentDetails;
            
            // Populate form fields
            document.getElementById('edit_account_id').value = id || '';
            document.getElementById('edit_code').value = code || '';
            document.getElementById('edit_name').value = name || '';
            document.getElementById('edit_accountType').value = accountType || '';
            document.getElementById('edit_accountSubType').value = accountSubType || '';
            document.getElementById('edit_currency').value = currency || 'UGX';
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_parent_account').value = parentAccount || '';
            document.getElementById('edit_status').value = status || '1';
            
            new bootstrap.Modal(document.getElementById('editAccountModal')).show();
        });
    });

    document.getElementById('editAccountForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const accountId = document.getElementById('edit_account_id').value;
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
                    location.reload();
                });
            } else {
                // Check if there are validation errors
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
                    text: errorMessage,
                    customClass: {
                        popup: 'swal-wide'
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message || 'An error occurred while updating the account',
                customClass: {
                    popup: 'swal-wide'
                }
            });
        });
    });

    // ============= VIEW ACCOUNT ============= (Using event delegation)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-view-account')) {
            e.preventDefault();
            const button = e.target.closest('.btn-view-account');
            const accountId = button.getAttribute('data-id');
            
            console.log('View button clicked, Account ID:', accountId);
            
            if (!accountId || accountId === 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Account ID is missing. Please refresh the page and try again.'
                });
                return;
            }
            
            const viewContent = document.getElementById('viewAccountContent');
            viewContent.innerHTML = '<div class="text-center py-4"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>';
            
            new bootstrap.Modal(document.getElementById('viewAccountModal')).show();
            
            // Use query parameter instead of route parameter to avoid conflicts
            const url = `/admin/settings/system-accounts/view?id=${accountId}`;
            console.log('Fetching account details from:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response content-type:', response.headers.get("content-type"));
                
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Error response body:', text);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    return response.text().then(text => {
                        console.error('Non-JSON response body:', text.substring(0, 500));
                        throw new Error("Server returned non-JSON response. Check console for details.");
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.account) {
                    const account = data.account;
                    const html = `
                        <div class="mb-3" style="background-color: #ffffff; color: #333333;">
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Account Code:</strong> ${account.code || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Account Name:</strong> ${account.name || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Account Type:</strong> ${account.accountType || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Account Sub Type:</strong> ${account.accountSubType || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Currency:</strong> ${account.currency || 'UGX'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Running Balance:</strong> ${parseFloat(account.running_balance || 0).toLocaleString()}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Description:</strong> ${account.description || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Status:</strong> <span class="badge ${account.status == 1 ? 'badge-success' : 'badge-danger'}">${account.status == 1 ? 'Active' : 'Inactive'}</span></p>
                        </div>
                    `;
                    viewContent.innerHTML = html;
                } else {
                    viewContent.innerHTML = '<div class="alert alert-danger">Failed to load account details</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                viewContent.innerHTML = '<div class="alert alert-danger">Failed to load account details. ' + error.message + '</div>';
            });
        }
    });

    // ============= DELETE ACCOUNT =============
    document.querySelectorAll('.btn-delete-account').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const accountId = this.getAttribute('data-id');
            const accountName = this.getAttribute('data-name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete account "${accountName}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/settings/system-accounts/delete/${accountId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'Failed to delete account'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the account'
                        });
                    });
                }
            });
        });
    });
</script>
@endpush
