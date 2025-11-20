@extends('layouts.admin')

@section('title', 'School Management')

@section('content')
<!-- Header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3 class="font-weight-bold" style="color: #000000;">School Management</h3>
                                <p class="text-muted mb-0">Manage all registered schools</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="mdi mdi-school mdi-36px text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" style="color: #000000;">Total Schools</h6>
                                        <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ $schools->total() }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="mdi mdi-clock-outline mdi-36px text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" style="color: #000000;">Pending</h6>
                                        <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ App\Models\School::where('status', 'pending')->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="mdi mdi-check-circle mdi-36px text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" style="color: #000000;">Approved</h6>
                                        <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ App\Models\School::where('status', 'approved')->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="mdi mdi-close-circle mdi-36px text-danger"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" style="color: #000000;">Rejected</h6>
                                        <h3 class="mb-0 font-weight-bold" style="color: #000000;">{{ App\Models\School::where('status', 'rejected')->count() }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schools Table -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title" style="color: #000000;">All Schools</h4>
                                
                                <!-- Search Bar -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="searchSchool" placeholder="Search schools...">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" id="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="suspended">Suspended</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr style="color: #000000;">
                                                <th>ID</th>
                                                <th>School Name</th>
                                                <th>Type</th>
                                                <th>District</th>
                                                <th>Contact Person</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Assessment</th>
                                                <th>Registered</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="schoolsTableBody">
                                            @forelse($schools as $school)
                                                <tr style="color: #000000;">
                                                    <td>{{ $school->id }}</td>
                                                    <td><strong>{{ $school->school_name }}</strong></td>
                                                    <td><span class="badge bg-info">{{ $school->school_type }}</span></td>
                                                    <td>{{ $school->district }}</td>
                                                    <td>{{ $school->contact_person }}</td>
                                                    <td>{{ $school->email }}</td>
                                                    <td>
                                                        @if($school->status == 'pending')
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        @elseif($school->status == 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                        @elseif($school->status == 'suspended')
                                                            <span class="badge bg-secondary">Suspended</span>
                                                        @else
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($school->assessment_complete)
                                                            <span class="badge bg-success"><i class="mdi mdi-check"></i> Complete</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark"><i class="mdi mdi-clock"></i> Incomplete</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $school->created_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <!-- View Button -->
                                                            <a href="{{ route('admin.schools.show', $school) }}" 
                                                               class="btn btn-sm btn-info" 
                                                               title="View Details"
                                                               data-bs-toggle="tooltip">
                                                                <i class="mdi mdi-eye"></i>
                                                            </a>

                                                            <!-- Edit Button -->
                                                            <a href="{{ route('admin.schools.edit', $school) }}" 
                                                               class="btn btn-sm btn-primary" 
                                                               title="Edit"
                                                               data-bs-toggle="tooltip">
                                                                <i class="mdi mdi-pencil"></i>
                                                            </a>

                                                            <!-- Complete Assessment Button (for incomplete assessments) -->
                                                            @if(!$school->assessment_complete)
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-warning" 
                                                                        title="Send Assessment Link"
                                                                        data-bs-toggle="tooltip"
                                                                        onclick="copyAssessmentLink('{{ $school->email }}', '{{ route('school.complete-assessment') }}')">
                                                                    <i class="mdi mdi-clipboard-check"></i>
                                                                </button>
                                                            @endif

                                                            @if($school->status == 'pending')
                                                                <!-- Approve Button -->
                                                                <form action="{{ route('admin.schools.approve', $school) }}" method="POST" class="d-inline approve-form">
                                                                    @csrf
                                                                    <button type="submit" 
                                                                            class="btn btn-sm btn-success" 
                                                                            title="Approve"
                                                                            data-bs-toggle="tooltip">
                                                                        <i class="mdi mdi-check"></i>
                                                                    </button>
                                                                </form>

                                                                <!-- Reject Button -->
                                                                <a href="{{ route('admin.schools.show', $school) }}?action=reject" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   title="Reject"
                                                                   data-bs-toggle="tooltip">
                                                                    <i class="mdi mdi-close"></i>
                                                                </a>
                                                            @endif

                                                            @if($school->status == 'approved')
                                                                <!-- Suspend Button -->
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-warning" 
                                                                        title="Suspend"
                                                                        data-bs-toggle="tooltip"
                                                                        onclick="if(confirm('Are you sure you want to suspend this school?')) { window.location.href='{{ route('admin.schools.show', $school) }}?action=suspend'; }">
                                                                    <i class="mdi mdi-pause"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center py-4">
                                                        <i class="mdi mdi-information mdi-48px text-muted"></i>
                                                        <p class="text-muted mb-0">No schools registered yet</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $schools->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Action buttons styling */
    .d-flex.gap-1 {
        gap: 0.25rem !important;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .btn-sm i {
        font-size: 1rem;
    }
    
    /* Ensure buttons stay in one line */
    td .d-flex {
        flex-wrap: nowrap;
    }
</style>

<script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Schools page loaded');
        
        // Handle approve form submissions with confirmation
        document.querySelectorAll('.approve-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (confirm('Are you sure you want to approve this school?\n\nThis will:\n- Activate the school account\n- Allow school users to login\n- Grant access to all features')) {
                    this.submit();
                }
            });
        });
        
        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });

    // Search functionality
    document.getElementById('searchSchool').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#schoolsTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Status filter
    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value.toLowerCase();
        const rows = document.querySelectorAll('#schoolsTableBody tr');
        
        rows.forEach(row => {
            if (status === '') {
                row.style.display = '';
            } else {
                const statusBadge = row.querySelector('.badge');
                if (statusBadge && statusBadge.textContent.toLowerCase().includes(status)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });

    // Copy assessment link
    function copyAssessmentLink(email, baseUrl) {
        const link = baseUrl;
        const message = `Dear School Administrator,\n\nYour school registration has been saved but the assessment is incomplete. Please complete your assessment using this link:\n\n${link}\n\nYou will need to enter your registered email (${email}) to continue.\n\nThank you!`;
        
        // Copy to clipboard
        navigator.clipboard.writeText(message).then(function() {
            alert('Assessment link copied to clipboard!\n\nYou can send this to the school:\n\n' + message);
        }).catch(function(err) {
            // Fallback if clipboard API fails
            prompt('Assessment Link (Copy this):', link);
        });
    }
</script>
@endsection
