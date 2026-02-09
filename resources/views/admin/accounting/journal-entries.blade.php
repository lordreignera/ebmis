@extends('layouts.admin')

@section('title', 'Journal Entries')

@push('styles')
<style>
    /* Modern Pagination Styles */
    .modern-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-top: 1px solid #e9ecef;
    }
    
    .pagination-info {
        color: #6c757d;
        font-size: 14px;
    }
    
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .pagination-numbers {
        display: flex;
        gap: 5px;
    }
    
    .pagination-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: white;
        color: #495057;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .pagination-btn:hover:not([disabled]) {
        background: #f8f9fa;
        border-color: #0d6efd;
        color: #0d6efd;
    }
    
    .pagination-btn.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: white;
        font-weight: bold;
    }
    
    .pagination-btn[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Journal Entries</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Journal Entries</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="mdi mdi-filter me-2"></i>Filters</h5>
                <form method="GET" action="{{ route('admin.accounting.journal-entries') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Reference Type</label>
                                <select class="form-control" name="reference_type">
                                    <option value="">All Types</option>
                                    <option value="Disbursement" {{ request('reference_type') == 'Disbursement' ? 'selected' : '' }}>Disbursement</option>
                                    <option value="Repayment" {{ request('reference_type') == 'Repayment' ? 'selected' : '' }}>Repayment</option>
                                    <option value="Fee Collection" {{ request('reference_type') == 'Fee Collection' ? 'selected' : '' }}>Fee Collection</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="">All Status</option>
                                    <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                                    <option value="reversed" {{ request('status') == 'reversed' ? 'selected' : '' }}>Reversed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter me-1"></i>Apply Filters</button>
                            <a href="{{ route('admin.accounting.journal-entries') }}" class="btn btn-secondary"><i class="mdi mdi-refresh me-1"></i>Clear</a>
                            <a href="{{ route('admin.accounting.journal-entries.download', request()->all()) }}" class="btn btn-info"><i class="mdi mdi-download me-1"></i>Download PDF</a>
                            <button type="button" class="btn btn-success" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Journal Entries List -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="mdi mdi-book-open-variant me-2"></i>All Journal Entries ({{ $entries->total() }})</h5>
                </div>

                @if($entries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="journalEntriesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Journal #</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Narrative</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                            <tr>
                                <td>{{ ($entries->currentPage() - 1) * $entries->perPage() + $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('admin.accounting.journal-entry', $entry->Id) }}" class="text-primary font-weight-bold">
                                        {{ $entry->journal_number }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($entry->transaction_date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $entry->reference_type }}</span><br>
                                    <small class="text-muted">#{{ $entry->reference_id }}</small>
                                </td>
                                <td>
                                    <div style="max-width: 300px;">
                                        {{ Str::limit($entry->narrative, 60) }}
                                    </div>
                                    @if($entry->costCenter)
                                    <small class="text-muted"><i class="mdi mdi-domain"></i> {{ $entry->costCenter->name }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="font-weight-bold">{{ number_format($entry->total_debit, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="font-weight-bold">{{ number_format($entry->total_credit, 2) }}</span>
                                </td>
                                <td>
                                    @if($entry->status == 'posted')
                                    <span class="badge badge-success">Posted</span>
                                    @elseif($entry->status == 'reversed')
                                    <span class="badge badge-danger">Reversed</span>
                                    @else
                                    <span class="badge badge-warning">{{ ucfirst($entry->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.accounting.journal-entry', $entry->Id) }}" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-active font-weight-bold">
                                <td colspan="5" class="text-end">TOTAL:</td>
                                <td class="text-end">{{ number_format($entries->sum('total_debit'), 2) }}</td>
                                <td class="text-end">{{ number_format($entries->sum('total_credit'), 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Modern Pagination -->
                <div class="modern-pagination mt-4">
                    <div class="pagination-info">
                        Showing {{ $entries->firstItem() ?? 0 }} to {{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }} entries
                    </div>
                    <div class="pagination-controls">
                        @if ($entries->onFirstPage())
                            <span class="pagination-btn" disabled>
                                <i class="mdi mdi-chevron-left"></i>
                                Previous
                            </span>
                        @else
                            <a href="{{ $entries->appends(request()->query())->previousPageUrl() }}" class="pagination-btn">
                                <i class="mdi mdi-chevron-left"></i>
                                Previous
                            </a>
                        @endif

                        <div class="pagination-numbers">
                            @php
                                $currentPage = $entries->currentPage();
                                $lastPage = $entries->lastPage();
                                $start = max(1, $currentPage - 2);
                                $end = min($lastPage, $currentPage + 2);
                                
                                // Adjust if at the beginning or end
                                if ($currentPage <= 3) {
                                    $end = min(5, $lastPage);
                                }
                                if ($currentPage >= $lastPage - 2) {
                                    $start = max(1, $lastPage - 4);
                                }
                            @endphp

                            @if($start > 1)
                                <a href="{{ $entries->appends(request()->query())->url(1) }}" class="pagination-btn">1</a>
                                @if($start > 2)
                                    <span class="pagination-btn" disabled>...</span>
                                @endif
                            @endif

                            @for ($page = $start; $page <= $end; $page++)
                                @if ($page == $currentPage)
                                    <span class="pagination-btn active">{{ $page }}</span>
                                @else
                                    <a href="{{ $entries->appends(request()->query())->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($end < $lastPage)
                                @if($end < $lastPage - 1)
                                    <span class="pagination-btn" disabled>...</span>
                                @endif
                                <a href="{{ $entries->appends(request()->query())->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                            @endif
                        </div>

                        @if ($entries->hasMorePages())
                            <a href="{{ $entries->appends(request()->query())->nextPageUrl() }}" class="pagination-btn">
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
                @else
                <div class="alert alert-info">
                    <i class="mdi mdi-information me-2"></i>No journal entries found. Journal entries are automatically created when loans are disbursed or repayments are made.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
