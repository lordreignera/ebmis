@extends('layouts.admin')

@section('title', 'Loans Due Report')

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
                        <li class="breadcrumb-item active">Loans Due</li>
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
                    <form method="GET" action="{{ route('admin.reports.loans-due') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="date" class="form-label">As of Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date" 
                                   name="date" 
                                   value="{{ old('date', request('date')) }}" 
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
                            <i class="mdi mdi-calendar-clock"></i> Please select a date as at which to run the report.
                        </div>
                    @else
                        <p class="mb-3">{!! $rep_msg !!}</p>
                    @endif

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="loansTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Branch</th>
                                    <th>Code</th>
                                    <th>Period</th>
                                    <th>Installment (UGX)</th>
                                    <th>Principal (UGX)</th>
                                    <th>Interest (UGX)</th>
                                    <th>Expected Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loans as $index => $loan)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $loan->member_name ?? 'N/A' }}</td>
                                        <td>{{ $loan->branch_name ?? 'N/A' }}</td>
                                        <td>{{ $loan->loan_code ?? 'N/A' }}</td>
                                        <td>{{ $loan->period ?? 'N/A' }}</td>
                                        <td>{{ number_format($loan->installment_amount ?? 0, 0) }}</td>
                                        <td>{{ number_format($loan->principal_amount ?? 0, 0) }}</td>
                                        <td>{{ number_format($loan->interest_amount ?? 0, 0) }}</td>
                                        <td>{{ $loan->expected_date ?? 'N/A' }}</td>
                                        <td>
                                            @if($loan->loan_type == 'Personal Loan')
                                                <a href="{{ route('admin.loans.show', $loan->loan_id) }}" 
                                                   class="btn btn-sm btn-success" 
                                                   title="View Repayment Schedule">
                                                    <i class="mdi mdi-cash-plus"></i> Repay
                                                </a>
                                            @else
                                                <a href="{{ route('admin.groups.show', $loan->group_id) }}" 
                                                   class="btn btn-sm btn-success" 
                                                   title="View Group Details">
                                                    <i class="mdi mdi-cash-plus"></i> Repay
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
    $('#loansTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": [[0, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": 0 }
        ]
    });
});
</script>
@endsection