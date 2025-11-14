@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-cash-multiple"></i>
        </span> Fees Types
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Fees Types</li>
        </ul>
    </nav>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap">
                    <div class="mb-3 mb-md-0">
                        <h4 class="card-title mb-0" style="color: #000;">Fees Types</h4>
                        <p class="text-muted mt-2 mb-0">Types of Fees that have been added to the system.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeTypeModal" style="color: #000; font-weight: 600; background-color: #007bff; border: 1px solid #007bff;">
                            <i class="mdi mdi-plus"></i> Add Fees Type
                        </button>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('admin.settings.fees-products') }}" id="searchForm">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Type in to Search" value="{{ request('search') }}">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8 text-end">
                        <span class="text-muted">Show</span>
                        <select class="form-select d-inline-block w-auto" name="per_page" onchange="updatePerPage(this.value)">
                            <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fees Type Name</th>
                                <th>Fees Account</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feeTypes as $feeType)
                            <tr>
                                <td>{{ ($feeTypes->currentPage() - 1) * $feeTypes->perPage() + $loop->iteration }}</td>
                                <td class="font-weight-bold">{{ $feeType->name }}</td>
                                <td>
                                    @if($feeType->systemAccount)
                                        {{ $feeType->systemAccount->code }} - {{ $feeType->systemAccount->name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-info btn-view-fee" title="View" data-id="{{ $feeType->id }}">
                                            <i class="mdi mdi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary btn-edit-fee" title="Edit" 
                                            data-id="{{ $feeType->id }}"
                                            data-name="{{ $feeType->name }}"
                                            data-account="{{ $feeType->account }}"
                                            data-isactive="{{ $feeType->isactive }}"
                                            data-required_disbursement="{{ $feeType->required_disbursement }}">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-fee" title="Delete" 
                                            data-id="{{ $feeType->id }}"
                                            data-name="{{ $feeType->name }}">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="mdi mdi-cash-multiple mdi-48px"></i>
                                        <p class="mt-2">No fee types found</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($feeTypes->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Showing {{ $feeTypes->firstItem() ?? 0 }} to {{ $feeTypes->lastItem() ?? 0 }} of {{ $feeTypes->total() }} entries
                    </div>
                    <div>
                        {{ $feeTypes->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Fee Type Modal -->
<div class="modal fade" id="addFeeTypeModal" tabindex="-1" aria-labelledby="addFeeTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="addFeeTypeModalLabel">Add Fee Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFeeTypeForm">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label" style="color: #000;">Fee Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="account" class="form-label" style="color: #000;">Fees Account <span class="text-danger">*</span></label>
                        <select class="form-control" id="account" name="account" required style="background-color: white; color: #000;">
                            <option value="">Select Fees Account</option>
                            @foreach($systemAccounts as $acc)
                                <option value="{{ $acc->Id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="isactive" name="isactive" value="1" checked>
                            <label class="form-check-label" for="isactive" style="color: #000;">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="required_disbursement" name="required_disbursement" value="1">
                            <label class="form-check-label" for="required_disbursement" style="color: #000;">
                                Required at Disbursement
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Add Fee Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Fee Type Modal -->
<div class="modal fade" id="editFeeTypeModal" tabindex="-1" aria-labelledby="editFeeTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="editFeeTypeModalLabel">Edit Fee Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFeeTypeForm">
                @csrf
                <input type="hidden" id="edit_fee_id" name="fee_id">
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="edit_name" class="form-label" style="color: #000;">Fee Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_account" class="form-label" style="color: #000;">Fees Account <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_account" name="account" required style="background-color: white; color: #000;">
                            <option value="">Select Fees Account</option>
                            @foreach($systemAccounts as $acc)
                                <option value="{{ $acc->Id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_isactive" name="isactive" value="1">
                            <label class="form-check-label" for="edit_isactive" style="color: #000;">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_required_disbursement" name="required_disbursement" value="1">
                            <label class="form-check-label" for="edit_required_disbursement" style="color: #000;">
                                Required at Disbursement
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Update Fee Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Fee Type Modal -->
<div class="modal fade" id="viewFeeTypeModal" tabindex="-1" aria-labelledby="viewFeeTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0dcaf0; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="viewFeeTypeModalLabel">View Fee Type Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: white;">
                <div id="viewFeeTypeContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function updatePerPage(value) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', value);
        window.location = url;
    }

    // ============= ADD FEE TYPE =============
    document.getElementById('addFeeTypeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/admin/settings/fees-products', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
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
                    text: data.message || 'Failed to create fee type'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while creating the fee type'
            });
        });
    });

    // ============= EDIT FEE TYPE =============
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-fee')) {
            e.preventDefault();
            const button = e.target.closest('.btn-edit-fee');
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const account = button.getAttribute('data-account');
            const isactive = button.getAttribute('data-isactive');
            const required_disbursement = button.getAttribute('data-required_disbursement');
            
            document.getElementById('edit_fee_id').value = id || '';
            document.getElementById('edit_name').value = name || '';
            document.getElementById('edit_account').value = account || '';
            document.getElementById('edit_isactive').checked = isactive == '1';
            document.getElementById('edit_required_disbursement').checked = required_disbursement == '1';
            
            new bootstrap.Modal(document.getElementById('editFeeTypeModal')).show();
        }
    });

    document.getElementById('editFeeTypeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const feeId = document.getElementById('edit_fee_id').value;
        const formData = new FormData(this);
        
        fetch(`/admin/settings/fees-products/update/${feeId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
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
                    text: data.message || 'Failed to update fee type'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while updating the fee type'
            });
        });
    });

    // ============= VIEW FEE TYPE =============
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-view-fee')) {
            e.preventDefault();
            const button = e.target.closest('.btn-view-fee');
            const feeId = button.getAttribute('data-id');
            
            const viewContent = document.getElementById('viewFeeTypeContent');
            viewContent.innerHTML = '<div class="text-center py-4"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>';
            
            new bootstrap.Modal(document.getElementById('viewFeeTypeModal')).show();
            
            const url = `/admin/settings/fees-products/view?id=${feeId}`;
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.feeType) {
                    const feeType = data.feeType;
                    const accountDisplay = feeType.system_account 
                        ? `${feeType.system_account.code} - ${feeType.system_account.name}` 
                        : 'N/A';
                    const html = `
                        <div class="mb-3" style="background-color: #ffffff; color: #333333;">
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Name:</strong> ${feeType.name || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Fees Account:</strong> ${accountDisplay}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Status:</strong> <span class="badge ${feeType.isactive ? 'badge-success' : 'badge-danger'}">${feeType.isactive ? 'Active' : 'Inactive'}</span></p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Required at Disbursement:</strong> ${feeType.required_disbursement ? 'Yes' : 'No'}</p>
                        </div>
                    `;
                    viewContent.innerHTML = html;
                } else {
                    viewContent.innerHTML = '<div class="alert alert-danger">Failed to load fee type details</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                viewContent.innerHTML = '<div class="alert alert-danger">Failed to load fee type details. ' + error.message + '</div>';
            });
        }
    });

    // ============= DELETE FEE TYPE =============
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete-fee')) {
            e.preventDefault();
            const button = e.target.closest('.btn-delete-fee');
            const feeId = button.getAttribute('data-id');
            const feeName = button.getAttribute('data-name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete fee type "${feeName}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/settings/fees-products/delete/${feeId}`, {
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
                                text: data.message || 'Failed to delete fee type'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the fee type'
                        });
                    });
                }
            });
        }
    });
</script>
@endpush
@endsection
