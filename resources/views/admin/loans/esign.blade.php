@extends('layouts.admin')

@section('title', 'eSign Loans Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-file-document-edit"></i> 
                        eSign Loan Portfolio
                        <span class="badge bg-info">Electronic Signature</span>
                    </h3>
                    <a href="{{ route('admin.loans.create') }}?type=personal&period=daily&esign=1" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Create New eSign Loan
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['total_esign'] ?? 0 }}</h4>
                                            <small>Total eSign</small>
                                        </div>
                                        <i class="mdi mdi-file-document-edit mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['pending_esign'] ?? 0 }}</h4>
                                            <small>Pending</small>
                                        </div>
                                        <i class="mdi mdi-clock mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['approved_esign'] ?? 0 }}</h4>
                                            <small>Approved</small>
                                        </div>
                                        <i class="mdi mdi-check-circle mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['disbursed_esign'] ?? 0 }}</h4>
                                            <small>Disbursed</small>
                                        </div>
                                        <i class="mdi mdi-cash-usd mdi-24px"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-section">
                        <form method="GET" action="{{ route('admin.loans.esign') }}" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pending</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Approved</option>
                                    <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Disbursed</option>
                                    <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Completed</option>
                                    <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select class="form-control" id="branch_id" name="branch_id">
                                    <option value="">All Branches</option>
                                    @foreach(\App\Models\Branch::all() as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <a href="{{ route('admin.loans.esign') }}" class="btn btn-secondary d-block w-100">
                                    <i class="mdi mdi-refresh"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>

    <!-- Loans Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @if($loans->count() > 0)
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-search">
                                <input type="text" placeholder="Search eSign loans..." id="quickSearch">
                            </div>
                            <div class="table-actions">
                                <a href="{{ route('admin.loans.create') }}?type=personal&period=daily&esign=1" class="export-btn">
                                    <i class="mdi mdi-plus"></i> New eSign Loan
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="modern-table table-hover">
                            <thead>
                                <tr>
                                    <th>Loan Code</th>
                                    <th>Borrower</th>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loans as $loan)
                                <tr>
                                    <td>
                                        <span class="account-number">{{ $loan->code }}</span>
                                        <br><small class="text-muted">Sign Code: {{ $loan->sign_code }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($loan->loan_type === 'personal' && isset($loan->member))
                                                @if($loan->member->pp_file)
                                                    <img src="{{ Storage::url($loan->member->pp_file) }}" 
                                                         class="rounded-circle me-2" width="32" height="32" alt="Photo">
                                                @else
                                                    <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 32px; height: 32px;">
                                                        <span class="text-white fw-bold">{{ substr($loan->member->fname ?? 'N', 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-medium">{{ $loan->member->fname ?? 'N/A' }} {{ $loan->member->lname ?? '' }}</div>
                                                    <small class="text-muted">{{ $loan->member->contact ?? '' }}</small>
                                                </div>
                                            @else
                                                <div>
                                                    <div class="fw-medium">{{ $loan->group->group_name ?? 'N/A' }}</div>
                                                    <small class="text-muted">Group Loan</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $loan->loan_type === 'personal' ? 'info' : 'success' }}">
                                            {{ ucfirst($loan->loan_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                    <td><span class="fw-semibold">UGX {{ number_format($loan->principal, 2) }}</span></td>
                                    <td>{{ $loan->interest }}%</td>
                                    <td>{{ $loan->period }} days</td>
                                    <td>
                                        @php
                                            $statusClass = match($loan->status) {
                                                0 => 'status-pending',
                                                1 => 'status-approved',
                                                2 => 'status-disbursed',
                                                3 => 'status-verified',
                                                4 => 'status-not-verified',
                                                default => 'status-not-verified'
                                            };
                                            $statusText = match($loan->status) {
                                                0 => 'Pending',
                                                1 => 'Approved',
                                                2 => 'Disbursed',
                                                3 => 'Completed',
                                                4 => 'Rejected',
                                                default => 'Unknown'
                                            };
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($loan->datecreated)->format('M d, Y') }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('admin.loans.show', $loan->id) }}?type={{ $loan->loan_type }}" 
                                               class="btn-modern btn-view" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if($loan->status == 0)
                                                <button type="button" class="btn-modern btn-process" 
                                                        onclick="approveLoan({{ $loan->id }}, '{{ $loan->loan_type }}')" title="Approve">
                                                    <i class="mdi mdi-check"></i>
                                                </button>
                                                <button type="button" class="btn-modern btn-delete" 
                                                        onclick="rejectLoan({{ $loan->id }}, '{{ $loan->loan_type }}')" title="Reject">
                                                    <i class="mdi mdi-close"></i>
                                                </button>
                                            @endif
                                            <a href="{{ route('admin.loans.view-agreement', ['id' => $loan->id, 'type' => $loan->loan_type]) }}" 
                                               class="btn-modern btn-warning" title="View Agreement" target="_blank">
                                                <i class="mdi mdi-file-document"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>

                        <!-- Pagination -->
                        <div class="table-footer">
                            <div class="table-info">
                                Showing {{ $loans->firstItem() ?? 0 }} to {{ $loans->lastItem() ?? 0 }} of {{ $loans->total() }} entries
                            </div>
                            <div class="table-pagination">
                                {{ $loans->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="mdi mdi-file-document-outline text-muted" style="font-size: 48px;"></i>
                        <h5 class="mt-3 text-muted">No eSign loans found</h5>
                        <p class="text-muted">Start by creating a new eSign loan application</p>
                        <a href="{{ route('admin.loans.create') }}?type=personal&period=daily&esign=1" class="btn btn-primary">
                            <i class="mdi mdi-plus-circle me-1"></i> Create eSign Loan
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add any JavaScript functionality for the eSign page here
    console.log('eSign loans page loaded');
});
</script>
@endpush