@extends('layouts.admin')

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
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgencyModal">
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
                            <td>AGN{{ str_pad($agency->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $agency->addedBy->name ?? 'N/A' }}</td>
                            <td>{{ $agency->addedBy->email ?? 'N/A' }}</td>
                            <td>{{ $agency->addedBy->phone ?? 'N/A' }}</td>
                            <td>{{ $agency->addedBy->email ?? 'N/A' }}</td>
                            <td>
                                @if($agency->isActive())
                                    <span class="status-badge status-active">Active</span>
                                @else
                                    <span class="status-badge status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary" title="Edit" data-id="{{ $agency->id }}">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" title="View" data-id="{{ $agency->id }}">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Delete" data-id="{{ $agency->id }}">
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAgencyModalLabel">Add New Agency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAgencyForm" class="form-validate">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agency_name" class="form-label">Agency Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="agency_name" name="agency_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="agency_code" class="form-label">Agency Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="agency_code" name="agency_code" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="location" name="location" rows="3" required placeholder="Enter agency address/location"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter agency description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Agency</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable with modern styling
    $('#agenciesTable').DataTable({
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

    // Handle form submission
    $('#addAgencyForm').on('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        if (this.checkValidity()) {
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.html('<i class="mdi mdi-loading mdi-spin"></i> Saving...').prop('disabled', true);
            
            // Simulate API call
            setTimeout(function() {
                // Reset form and close modal
                $('#addAgencyForm')[0].reset();
                $('#addAgencyModal').modal('hide');
                submitBtn.html('Save Agency').prop('disabled', false);
                
                // Show success message
                alert('Agency added successfully!');
            }, 1500);
        } else {
            // Show validation errors
            $(this).addClass('was-validated');
        }
    });

    // Search functionality
    $('#agencySearch').on('keyup', function() {
        $('#agenciesTable').DataTable().search(this.value).draw();
    });

    // Action button handlers
    $(document).on('click', '.btn-outline-primary', function() {
        alert('Edit functionality to be implemented');
    });

    $(document).on('click', '.btn-outline-info', function() {
        alert('View functionality to be implemented');
    });

    $(document).on('click', '.btn-outline-danger', function() {
        if (confirm('Are you sure you want to delete this agency?')) {
            alert('Delete functionality to be implemented');
        }
    });
});
</script>
@endsection