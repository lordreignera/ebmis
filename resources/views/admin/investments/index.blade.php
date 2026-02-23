@extends('layouts.admin')

@section('title', 'Investments Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/modern-tables.css') }}">
@endpush

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
                    <h4 class="card-title">Investments List ({{ $investments->total() }} total)</h4>
                </div>
                
                @if($investments->count() > 0)
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-search">
                                <input type="text" placeholder="Search investments..." id="quickSearch" value="{{ request('search') }}">
                            </div>
                            <div class="table-actions">
                                <div class="table-show-entries">
                                    Show 
                                    <select onchange="window.location.href='{{ url()->current() }}?per_page='+this.value+'&{{ http_build_query(request()->except('per_page')) }}'">
                                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    entries
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 15%;">Investor</th>
                                    <th style="width: 15%;">Investment Name</th>
                                    <th style="width: 10%;">Amount</th>
                                    <th style="width: 8%;">Rate</th>
                                    <th style="width: 8%;">Period</th>
                                    <th style="width: 10%;">Interest</th>
                                    <th style="width: 12%;">Type</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 12%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($investments as $index => $investment)
                                <tr>
                                    <td>{{ $investments->firstItem() + $index }}</td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">{{ $investment->investor->full_name ?? 'Unknown Investor' }}</div>
                                            <small class="text-muted">
                                                <i class="mdi mdi-map-marker me-1"></i>
                                                {{ optional(optional($investment->investor)->country)->name ?? 'Unknown' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $investment->name }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">${{ number_format($investment->amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-bold">{{ $investment->percentage }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">
                                            {{ $investment->period }} years
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success fw-bold">
                                            ${{ number_format($investment->interest, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $investment->type == 1 ? 'status-verified' : 'status-pending' }}">
                                            {{ $investment->type_name }}
                                        </span>
                                    </td>
                                    <td>
                                        @switch($investment->status)
                                            @case(1)
                                                <span class="status-badge status-verified">Active</span>
                                                @break
                                            @case(0)
                                                <span class="status-badge status-pending">Pending</span>
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
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('admin.investments.show-investment', $investment->id) }}" 
                                               class="btn btn-sm btn-primary" title="View Investment">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if($investment->investor)
                                                <a href="{{ route('admin.accounting.journal-entries', ['investor_id' => $investment->investor->id]) }}"
                                                   class="btn btn-sm btn-warning" title="View Ledger">
                                                    <i class="mdi mdi-book-open-variant"></i>
                                                </a>
                                            @else
                                                <span class="btn btn-sm btn-secondary disabled" title="Investor not found">
                                                    <i class="mdi mdi-book-open-page-variant"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                        <div class="modern-pagination">
                            <div class="pagination-info">
                                @if($investments->total() > 0)
                                    Showing {{ $investments->firstItem() ?? 1 }} to {{ $investments->lastItem() ?? $investments->count() }} of {{ $investments->total() }} entries
                                @else
                                    No entries found
                                @endif
                            </div>
                            <div class="pagination-controls">
                                @if($investments->hasPages())
                                    @if ($investments->onFirstPage())
                                        <span class="pagination-btn" disabled>Previous</span>
                                    @else
                                        <a class="pagination-btn" href="{{ $investments->previousPageUrl() }}">Previous</a>
                                    @endif

                                    <div class="pagination-numbers">
                                        @php
                                            $currentPage = $investments->currentPage();
                                            $lastPage = $investments->lastPage();
                                            $start = max(1, $currentPage - 2);
                                            $end = min($lastPage, $currentPage + 2);
                                            
                                            if ($currentPage <= 3) {
                                                $end = min(5, $lastPage);
                                            }
                                            if ($currentPage >= $lastPage - 2) {
                                                $start = max(1, $lastPage - 4);
                                            }
                                        @endphp

                                        @if($start > 1)
                                            <a href="{{ $investments->url(1) }}" class="pagination-btn">1</a>
                                            @if($start > 2)
                                                <span class="pagination-btn" disabled>...</span>
                                            @endif
                                        @endif

                                        @for ($page = $start; $page <= $end; $page++)
                                            @if ($page == $currentPage)
                                                <span class="pagination-btn active">{{ $page }}</span>
                                            @else
                                                <a href="{{ $investments->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                            @endif
                                        @endfor

                                        @if($end < $lastPage)
                                            @if($end < $lastPage - 1)
                                                <span class="pagination-btn" disabled>...</span>
                                            @endif
                                            <a href="{{ $investments->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                        @endif
                                    </div>

                                    @if ($investments->hasMorePages())
                                        <a class="pagination-btn" href="{{ $investments->nextPageUrl() }}">Next</a>
                                    @else
                                        <span class="pagination-btn" disabled>Next</span>
                                    @endif
                                @endif
                            </div>
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
    // Quick search functionality
    $('#quickSearch').on('keyup', function(e) {
        var value = $(this).val().trim();
        
        // Build URL with search parameter
        var url = new URL(window.location.href);
        
        if (value.length > 0) {
            url.searchParams.set('search', value);
            url.searchParams.delete('page'); // Reset to page 1 when searching
        } else {
            url.searchParams.delete('search');
        }
        
        // Debounce the search (wait 500ms after user stops typing)
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            window.location.href = url.toString();
        }, 500);
    });
    
    // Auto-submit form on filter change
    $('select[name="status"], select[name="type"]').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
@endsection