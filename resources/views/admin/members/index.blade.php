@extends('layouts.admin')

@section('title', 'Member Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-account-multiple"></i> Member Management
                    </h3>
                    <a href="{{ route('admin.members.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add New Member
                    </a>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['total'] ?? 0 }}</h4>
                                            <small>Total Members</small>
                                        </div>
                                        <i class="mdi mdi-account-multiple mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['approved'] ?? 0 }}</h4>
                                            <small>Approved Members</small>
                                        </div>
                                        <i class="mdi mdi-check-circle mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['pending'] ?? 0 }}</h4>
                                            <small>Pending Approval</small>
                                        </div>
                                        <i class="mdi mdi-clock mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['rejected'] ?? 0 }}</h4>
                                            <small>Rejected Members</small>
                                        </div>
                                        <i class="mdi mdi-account-cancel mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" action="{{ route('admin.members.index') }}" class="row g-3">
                                        <div class="col-md-3">
                                            <select name="status" class="form-select">
                                                <option value="">All Status</option>
                                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="branch_id" class="form-select">
                                                <option value="">All Branches</option>
                                                @foreach(\App\Models\Branch::active()->get() as $branch)
                                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" 
                                                       placeholder="Search members..." value="{{ request('search') }}">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="mdi mdi-magnify"></i>
                                                </button>
                                                <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary">
                                                    <i class="mdi mdi-refresh"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modern Members Table -->
                    <div class="table-container">
                        <!-- Table Header with Search and Controls -->
                        <div class="table-header">
                            <div class="table-search">
                                <input type="text" placeholder="Search members..." id="memberSearch" value="{{ request('search') }}">
                            </div>
                            <div class="table-actions">
                                <a href="{{ route('admin.members.create') }}" class="export-btn">
                                    <i class="mdi mdi-plus"></i>
                                    Add Member
                                </a>
                                <div class="table-show-entries">
                                    Show
                                    <select id="entriesPerPage">
                                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    entries
                                </div>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="table-responsive">
                            <table class="modern-table" id="membersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Branch</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($members as $member)
                                        <tr data-member-id="{{ $member->id }}">
                                            <td>{{ $member->id }}</td>
                                            <td>
                                                <span class="account-number">{{ $member->code ?? 'N/A' }}</span>
                                            </td>
                                            <td>{{ $member->fname }} {{ $member->lname }}</td>
                                            <td>{{ $member->contact }}</td>
                                            <td>{{ $member->branch->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="status-badge status-individual">
                                                    {{ $member->member_type == 1 ? 'Group' : 'Individual' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $member->status === 'approved' || $member->verified ? 'status-verified' : 'status-not-verified' }}">
                                                    {{ $member->status === 'approved' || $member->verified ? 'Active' : ucfirst($member->status ?? 'Inactive') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.members.show', $member) }}" class="btn-modern btn-view">View</a>
                                                    <a href="{{ route('admin.members.edit', $member) }}" class="btn-modern btn-view">Edit</a>
                                                    <button onclick="deleteMember({{ $member->id }})" class="btn-modern btn-delete">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="table-empty">
                                                <i class="mdi mdi-account-search mdi-72px"></i>
                                                <h5 class="mt-3 mb-2">No members found</h5>
                                                <p class="text-muted mb-3">There are no members matching your current filters.</p>
                                                <a href="{{ route('admin.members.create') }}" class="btn btn-primary">
                                                    <i class="mdi mdi-plus me-2"></i>Add New Member
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Modern Pagination -->
                        <div class="modern-pagination">
                            <div class="pagination-info">
                                Showing {{ $members->firstItem() ?? 0 }} to {{ $members->lastItem() ?? 0 }} of {{ $members->total() }} entries
                            </div>
                            <div class="pagination-controls">
                                @if ($members->onFirstPage())
                                    <span class="pagination-btn" disabled>
                                        <i class="mdi mdi-chevron-left"></i>
                                        Previous
                                    </span>
                                @else
                                    <a href="{{ $members->previousPageUrl() }}" class="pagination-btn">
                                        <i class="mdi mdi-chevron-left"></i>
                                        Previous
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @foreach ($members->getUrlRange(1, $members->lastPage()) as $page => $url)
                                        @if ($page == $members->currentPage())
                                            <span class="pagination-btn active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                </div>

                                @if ($members->hasMorePages())
                                    <a href="{{ $members->nextPageUrl() }}" class="pagination-btn">
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

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3"
                                  placeholder="Enter approval notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"
                                  placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approveMember(memberId) {
    document.getElementById('approvalForm').action = `/admin/members/${memberId}/approve`;
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function rejectMember(memberId) {
    document.getElementById('rejectionForm').action = `/admin/members/${memberId}/reject`;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

// Enhanced Table Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize table enhancements
    initializeTableEnhancements();
    
    // Add row click functionality
    const tableRows = document.querySelectorAll('#membersTable tbody tr');
    tableRows.forEach(row => {
        if (!row.querySelector('td[colspan]')) { // Skip empty state row
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Don't trigger row click if clicking on buttons
                if (!e.target.closest('.btn-group') && !e.target.closest('button') && !e.target.closest('a')) {
                    const memberId = this.dataset.memberId;
                    if (memberId) {
                        window.location.href = `/admin/members/${memberId}`;
                    }
                }
            });
        }
    });
    
    // Add sortable functionality
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            const currentSort = new URLSearchParams(window.location.search).get('sort');
            const currentDirection = new URLSearchParams(window.location.search).get('direction');
            
            let newDirection = 'asc';
            if (currentSort === sortField && currentDirection === 'asc') {
                newDirection = 'desc';
            }
            
            // Update URL with sort parameters
            const url = new URL(window.location);
            url.searchParams.set('sort', sortField);
            url.searchParams.set('direction', newDirection);
            window.location.href = url.toString();
        });
    });
    
    // Highlight current sort column
    const currentSort = new URLSearchParams(window.location.search).get('sort');
    const currentDirection = new URLSearchParams(window.location.search).get('direction');
    if (currentSort) {
        const header = document.querySelector(`[data-sort="${currentSort}"]`);
        if (header) {
            header.classList.add(currentDirection === 'desc' ? 'desc' : 'asc');
        }
    }
});

function initializeTableEnhancements() {
    // Add loading animation to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.disabled) {
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i>';
                this.disabled = true;
                
                // Re-enable after 2 seconds (adjust as needed)
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                }, 2000);
            }
        });
    });
    
    // Add hover effects to badges
    document.querySelectorAll('.badge').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Add tooltip functionality (if you have Bootstrap tooltips enabled)
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Quick search functionality
function quickSearch() {
    const searchTerm = document.getElementById('quickSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#membersTable tbody tr');
    
    rows.forEach(row => {
        if (!row.querySelector('td[colspan]')) { // Skip empty state row
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

// Export functionality (optional)
function exportTable() {
    // Implementation for table export
    console.log('Export functionality can be implemented here');
}
</script>
@endpush
@endsection