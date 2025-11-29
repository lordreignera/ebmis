@extends('layouts.admin')

@section('title', 'Late Fees Management')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Late Fees Management</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkWaiveModal">
            <i class="fas fa-eraser"></i> Bulk Waive
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Late Fees</div>
                            <div class="h5 mb-0 font-weight-bold">UGX {{ number_format($stats['total'], 0) }}</div>
                            <small class="text-muted">{{ number_format($stats['count']) }} records</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0 font-weight-bold">UGX {{ number_format($stats['pending'], 0) }}</div>
                            <small class="text-muted">{{ number_format($stats['pending_count']) }} records</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid</div>
                            <div class="h5 mb-0 font-weight-bold">UGX {{ number_format($stats['paid'], 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Waived</div>
                            <div class="h5 mb-0 font-weight-bold">UGX {{ number_format($stats['waived'], 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> Filters
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.late-fees.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Paid</option>
                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Waived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or Loan Code" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.late-fees.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Late Fees Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Late Fees List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Loan Code</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th>Periods</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Calculated Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lateFees as $lateFee)
                            <tr>
                                <td>{{ $lateFees->firstItem() + $loop->index }}</td>
                                <td>
                                    @if($lateFee->member)
                                        {{ $lateFee->member->fname }} {{ $lateFee->member->lname }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lateFee->loan)
                                        <a href="{{ route('admin.loans.repayments.schedules', $lateFee->loan_id) }}" target="_blank">
                                            {{ $lateFee->loan->code }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ date('d-m-Y', strtotime($lateFee->schedule_due_date)) }}</td>
                                <td>{{ $lateFee->days_overdue }} days</td>
                                <td>{{ $lateFee->periods_overdue }}</td>
                                <td class="text-end">
                                    <strong>{{ number_format($lateFee->amount, 0) }}</strong>
                                </td>
                                <td>
                                    @if($lateFee->status == 0)
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($lateFee->status == 1)
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($lateFee->status == 2)
                                        <span class="badge bg-info">Waived</span>
                                    @else
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @endif
                                </td>
                                <td>{{ date('d-m-Y H:i', strtotime($lateFee->calculated_date)) }}</td>
                                <td>
                                    @if($lateFee->status == 0)
                                        <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#waiveModal{{ $lateFee->id }}">
                                            <i class="fas fa-eraser"></i> Waive
                                        </button>
                                    @elseif($lateFee->status == 2)
                                        <small class="text-muted">
                                            Waived: {{ $lateFee->waiver_reason }}
                                        </small>
                                    @endif
                                </td>
                            </tr>

                            <!-- Waive Modal -->
                            <div class="modal fade" id="waiveModal{{ $lateFee->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.late-fees.waive', $lateFee->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Waive Late Fee</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Member:</strong> {{ $lateFee->member->fname ?? '' }} {{ $lateFee->member->lname ?? '' }}</p>
                                                <p><strong>Amount:</strong> UGX {{ number_format($lateFee->amount, 0) }}</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Reason for Waiver <span class="text-danger">*</span></label>
                                                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-info">Waive Late Fee</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No late fees found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $lateFees->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Bulk Waive Modal -->
<div class="modal fade" id="bulkWaiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.late-fees.bulk-waive') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Waive Late Fees</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This will waive ALL pending late fees for schedules due between the selected dates.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">From Date <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">To Date <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Waiver <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required 
                                  placeholder="e.g., System upgrade period - clients unable to repay"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Bulk Waive</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    .border-left-warning {
        border-left: 4px solid #f6c23e !important;
    }
    .border-left-info {
        border-left: 4px solid #36b9cc !important;
    }
</style>
@endpush
