@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    /* Force White Background on ALL Modals - Override Dark Theme */
    .modal {
        z-index: 9999 !important;
    }
    
    .modal-dialog {
        z-index: 10000 !important;
    }
    
    .modal-content {
        background-color: #ffffff !important;
        color: #333333 !important;
        border: 1px solid #dee2e6 !important;
    }
    
    .modal-header {
        background-color: #0d6efd !important;
        color: #ffffff !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    
    .modal-header.bg-info {
        background-color: #0dcaf0 !important;
    }
    
    .modal-body {
        background-color: #ffffff !important;
        color: #333333 !important;
        padding: 20px !important;
    }
    
    .modal-body p {
        color: #333333 !important;
    }
    
    .modal-body strong {
        color: #000000 !important;
    }
    
    .modal-footer {
        background-color: #ffffff !important;
        border-top: 1px solid #dee2e6 !important;
    }
    
    /* Form Elements */
    .form-label {
        color: #333333 !important;
        font-weight: 500 !important;
        margin-bottom: 0.5rem !important;
        display: block !important;
    }
    
    .form-control {
        background-color: #ffffff !important;
        color: #333333 !important;
        border: 1px solid #ced4da !important;
    }
    
    .form-control:focus {
        background-color: #ffffff !important;
        color: #333333 !important;
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }
    
    select.form-control,
    textarea.form-control {
        background-color: #ffffff !important;
        color: #333333 !important;
    }
    
    .form-control option {
        background-color: #ffffff !important;
        color: #333333 !important;
    }
    
    /* Alert in Modal */
    .alert-danger {
        background-color: #f8d7da !important;
        color: #721c24 !important;
        border-color: #f5c6cb !important;
    }
    
    .btn-close-white {
        filter: brightness(0) invert(1);
    }
    
    /* Loading spinner */
    .mdi-loading {
        color: #333333 !important;
    }
</style>
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Agency Management</h3>
                        <h6 class="font-weight-normal mb-0">Agencies that have been added to the system</h6>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="justify-content-end d-flex">
                            <button type="button" class="btn btn-primary" id="addAgencyBtn">
                                <i class="mdi mdi-plus"></i> Add Agency
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
                    <h4 class="table-title">All Agencies</h4>
                    <p class="table-subtitle">Manage and view all registered agencies</p>
                </div>
                <div class="table-header-right">
                    <button class="btn btn-sm btn-outline-secondary me-2">
                        <i class="mdi mdi-export"></i> Export
                    </button>
                    <div class="table-search">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" placeholder="Search agencies..." id="agencySearch">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table modern-table" id="agenciesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Agency Name</th>
                            <th>Agency Code</th>
                            <th>Location</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agencies as $agency)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $agency->name }}</td>
                            <td>{{ $agency->code ?? 'AGN' . str_pad($agency->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $agency->location ?? 'N/A' }}</td>
                            <td>{{ $agency->contact_person ?? 'N/A' }}</td>
                            <td>{{ $agency->phone ?? 'N/A' }}</td>
                            <td>{{ $agency->email ?? 'N/A' }}</td>
                            <td>
                                @if($agency->isActive())
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-agency" title="Edit" 
                                            data-id="{{ $agency->id }}"
                                            data-name="{{ $agency->name }}"
                                            data-code="{{ $agency->code }}"
                                            data-contact="{{ $agency->contact_person }}"
                                            data-phone="{{ $agency->phone }}"
                                            data-email="{{ $agency->email }}"
                                            data-location="{{ $agency->location }}"
                                            data-description="{{ $agency->description }}"
                                            data-status="{{ $agency->isActive() ? 'active' : 'inactive' }}">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info btn-view-agency" title="View" data-id="{{ $agency->id }}">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-agency" title="Delete" data-id="{{ $agency->id }}">
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
                                    No agencies found. Click "Add Agency" to get started.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-pagination">
                <div class="pagination-info">
                    Showing {{ $agencies->count() }} of {{ $agencies->count() }} entries
                </div>
                <nav>
                    <ul class="pagination pagination-modern">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" aria-label="Previous">
                                <i class="mdi mdi-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item active">
                            <a class="page-link" href="#">1</a>
                        </li>
                        <li class="page-item disabled">
                            <a class="page-link" href="#" aria-label="Next">
                                <i class="mdi mdi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Add Agency Modal -->
<div class="modal fade" id="addAgencyModal" tabindex="-1" aria-labelledby="addAgencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="addAgencyModalLabel">Add Agency Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAgencyForm">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="agency_name" class="form-label" style="color: #000;">Agency Name</label>
                        <input type="text" class="form-control" id="agency_name" name="agency_name" required style="background-color: white; color: #000;">
                        <small class="form-text text-muted" style="color: #666 !important;">Agency code will be auto-generated</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="contact_person" class="form-label" style="color: #000;">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" style="background-color: white; color: #000;">
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
                        <label for="location" class="form-label" style="color: #000;">Location</label>
                        <input type="text" class="form-control" id="location" name="location" style="background-color: white; color: #000;">
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

<!-- Edit Agency Modal -->
<div class="modal fade" id="editAgencyModal" tabindex="-1" aria-labelledby="editAgencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="editAgencyModalLabel">Edit Agency Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAgencyForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_agency_id" name="agency_id">
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="edit_agency_name" class="form-label" style="color: #000;">Agency Name</label>
                        <input type="text" class="form-control" id="edit_agency_name" name="agency_name" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_contact_person" class="form-label" style="color: #000;">Contact Person</label>
                        <input type="text" class="form-control" id="edit_contact_person" name="contact_person" style="background-color: white; color: #000;">
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
                        <label for="edit_location" class="form-label" style="color: #000;">Location</label>
                        <input type="text" class="form-control" id="edit_location" name="location" style="background-color: white; color: #000;">
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
                    <button type="submit" class="btn btn-primary">Submit to Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Agency Modal -->
<div class="modal fade" id="viewAgencyModal" tabindex="-1" aria-labelledby="viewAgencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0dcaf0; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="viewAgencyModalLabel">View Agency Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewAgencyContent" style="background-color: white;">
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
console.log('Agency script loading...');

// Use setTimeout to ensure DOM and all libraries are loaded
setTimeout(function() {
    console.log('Initializing agency management...');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    console.log('jQuery available:', typeof $ !== 'undefined');
    
    // CSRF Token Setup
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
    console.log('CSRF Token:', csrfToken ? 'Found' : 'Missing');
    
    if (!csrfToken) {
        console.error('CSRF token not found! Make sure <meta name="csrf-token"> exists in layout.');
    }
    
    // Setup jQuery AJAX with CSRF token
    if (typeof $ !== 'undefined') {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }

    // Initialize DataTable if jQuery is available
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        try {
            const table = $('#agenciesTable').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "pageLength": 10,
                "dom": '<"top"f>rt<"bottom"p><"clear">',
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search agencies...",
                    "paginate": {
                        "previous": "<i class='mdi mdi-chevron-left'></i>",
                        "next": "<i class='mdi mdi-chevron-right'></i>"
                    }
                }
            });

            // Search functionality
            $('#agencySearch').on('keyup', function() {
                table.search(this.value).draw();
            });
        } catch(e) {
            console.log('DataTable initialization skipped:', e);
        }
    }

    // ============= ADD AGENCY BUTTON =============
    const addAgencyBtn = document.getElementById('addAgencyBtn');
    if (addAgencyBtn) {
        console.log('Add Agency button found');
        addAgencyBtn.addEventListener('click', function() {
            console.log('Add Agency button clicked - opening modal');
            const addModal = document.getElementById('addAgencyModal');
            if (addModal) {
                if (typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(addModal).show();
                } else {
                    console.error('Bootstrap is not loaded!');
                }
            } else {
                console.error('Add Agency modal not found!');
            }
        });
    } else {
        console.error('Add Agency button not found!');
    }
    
    // ============= CREATE AGENCY =============
    const addForm = document.getElementById('addAgencyForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Add form submitted');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('{{ route("admin.settings.agencies.store") }}', {
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
                    addForm.reset();
                    bootstrap.Modal.getInstance(document.getElementById('addAgencyModal')).hide();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Agency added successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to add agency');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'Failed to add agency'
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // ============= EDIT AGENCY =============
    const editButtons = document.querySelectorAll('.btn-edit-agency');
    console.log('Edit buttons found:', editButtons.length);
    
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Edit button clicked');
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');
            const contact = this.getAttribute('data-contact');
            const phone = this.getAttribute('data-phone');
            const email = this.getAttribute('data-email');
            const location = this.getAttribute('data-location');
            const description = this.getAttribute('data-description');
            const status = this.getAttribute('data-status');
            
            document.getElementById('edit_agency_id').value = id || '';
            document.getElementById('edit_agency_name').value = name || '';
            document.getElementById('edit_contact_person').value = contact || '';
            document.getElementById('edit_phone').value = phone || '';
            document.getElementById('edit_email').value = email || '';
            document.getElementById('edit_location').value = location || '';
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_status').value = status || 'active';
            
            new bootstrap.Modal(document.getElementById('editAgencyModal')).show();
        });
    });

    const editForm = document.getElementById('editAgencyForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Edit form submitted');
            
            const agencyId = document.getElementById('edit_agency_id').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('{{ url("admin/settings/agencies") }}/' + agencyId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editAgencyModal')).hide();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Agency updated successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to update agency');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'Failed to update agency'
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // ============= VIEW AGENCY =============
    const viewButtons = document.querySelectorAll('.btn-view-agency');
    console.log('View buttons found:', viewButtons.length);
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('View button clicked');
            const agencyId = this.getAttribute('data-id');
            
            const viewContent = document.getElementById('viewAgencyContent');
            viewContent.innerHTML = '<div class="text-center py-4"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>';
            
            new bootstrap.Modal(document.getElementById('viewAgencyModal')).show();
            
            console.log('Fetching agency details for ID:', agencyId);
            
            fetch('{{ url("admin/settings/agencies") }}/' + agencyId, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success && data.agency) {
                    const agency = data.agency;
                    const html = `
                        <div class="mb-3" style="background-color: #ffffff; color: #333333;">
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Agency Name:</strong> ${agency.name || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Agency Code:</strong> ${agency.code || 'AGN' + String(agency.id).padStart(3, '0')}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Contact Person:</strong> ${agency.contact_person || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Phone:</strong> ${agency.phone || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Email:</strong> ${agency.email || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Location:</strong> ${agency.location || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Status:</strong> <span class="badge ${agency.isactive ? 'bg-success' : 'bg-danger'}">${agency.isactive ? 'Active' : 'Inactive'}</span></p>
                            ${agency.description ? '<p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Description:</strong><br>' + agency.description + '</p>' : ''}
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Total Branches:</strong> ${agency.branches ? agency.branches.length : 0}</p>
                        </div>
                    `;
                    viewContent.innerHTML = html;
                } else {
                    throw new Error(data.message || 'Failed to load agency details');
                }
            })
            .catch(error => {
                console.error('View Error:', error);
                viewContent.innerHTML = '<div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24;">Failed to load agency details: ' + error.message + '</div>';
            });
        });
    });

    // ============= DELETE AGENCY =============
    const deleteButtons = document.querySelectorAll('.btn-delete-agency');
    console.log('Delete buttons found:', deleteButtons.length);
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Delete button clicked');
            const agencyId = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ url("admin/settings/agencies") }}/' + agencyId, {
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
                                text: data.message || 'Agency deleted successfully!',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Failed to delete agency');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: error.message || 'Failed to delete agency'
                        });
                    });
                }
            });
        });
    });
    
    console.log('Agency management initialized successfully');
}, 500); // Wait 500ms for all libraries to load
</script>
@endpush