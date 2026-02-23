@extends('layouts.admin')

@section('title', 'Investors Management')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">
                    @if($type === 'international')
                        International Investors
                    @elseif($type === 'local') 
                        Local Investors
                    @else
                        All Investors
                    @endif
                </h4>
                <p class="text-muted mb-0">View and manage investor details that have been added to the system</p>
            </div>
            <div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.investments.investors', ['type' => 'all']) }}" 
                       class="btn btn-sm {{ $type === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                        All Investors
                    </a>
                    <a href="{{ route('admin.investments.investors', ['type' => 'local']) }}" 
                       class="btn btn-sm {{ $type === 'local' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Local
                    </a>
                    <a href="{{ route('admin.investments.investors', ['type' => 'international']) }}" 
                       class="btn btn-sm {{ $type === 'international' ? 'btn-primary' : 'btn-outline-primary' }}">
                        International
                    </a>
                </div>
                <a href="{{ route('admin.investments.create-investor') }}" class="btn btn-success btn-sm ml-2">
                    <i class="mdi mdi-plus"></i> Add Investor
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Total Investors</h4>
                        <h2 class="text-primary mb-2">{{ number_format($stats['total_investors']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-account-multiple icon-lg text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">International</h4>
                        <h2 class="text-success mb-2">{{ number_format($stats['international_investors']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-earth icon-lg text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Local</h4>
                        <h2 class="text-info mb-2">{{ number_format($stats['local_investors']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-home icon-lg text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Investment Value</h4>
                        <h2 class="text-warning mb-2">${{ number_format($stats['total_investment_value'], 0) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-cash-multiple icon-lg text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Search Investors</h4>
                <form method="GET" action="{{ route('admin.investments.investors') }}" class="row">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div class="col-md-6">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by name, email, or phone..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                    @if(request('search'))
                        <div class="col-md-2">
                            <a href="{{ route('admin.investments.investors', ['type' => $type]) }}" class="btn btn-secondary">Clear</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Investors Table -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Investors List</h4>
                    <span class="text-muted">{{ $investors->total() }} total investors</span>
                </div>
                
                @if($investors->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="investorsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Investor Name</th>
                                <th>Country</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>No. of Investments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($investors as $index => $investor)
                            <tr>
                                <td>{{ $investors->firstItem() + $index }}</td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $investor->title_name }} {{ $investor->full_name }}
                                        </div>
                                        <small class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            {{ $investor->city }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="mdi mdi-flag me-1"></i>
                                        {{ $investor->country->name ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="mailto:{{ $investor->email }}" class="text-decoration-none">
                                        {{ $investor->email }}
                                    </a>
                                </td>
                                <td>
                                    <a href="tel:{{ $investor->phone }}" class="text-decoration-none">
                                        {{ $investor->phone }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge {{ $investor->investments_count > 0 ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $investor->investments_count }}
                                    </span>
                                </td>
                                <td>
                                    @switch($investor->status)
                                        @case(1)
                                            <span class="badge bg-success">Active</span>
                                            @break
                                        @case(0)
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-danger">Suspended</span>
                                            @break
                                        @case(3)
                                            <span class="badge bg-secondary">Deactivated</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $investor->status_name }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.investments.show-investor', $investor->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Profile">
                                            <i class="mdi mdi-eye"></i>
                                        </a>

                                        <a href="{{ route('admin.accounting.journal-entries', ['investor_id' => $investor->id]) }}"
                                           class="btn btn-sm btn-outline-warning" title="View Journal Ledger">
                                            <i class="mdi mdi-book-open-variant"></i>
                                        </a>
                                        
                                        @if($investor->investments_count > 0)
                                            <a href="{{ route('admin.investments.show-investor', $investor->id) }}#investments" 
                                               class="btn btn-sm btn-outline-success" title="View Investments">
                                                <i class="mdi mdi-chart-line"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('admin.investments.create-investment', $investor->id) }}" 
                                               class="btn btn-sm btn-outline-info" title="Add Investment">
                                                <i class="mdi mdi-plus"></i>
                                            </a>
                                        @endif
                                        
                                        @if(auth()->user()->user_type === 'admin')
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    title="Delete Investor"
                                                    onclick="confirmDelete({{ $investor->id }}, '{{ $investor->full_name }}')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted">
                            Showing {{ $investors->firstItem() }} to {{ $investors->lastItem() }} of {{ $investors->total() }} results
                        </span>
                    </div>
                    <div>
                        {{ $investors->appends(request()->query())->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-account-multiple-outline" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No investors found</h5>
                    <p class="text-muted">
                        @if(request('search'))
                            No investors match your search criteria. <a href="{{ route('admin.investments.investors', ['type' => $type]) }}">Clear search</a>
                        @else
                            Start by adding your first investor to the system
                        @endif
                    </p>
                    <a href="{{ route('admin.investments.create-investor') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add Investor
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Deactivate Investor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        <strong>Are you sure?</strong>
                        <p class="mb-0">You are about to delete investor <strong id="investorName"></strong>. This action will soft-delete the investor from active lists.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_code">Admin Security Code</label>
                        <input type="password" class="form-control" id="admin_code" name="code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="del_comments">Deactivation Comments</label>
                        <textarea class="form-control" id="del_comments" name="del_comments" rows="3" 
                                  placeholder="Enter reason for deactivation..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete"></i> Delete Investor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(investorId, investorName) {
    $('#investorName').text(investorName);
    let deleteUrl = `{{ route('admin.investments.delete-investor', ['investor' => '__INVESTOR_ID__']) }}`;
    deleteUrl = deleteUrl.replace('__INVESTOR_ID__', investorId);
    $('#deleteForm').attr('action', deleteUrl);
    $('#deleteModal').modal('show');
}

$(document).ready(function() {
    // Clear form when modal is hidden
    $('#deleteModal').on('hidden.bs.modal', function () {
        $('#deleteForm')[0].reset();
    });
});
</script>
@endpush
@endsection