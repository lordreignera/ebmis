@extends('layouts.admin')

@section('title', 'Enhanced Tables Demo')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h2 class="page-title">
                    <i class="mdi mdi-table-large me-3"></i>
                    Enhanced Tables Preview
                </h2>
                <p class="text-muted">Showcasing the new table design with improved hover effects, scrollbars, and modern styling</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 60px; height: 60px;">
                        <i class="mdi mdi-table text-white mdi-24px"></i>
                    </div>
                    <h5 class="card-title">Modern Tables</h5>
                    <p class="text-muted mb-0">Beautiful gradient headers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="bg-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 60px; height: 60px;">
                        <i class="mdi mdi-cursor-pointer text-white mdi-24px"></i>
                    </div>
                    <h5 class="card-title">Fixed Hover</h5>
                    <p class="text-muted mb-0">Readable text on hover</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="bg-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 60px; height: 60px;">
                        <i class="mdi mdi-view-list text-white mdi-24px"></i>
                    </div>
                    <h5 class="card-title">Scrollable</h5>
                    <p class="text-muted mb-0">Custom scrollbars</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="bg-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 60px; height: 60px;">
                        <i class="mdi mdi-responsive text-white mdi-24px"></i>
                    </div>
                    <h5 class="card-title">Responsive</h5>
                    <p class="text-muted mb-0">Works on all devices</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Variations -->
    <div class="row">
        <!-- Basic Enhanced Table -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="mdi mdi-table me-2"></i>
                        Main Enhanced Table with Scrolling
                    </h4>
                    <p class="card-description">Modern table with gradient header, smooth hover effects, and custom scrollbars (Fixed height with scrolling)</p>
                </div>
                <div class="card-body">
                    <div class="table-force-scroll">
                        <table class="table table-hover" id="basicTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="id" style="min-width: 80px;">
                                        <i class="mdi mdi-hash"></i> ID
                                    </th>
                                    <th class="sortable" data-sort="name" style="min-width: 200px;">
                                        <i class="mdi mdi-account"></i> User
                                    </th>
                                    <th style="min-width: 180px;">
                                        <i class="mdi mdi-email"></i> Contact
                                    </th>
                                    <th class="sortable" data-sort="role" style="min-width: 120px;">
                                        <i class="mdi mdi-account-key"></i> Role
                                    </th>
                                    <th class="sortable" data-sort="status" style="min-width: 100px;">
                                        <i class="mdi mdi-information"></i> Status
                                    </th>
                                    <th style="min-width: 120px;">
                                        <i class="mdi mdi-map-marker"></i> Location
                                    </th>
                                    <th style="min-width: 130px;">
                                        <i class="mdi mdi-calendar"></i> Joined
                                    </th>
                                    <th style="min-width: 150px;">
                                        <i class="mdi mdi-cog"></i> Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-user-id="1">
                                    <td><span class="fw-bold text-primary">#001</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 36px; height: 36px;">
                                                <span class="text-white fw-bold">J</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">John Doe</div>
                                                <small class="text-muted">Software Engineer</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-primary fw-medium">
                                                <i class="mdi mdi-phone me-1"></i>+256 777 123456
                                            </span>
                                            <small class="text-muted">
                                                <i class="mdi mdi-email me-1"></i>john@example.com
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="mdi mdi-shield-account me-1"></i>Admin
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-active"></span>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            Kampala, Uganda
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            Jan 15, 2024
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-user-id="2">
                                    <td><span class="fw-bold text-primary">#002</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 36px; height: 36px;">
                                                <span class="text-white fw-bold">A</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Alice Smith</div>
                                                <small class="text-muted">Project Manager</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-primary fw-medium">
                                                <i class="mdi mdi-phone me-1"></i>+256 777 789012
                                            </span>
                                            <small class="text-muted">
                                                <i class="mdi mdi-email me-1"></i>alice@example.com
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="mdi mdi-account-group me-1"></i>Manager
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-active"></span>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            Entebbe, Uganda
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            Feb 20, 2024
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-user-id="3">
                                    <td><span class="fw-bold text-primary">#003</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-warning rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 36px; height: 36px;">
                                                <span class="text-dark fw-bold">M</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Mike Johnson</div>
                                                <small class="text-muted">Developer</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-primary fw-medium">
                                                <i class="mdi mdi-phone me-1"></i>+256 777 345678
                                            </span>
                                            <small class="text-muted">
                                                <i class="mdi mdi-email me-1"></i>mike@example.com
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="mdi mdi-account me-1"></i>User
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-pending"></span>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            Jinja, Uganda
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            Mar 10, 2024
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Additional rows to demonstrate scrolling -->
                                <tr data-user-id="4">
                                    <td><span class="fw-bold text-primary">#004</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 36px; height: 36px;">
                                                <span class="text-white fw-bold">S</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Sarah Wilson</div>
                                                <small class="text-muted">Designer</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-primary fw-medium">
                                                <i class="mdi mdi-phone me-1"></i>+256 777 456789
                                            </span>
                                            <small class="text-muted">
                                                <i class="mdi mdi-email me-1"></i>sarah@example.com
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="mdi mdi-palette me-1"></i>Designer
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-active"></span>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            Mbarara, Uganda
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            Apr 5, 2024
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-user-id="5">
                                    <td><span class="fw-bold text-primary">#005</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-purple rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 36px; height: 36px; background-color: #6f42c1;">
                                                <span class="text-white fw-bold">D</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">David Brown</div>
                                                <small class="text-muted">Marketing Specialist</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-primary fw-medium">
                                                <i class="mdi mdi-phone me-1"></i>+256 777 567890
                                            </span>
                                            <small class="text-muted">
                                                <i class="mdi mdi-email me-1"></i>david@example.com
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <i class="mdi mdi-bullhorn me-1"></i>Marketing
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-inactive"></span>
                                        <span class="badge bg-danger">Inactive</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            Gulu, Uganda
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="mdi mdi-calendar me-1"></i>
                                            May 12, 2024
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Striped Table -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="mdi mdi-view-list me-2"></i>
                        Striped Table
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><i class="mdi mdi-hash"></i> #</th>
                                    <th><i class="mdi mdi-folder"></i> Item</th>
                                    <th><i class="mdi mdi-chart-line"></i> Value</th>
                                    <th><i class="mdi mdi-information"></i> Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Sample Item A</td>
                                    <td><span class="badge bg-success">$1,250</span></td>
                                    <td><span class="status-indicator status-active"></span>Active</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Sample Item B</td>
                                    <td><span class="badge bg-warning">$750</span></td>
                                    <td><span class="status-indicator status-pending"></span>Pending</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Sample Item C</td>
                                    <td><span class="badge bg-danger">$0</span></td>
                                    <td><span class="status-indicator status-inactive"></span>Inactive</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Small Table -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="mdi mdi-table-small me-2"></i>
                        Compact Table
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th><i class="mdi mdi-calendar"></i> Date</th>
                                    <th><i class="mdi mdi-cash"></i> Amount</th>
                                    <th><i class="mdi mdi-account"></i> User</th>
                                    <th><i class="mdi mdi-check"></i> Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Nov 7, 2024</td>
                                    <td>$500.00</td>
                                    <td>John Doe</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                </tr>
                                <tr>
                                    <td>Nov 6, 2024</td>
                                    <td>$750.00</td>
                                    <td>Alice Smith</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Nov 5, 2024</td>
                                    <td>$320.00</td>
                                    <td>Mike Johnson</td>
                                    <td><span class="badge bg-danger">Failed</span></td>
                                </tr>
                                <tr>
                                    <td>Nov 4, 2024</td>
                                    <td>$1,200.00</td>
                                    <td>Sarah Wilson</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Highlights -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="mdi mdi-star me-2"></i>
                        Enhanced Table Features
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px;">
                                    <i class="mdi mdi-palette text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Modern Gradient Headers</h6>
                                    <small class="text-muted">Beautiful gradient backgrounds with improved typography</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px;">
                                    <i class="mdi mdi-cursor-pointer text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Fixed Hover Effects</h6>
                                    <small class="text-muted">No more black background on hover - readable text always</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px;">
                                    <i class="mdi mdi-animation text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Smooth Animations</h6>
                                    <small class="text-muted">Subtle animations and micro-interactions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px;">
                                    <i class="mdi mdi-sort text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Sortable Columns</h6>
                                    <small class="text-muted">Click headers to sort data with visual indicators</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px;">
                                    <i class="mdi mdi-responsive text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Responsive Design</h6>
                                    <small class="text-muted">Perfect display on all device sizes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px;">
                                    <i class="mdi mdi-eye text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Status Indicators</h6>
                                    <small class="text-muted">Visual status dots and improved badge styling</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add row click functionality for demo
    document.querySelectorAll('table tbody tr').forEach(row => {
        if (row.dataset.userId || row.dataset.groupId) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                if (!e.target.closest('.btn-group') && !e.target.closest('button')) {
                    const id = this.dataset.userId || this.dataset.groupId;
                    console.log('Row clicked:', id);
                    // In real implementation, this would navigate to detail page
                }
            });
        }
    });

    // Add sortable functionality
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            // Remove existing sort classes
            document.querySelectorAll('.sortable').forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            
            // Add sort class
            if (this.dataset.sortDirection === 'asc') {
                this.classList.add('desc');
                this.dataset.sortDirection = 'desc';
            } else {
                this.classList.add('asc');
                this.dataset.sortDirection = 'asc';
            }
            
            console.log('Sorting by:', this.dataset.sort, this.dataset.sortDirection);
        });
    });

    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>
@endpush
@endsection