@extends('layouts.admin')

@section('content')
@push('styles')
<style>
    .modal-content { background-color: #ffffff !important; }
    .modal-body { background-color: #ffffff !important; color: #000000 !important; }
    .modal-header { border-bottom: 1px solid #dee2e6; }
    .modal-footer { border-top: 1px solid #dee2e6; }
    label { color: #000000 !important; }
    input, select, textarea { background-color: #ffffff !important; color: #000000 !important; }
</style>
@endpush

<div class="main-panel">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Branch Management</h3>
                        <h6 class="font-weight-normal mb-0">Branches that have been added to the system</h6>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="justify-content-end d-flex">
                            <button type="button" class="btn btn-primary" id="addBranchBtn">
                                <i class="mdi mdi-plus"></i> Add Branch
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern Table Container -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-header-left">
                    <h4 class="table-title">All Branches</h4>
                    <p class="table-subtitle">Manage and view all registered branches</p>
                </div>
                <div class="table-header-right">
                    <div class="table-search">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" placeholder="Search branches..." id="branchSearch">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table modern-table" id="branchesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branch Name</th>
                            <th>Branch Code</th>
                            <th>Location</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $branch->name }}</td>
                            <td>{{ $branch->code ?? 'BRN' . str_pad($branch->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $branch->address ?? 'N/A' }}</td>
                            <td>{{ $branch->phone ?? 'N/A' }}</td>
                            <td>{{ $branch->email ?? 'N/A' }}</td>
                            <td>{{ $branch->country ?? 'N/A' }}</td>
                            <td>
                                @if($branch->is_active)
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-branch" title="Edit" 
                                        data-id="{{ $branch->id }}"
                                        data-name="{{ $branch->name }}"
                                        data-address="{{ $branch->address }}"
                                        data-phone="{{ $branch->phone }}"
                                        data-email="{{ $branch->email }}"
                                        data-country="{{ $branch->country }}"
                                        data-description="{{ $branch->description }}"
                                        data-status="{{ $branch->is_active ? 'active' : 'inactive' }}">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info btn-view-branch" title="View" data-id="{{ $branch->id }}">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-branch" title="Delete" data-id="{{ $branch->id }}">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="mdi mdi-information-outline"></i>
                                    No branches found. Click "Add Branch" to get started.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="addBranchModalLabel">Add Branch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addBranchForm">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="branch_name" class="form-label" style="color: #000;">Branch Name</label>
                        <input type="text" class="form-control" id="branch_name" name="branch_name" required style="background-color: white; color: #000;">
                        <small class="form-text text-muted" style="color: #666 !important;">Branch code will be auto-generated</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="address" class="form-label" style="color: #000;">Address/Location</label>
                        <input type="text" class="form-control" id="address" name="address" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="phone" class="form-label" style="color: #000;">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="email" class="form-label" style="color: #000;">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="country" class="form-label" style="color: #000;">Country</label>
                        <input type="text" class="form-control" id="country" name="country" style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="status" class="form-label" style="color: #000;">Status</label>
                        <select class="form-control" id="status" name="status" required style="background-color: white; color: #000;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label" style="color: #000;">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" style="background-color: white; color: #000;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Submit to Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="editBranchModalLabel">Edit Branch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editBranchForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_branch_id" name="branch_id">
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="edit_branch_name" class="form-label" style="color: #000;">Branch Name</label>
                        <input type="text" class="form-control" id="edit_branch_name" name="branch_name" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_address" class="form-label" style="color: #000;">Address/Location</label>
                        <input type="text" class="form-control" id="edit_address" name="address" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_phone" class="form-label" style="color: #000;">Phone Number</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone" style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_email" class="form-label" style="color: #000;">Email Address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_country" class="form-label" style="color: #000;">Country</label>
                        <input type="text" class="form-control" id="edit_country" name="country" style="background-color: white; color: #000;">
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_status" class="form-label" style="color: #000;">Status</label>
                        <select class="form-control" id="edit_status" name="status" required style="background-color: white; color: #000;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_description" class="form-label" style="color: #000;">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2" style="background-color: white; color: #000;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Branch Modal -->
<div class="modal fade" id="viewBranchModal" tabindex="-1" aria-labelledby="viewBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0dcaf0; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="viewBranchModalLabel">View Branch Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewBranchContent" style="background-color: white;">
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
console.log('Branch script loading...');

// Use setTimeout to ensure DOM and all libraries are loaded
setTimeout(function() {
    console.log('Initializing branch management...');
    
    // CSRF Token Setup
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
    console.log('CSRF Token:', csrfToken ? 'Found' : 'Missing');

    // ============= ADD BRANCH =============
    const addBranchBtn = document.getElementById('addBranchBtn');
    if (addBranchBtn) {
        addBranchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add branch button clicked');
            new bootstrap.Modal(document.getElementById('addBranchModal')).show();
        });
    }

    const addForm = document.getElementById('addBranchForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Add form submitted');
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            console.log('Form data:', data);
            
            fetch('{{ url("admin/settings/branches") }}', {
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
                console.log('Response:', data);
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
                        text: data.message || 'Failed to create branch'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while creating the branch'
                });
            });
        });
    }

    // ============= EDIT BRANCH =============
    const editButtons = document.querySelectorAll('.btn-edit-branch');
    console.log('Edit buttons found:', editButtons.length);
    
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Edit button clicked');
            
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const address = this.getAttribute('data-address');
            const phone = this.getAttribute('data-phone');
            const email = this.getAttribute('data-email');
            const country = this.getAttribute('data-country');
            const description = this.getAttribute('data-description');
            const status = this.getAttribute('data-status');
            
            document.getElementById('edit_branch_id').value = id || '';
            document.getElementById('edit_branch_name').value = name || '';
            document.getElementById('edit_address').value = address || '';
            document.getElementById('edit_phone').value = phone || '';
            document.getElementById('edit_email').value = email || '';
            document.getElementById('edit_country').value = country || '';
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_status').value = status || 'active';
            
            new bootstrap.Modal(document.getElementById('editBranchModal')).show();
        });
    });

    const editForm = document.getElementById('editBranchForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Edit form submitted');
            
            const branchId = document.getElementById('edit_branch_id').value;
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (key !== 'branch_id' && key !== '_method') {
                    data[key] = value;
                }
            });
            
            console.log('Update data:', data);
            
            const url = '{{ url("admin/settings/branches") }}' + '/' + branchId;
            
            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
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
                        text: data.message || 'Failed to update branch'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while updating the branch'
                });
            });
        });
    }

    // ============= VIEW BRANCH =============
    const viewButtons = document.querySelectorAll('.btn-view-branch');
    console.log('View buttons found:', viewButtons.length);
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('View button clicked');
            const branchId = this.getAttribute('data-id');
            
            const viewContent = document.getElementById('viewBranchContent');
            viewContent.innerHTML = '<div class="text-center py-4"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>';
            
            new bootstrap.Modal(document.getElementById('viewBranchModal')).show();
            
            fetch('{{ url("admin/settings/branches") }}/' + branchId, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);
                if (data.success && data.branch) {
                    const branch = data.branch;
                    const html = `
                        <div class="mb-3" style="background-color: #ffffff; color: #333333;">
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Branch Name:</strong> ${branch.name || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Branch Code:</strong> ${branch.code || 'BRN' + String(branch.id).padStart(3, '0')}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Address:</strong> ${branch.address || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Phone:</strong> ${branch.phone || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Email:</strong> ${branch.email || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Country:</strong> ${branch.country || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Status:</strong> <span class="badge ${branch.is_active ? 'bg-success' : 'bg-danger'}">${branch.is_active ? 'Active' : 'Inactive'}</span></p>
                            ${branch.description ? '<p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Description:</strong><br>' + branch.description + '</p>' : ''}
                        </div>
                    `;
                    viewContent.innerHTML = html;
                } else {
                    viewContent.innerHTML = '<div class="alert alert-danger">Failed to load branch details</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                viewContent.innerHTML = '<div class="alert alert-danger">Failed to load branch details: ' + error.message + '</div>';
            });
        });
    });

    // ============= DELETE BRANCH =============
    const deleteButtons = document.querySelectorAll('.btn-delete-branch');
    console.log('Delete buttons found:', deleteButtons.length);
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const branchId = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ url("admin/settings/branches") }}' + '/' + branchId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
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
                                text: data.message || 'Failed to delete branch'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the branch'
                        });
                    });
                }
            });
        });
    });

}, 500);
</script>
@endpush
