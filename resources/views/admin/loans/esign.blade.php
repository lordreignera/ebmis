@extends('layouts.admin')

@section('title', 'eSign Loans Management')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">eSign Loans</h3>
                    <h6 class="font-weight-normal mb-0">Manage electronic signature loans</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Total eSign Loans</p>
                    <div class="d-flex">
                        <div class="d-flex align-items-center me-2">
                            <h2 class="text-primary font-weight-bold">{{ $stats['total_esign'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Pending</p>
                    <div class="d-flex">
                        <div class="d-flex align-items-center me-2">
                            <h2 class="text-warning font-weight-bold">{{ $stats['pending_esign'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Approved</p>
                    <div class="d-flex">
                        <div class="d-flex align-items-center me-2">
                            <h2 class="text-success font-weight-bold">{{ $stats['approved_esign'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Disbursed</p>
                    <div class="d-flex">
                        <div class="d-flex align-items-center me-2">
                            <h2 class="text-info font-weight-bold">{{ $stats['disbursed_esign'] }}</h2>
                        </div>
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
                    <form method="GET" action="{{ route('admin.loans.esign') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="disbursed" {{ request('status') == 'disbursed' ? 'selected' : '' }}>Disbursed</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.loans.esign') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title">eSign Loans List</h6>
                        <div>
                            <a href="{{ route('admin.loans.create') }}?type=personal&period=daily&esign=1" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-plus-circle me-1"></i> Add eSign Loan
                            </a>
                        </div>
                    </div>

                    @if($loans->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Borrower</th>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Principal</th>
                                    <th>Interest Rate</th>
                                    <th>Term</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loans as $loan)
                                <tr>
                                    <td>{{ $loan->code }}</td>
                                    <td>
                                        @if($loan->loan_type === 'personal')
                                            {{ $loan->member->fname ?? 'N/A' }} {{ $loan->member->lname ?? '' }}
                                        @else
                                            {{ $loan->group->group_name ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $loan->loan_type === 'personal' ? 'info' : 'success' }}">
                                            {{ ucfirst($loan->loan_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $loan->product->pname ?? 'N/A' }}</td>
                                    <td>UGX {{ number_format($loan->principal) }}</td>
                                    <td>{{ $loan->interest_rate }}%</td>
                                    <td>{{ $loan->term }} {{ $loan->period }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'disbursed' => 'info',
                                                'completed' => 'primary',
                                                'rejected' => 'danger',
                                                'defaulted' => 'danger'
                                            ];
                                            $color = $statusColors[$loan->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ ucfirst($loan->status) }}</span>
                                    </td>
                                    <td>{{ $loan->created_at ? $loan->created_at->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">
                                                    <i class="mdi mdi-eye me-1"></i> View Details
                                                </a></li>
                                                @if($loan->status === 'pending')
                                                <li><a class="dropdown-item" href="#">
                                                    <i class="mdi mdi-check me-1"></i> Approve
                                                </a></li>
                                                <li><a class="dropdown-item" href="#">
                                                    <i class="mdi mdi-close me-1"></i> Reject
                                                </a></li>
                                                @endif
                                                @if($loan->status === 'approved')
                                                <li><a class="dropdown-item" href="#">
                                                    <i class="mdi mdi-cash me-1"></i> Disburse
                                                </a></li>
                                                @endif
                                                <li><a class="dropdown-item" href="#">
                                                    <i class="mdi mdi-calendar me-1"></i> View Schedule
                                                </a></li>
                                                <li><a class="dropdown-item" href="#">
                                                    <i class="mdi mdi-file-document me-1"></i> Download Contract
                                                </a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $loans->appends(request()->query())->links() }}
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