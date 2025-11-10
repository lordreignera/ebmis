@extends('layouts.admin')

@section('title', 'Paid Loans Report')

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">EBIMS</a></li>
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Reports</a></li>
                        <li class="breadcrumb-item active">Paid Loans</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ $title }}</h4>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.paid-loans') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="start_date" 
                                   name="start_date" 
                                   value="{{ old('start_date', request('start_date')) }}" 
                                   required>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="end_date" 
                                   name="end_date" 
                                   value="{{ old('end_date', request('end_date')) }}" 
                                   required>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-control">
                                <option value="">All loans</option>
                                <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Personal loans</option>
                                <option value="group" {{ request('type') == 'group' ? 'selected' : '' }}>Group loans</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="run" value="1" class="btn btn-primary me-2">Run</button>
                            @if(isset($loans) && $loans->count() > 0)
                                <div class="btn-group">
                                    <a href="{{ request()->fullUrlWithQuery(['download' => 'csv']) }}" class="btn btn-sm btn-outline-success">
                                        <i class="mdi mdi-file-delimited"></i> CSV
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['download' => 'excel']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-file-excel"></i> Excel
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                                        <i class="mdi mdi-file-pdf"></i> PDF
                                    </a>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(!request('run'))
                        <div class="alert alert-info">
                            <i class="mdi mdi-calendar-clock"></i> Please select a Date Range and Type to run the report.
                        </div>
                    @else
                        <p class="mb-3">{!! $rep_msg !!}</p>
                    @endif

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="paidLoansTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Branch</th>
                                    <th>Code</th>
                                    <th>Interest</th>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Date Closed</th>
                                    <th>Investment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loans as $index => $loan)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $loan->member_name ?? 'N/A' }}</td>
                                        <td>{{ $loan->branch_name ?? 'N/A' }}</td>
                                        <td>{{ $loan->loan_code ?? 'N/A' }}</td>
                                        <td>{{ $loan->interest ?? 'N/A' }}%</td>
                                        <td>{{ $loan->period ?? 'N/A' }} months</td>
                                        <td>{{ number_format($loan->amount ?? 0, 0) }}</td>
                                        <td>
                                            @if($loan->date_closed)
                                                {{ \Carbon\Carbon::parse($loan->date_closed)->format('d/m/Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ number_format($loan->investment ?? 0, 0) }}</td>
                                        <td>
                                            @if($loan->loan_type == 'Personal Loan')
                                                <a href="{{ route('admin.loans.show', $loan->loan_id) }}" class="btn btn-sm btn-info" title="View Loan Details">
                                                    <i class="mdi mdi-eye"></i> View
                                                </a>
                                            @else
                                                <a href="{{ route('admin.groups.show', $loan->group_id ?? $loan->loan_id) }}" class="btn btn-sm btn-info" title="View Group Details">
                                                    <i class="mdi mdi-eye"></i> View
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No data available in table</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#paidLoansTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": [[7, "desc"]],
        "columnDefs": [
            { "orderable": false, "targets": 0 }
        ]
    });
});
</script>
@endsection