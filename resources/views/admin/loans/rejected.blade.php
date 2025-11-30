@extends('layouts.admin')

@section('title', 'Rejected Loans')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Rejected Loans</li>
                    </ol>
                </div>
                <h4 class="page-title">Rejected {{ ucfirst($type) }} Loans</h4>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Rejected Loans">Total Rejected</h5>
                            <h3 class="my-2 py-1">{{ $stats['total_rejected'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">All Time</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="rejected-loans-chart" data-colors="#dc3545"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Rejected This Month">This Month</h5>
                            <h3 class="my-2 py-1">{{ $stats['rejected_this_month'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-nowrap">{{ now()->format('F Y') }}</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="rejected-month-chart" data-colors="#ffc107"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter Rejected Loans</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.loans.rejected') }}" class="row g-3">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Loan code or member name">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="branch" class="form-label">Branch</label>
                            <select class="form-select" id="branch" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date') }}">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-magnify me-1"></i> Filter
                                </button>
                                <a href="{{ route('admin.loans.rejected', ['type' => $type]) }}" class="btn btn-secondary">
                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejected Loans Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Rejected Loans ({{ $loans->total() }} total)</h5>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <a class="btn btn-light dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-export me-1"></i> Export
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('admin.loans.rejected.export', array_merge(['type' => $type], request()->all())) }}">
                                        <i class="mdi mdi-file-excel me-1"></i> CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($loans->count() > 0)
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="modern-table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 12%;">Loan Code</th>
                                        <th style="width: 15%;">{{ $type === 'personal' ? 'Member' : 'Group' }}</th>
                                        <th style="width: 12%;">Branch</th>
                                        <th style="width: 10%;">Amount</th>
                                        <th style="width: 8%;">Period</th>
                                        <th style="width: 10%;">Date Rejected</th>
                                        <th style="width: 10%;">Rejected By</th>
                                        <th style="width: 10%;">Reason</th>
                                        <th style="width: 8%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($loans as $index => $loan)
                                        <tr>
                                            <td>{{ $loans->firstItem() + $index }}</td>
                                            <td>
                                                <span class="account-number">{{ $loan->code }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium">
                                                    @if($type === 'personal')
                                                        {{ $loan->member ? $loan->member->fname . ' ' . $loan->member->lname : 'N/A' }}
                                                    @else
                                                        {{ $loan->group ? $loan->group->name : 'N/A' }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $loan->branch ? $loan->branch->name : 'No Branch' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-semibold">{{ number_format($loan->principal, 0) }}</span>
                                            </td>
                                            <td class="text-center">{{ $loan->period }} months</td>
                                            <td class="text-center">
                                                @if($loan->date_rejected)
                                                    <small>{{ date('Y-m-d', strtotime($loan->date_rejected)) }}</small>
                                                @else
                                                    <small class="text-muted">N/A</small>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $loan->rejectedBy ? $loan->rejectedBy->name : 'System' }}</small>
                                            </td>
                                            <td>
                                                @if($loan->comments || $loan->Rcomments)
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#reasonModal{{ $loan->id }}">
                                                        <i class="mdi mdi-text-box-outline"></i> View
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.loans.show', $loan->id) }}?type={{ $type }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="mdi mdi-eye"></i> Details
                                                </a>
                                            </td>
                                        </tr>

                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                            <div class="modern-pagination">
                                <div class="pagination-info">
                                    @if($loans->total() > 0)
                                        Showing {{ $loans->firstItem() ?? 1 }} to {{ $loans->lastItem() ?? $loans->count() }} of {{ $loans->total() }} entries
                                    @else
                                        No entries found
                                    @endif
                                </div>
                                <div class="pagination-controls">
                                    @if($loans->hasPages())
                                        @if ($loans->onFirstPage())
                                            <span class="pagination-btn" disabled>Previous</span>
                                        @else
                                            <a class="pagination-btn" href="{{ $loans->previousPageUrl() }}">Previous</a>
                                        @endif

                                        <div class="pagination-numbers">
                                            @php
                                                $currentPage = $loans->currentPage();
                                                $lastPage = $loans->lastPage();
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
                                                <a href="{{ $loans->url(1) }}" class="pagination-btn">1</a>
                                                @if($start > 2)
                                                    <span class="pagination-btn" disabled>...</span>
                                                @endif
                                            @endif

                                            @for ($page = $start; $page <= $end; $page++)
                                                @if ($page == $currentPage)
                                                    <span class="pagination-btn active">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $loans->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                                @endif
                                            @endfor

                                            @if($end < $lastPage)
                                                @if($end < $lastPage - 1)
                                                    <span class="pagination-btn" disabled>...</span>
                                                @endif
                                                <a href="{{ $loans->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                            @endif
                                        </div>

                                        @if ($loans->hasMorePages())
                                            <a class="pagination-btn" href="{{ $loans->nextPageUrl() }}">Next</a>
                                        @else
                                            <span class="pagination-btn" disabled>Next</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="mdi mdi-close-circle-outline display-4 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Rejected Loans Found</h5>
                            <p class="text-muted mb-0">No loans match your current filter criteria.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modals -->
@foreach($loans as $loan)
    @if($loan->comments || $loan->Rcomments)
    <div class="modal fade" id="reasonModal{{ $loan->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Rejection Reason - {{ $loan->code }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-white">
                    <div class="mb-3">
                        <label class="fw-bold text-dark">Member:</label>
                        <p class="text-dark">
                            @if($type === 'personal')
                                {{ $loan->member ? $loan->member->fname . ' ' . $loan->member->lname : 'N/A' }}
                            @else
                                {{ $loan->group ? $loan->group->name : 'N/A' }}
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-dark">Rejection Reason:</label>
                        <p class="text-muted">{{ $loan->comments ?? $loan->Rcomments }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold text-dark">Rejected By:</label>
                        <p class="text-dark">{{ $loan->rejectedBy ? $loan->rejectedBy->name : 'System' }}</p>
                    </div>
                    <div>
                        <label class="fw-bold text-dark">Date Rejected:</label>
                        <p class="text-dark">{{ $loan->date_rejected ? \Carbon\Carbon::parse($loan->date_rejected)->format('F d, Y g:i A') : 'N/A' }}</p>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection
