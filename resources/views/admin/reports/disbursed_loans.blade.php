@extends('layouts.admin')

@section('title', 'Disbursed Loans Report')

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
                        <li class="breadcrumb-item active">Disbursed Loans</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ $data['title'] ?? 'Disbursed Loans Report' }}</h4>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.disbursed-loans') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search by loan ID, member name...">
                        </div>
                        <div class="col-md-2">
                            <label for="s_date" class="form-label">Start Date</label>
                            <input type="text" class="form-control" id="s_date" name="s_date" 
                                   value="{{ request('s_date') }}" placeholder="dd/mm/yyyy">
                        </div>
                        <div class="col-md-2">
                            <label for="e_date" class="form-label">End Date</label>
                            <input type="text" class="form-control" id="e_date" name="e_date" 
                                   value="{{ request('e_date') }}" placeholder="dd/mm/yyyy">
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Loan Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="1" {{ request('type') == '1' ? 'selected' : '' }}>Personal Loans</option>
                                <option value="2" {{ request('type') == '2' ? 'selected' : '' }}>Group Loans</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="run" value="1" class="btn btn-primary me-2">Run</button>
                            @if(isset($data['loans']) && $data['loans']->count() > 0)
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
                        <p class="mb-3">{!! $data['rep_msg'] ?? '' !!}</p>
                    @endif

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="disbursedLoansTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Loan ID</th>
                                    <th>Member</th>
                                    <th>Loan Type</th>
                                    <th>Branch</th>
                                    <th>Amount</th>
                                    <th>Interest (%)</th>
                                    <th>Period</th>
                                    <th>Date Disbursed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($data['loans']) && $data['loans']->count() > 0)
                                    @foreach($data['loans'] as $index => $loan)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>#{{ $loan->id }}</strong></td>
                                            <td>
                                                <div>
                                                    <strong>{{ $loan->mname }}</strong>
                                                    @if($loan->member_id)
                                                        <br><small class="text-muted">Member ID: {{ $loan->member_id }}</small>
                                                    @elseif($loan->group_id)
                                                        <br><small class="text-muted">Group ID: {{ $loan->group_id }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($loan->loan_type == 'Personal Loan')
                                                    <span class="badge bg-primary">Personal Loan</span>
                                                @elseif($loan->loan_type == 'Group Loan')
                                                    <span class="badge bg-info">Group Loan</span>
                                                @else
                                                    <span class="badge bg-secondary">Unknown</span>
                                                @endif
                                            </td>
                                            <td>{{ $loan->branch_name ?? 'N/A' }}</td>
                                            <td><strong>{{ number_format($loan->principal) }} UGX</strong></td>
                                            <td>{{ $loan->interest ?? 'N/A' }}%</td>
                                            <td>{{ $loan->period ?? 'N/A' }} months</td>
                                            <td>
                                                @if($loan->datecreated)
                                                    {{ \Carbon\Carbon::parse($loan->datecreated)->format('d M Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td><span class="badge bg-success">Disbursed</span></td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="mdi mdi-inbox-outline fs-1 text-muted mb-2"></i>
                                                <p class="text-muted">No disbursed loans found for the selected criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    @if(isset($data['loans']) && $data['loans']->count() > 0)
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                Showing {{ $data['loans']->count() }} disbursed loans
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
