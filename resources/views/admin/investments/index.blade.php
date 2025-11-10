@extends('layouts.admin')

@section('title', 'Investments Management')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Investment Management</h4>
            </div>
            <div>
                <a href="{{ route('admin.investments.create-investor') }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-plus"></i> Add Investor
                </a>
                <a href="{{ route('admin.investments.investors') }}" class="btn btn-info btn-sm">
                    <i class="mdi mdi-account-multiple"></i> View Investors
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
                        <h4 class="card-title mb-2">Total Investments</h4>
                        <h2 class="text-primary mb-2">{{ number_format($stats['total_investments']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-chart-line icon-lg text-primary"></i>
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
                        <h4 class="card-title mb-2">Active Investments</h4>
                        <h2 class="text-success mb-2">{{ number_format($stats['active_investments']) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-check-circle icon-lg text-success"></i>
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
                        <h4 class="card-title mb-2">Total Amount</h4>
                        <h2 class="text-info mb-2">${{ number_format($stats['total_amount'], 0) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-cash-multiple icon-lg text-info"></i>
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
                        <h4 class="card-title mb-2">Total Interest</h4>
                        <h2 class="text-warning mb-2">${{ number_format($stats['total_interest'], 0) }}</h2>
                    </div>
                    <div class="icon-container">
                        <i class="mdi mdi-trending-up icon-lg text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filter Investments</h4>
                <form method="GET" action="{{ route('admin.investments.index') }}" class="row">
                    <div class="col-md-3">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search investments..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Completed</option>
                            <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="1" {{ request('type') === '1' ? 'selected' : '' }}>Standard Interest</option>
                            <option value="2" {{ request('type') === '2' ? 'selected' : '' }}>Compound Interest</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="start_date" 
                               class="form-control" 
                               placeholder="Start Date"
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               name="end_date" 
                               class="form-control" 
                               placeholder="End Date"
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Investments Table -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Investments List</h4>
                    <span class="text-muted">{{ $investments->total() }} total investments</span>
                </div>
                
                @if($investments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="investmentsTable">
                        <thead>
                            <tr>
                                <th><i class="mdi mdi-account"></i> Investor</th>
                                <th><i class="mdi mdi-tag"></i> Investment Name</th>
                                <th><i class="mdi mdi-cash"></i> Amount</th>
                                <th><i class="mdi mdi-percent"></i> Rate</th>
                                <th><i class="mdi mdi-calendar"></i> Period</th>
                                <th><i class="mdi mdi-chart-line"></i> Interest</th>
                                <th><i class="mdi mdi-information"></i> Type</th>
                                <th><i class="mdi mdi-check-circle"></i> Status</th>
                                <th><i class="mdi mdi-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($investments as $investment)
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $investment->investor->full_name }}</div>
                                        <small class="text-muted">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            {{ $investment->investor->country->name ?? 'Unknown' }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $investment->name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        ${{ number_format($investment->amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-primary fw-bold">{{ $investment->percentage }}%</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $investment->period }} years
                                    </span>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">
                                        ${{ number_format($investment->interest, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $investment->type == 1 ? 'bg-primary' : 'bg-warning' }}">
                                        {{ $investment->type_name }}
                                    </span>
                                </td>
                                <td>
                                    @switch($investment->status)
                                        @case(1)
                                            <span class="badge bg-success">Active</span>
                                            @break
                                        @case(0)
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-info">Completed</span>
                                            @break
                                        @case(3)
                                            <span class="badge bg-danger">Cancelled</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $investment->status_name }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.investments.show-investment', $investment->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Investment">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.investments.show-investor', $investment->investor->id) }}" 
                                           class="btn btn-sm btn-outline-info" title="View Investor">
                                            <i class="mdi mdi-account"></i>
                                        </a>
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
                            Showing {{ $investments->firstItem() }} to {{ $investments->lastItem() }} of {{ $investments->total() }} results
                        </span>
                    </div>
                    <div>
                        {{ $investments->appends(request()->query())->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-chart-line-variant" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No investments found</h5>
                    <p class="text-muted">Start by adding your first investor and investment</p>
                    <a href="{{ route('admin.investments.create-investor') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add Investor
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('select[name="status"], select[name="type"]').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
@endsection