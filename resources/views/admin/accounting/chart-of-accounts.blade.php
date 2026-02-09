@extends('layouts.admin')

@section('title', 'Chart of Accounts')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Chart of Accounts</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Chart of Accounts</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.accounting.chart-of-accounts.download', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn btn-info"><i class="mdi mdi-download me-1"></i>Download PDF</a>
                <button class="btn btn-success" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print</button>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.accounting.chart-of-accounts') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Filter Period</label>
                        <select class="form-select" id="period_filter" onchange="updateDateInputs()">
                            <option value="all" {{ !request('start_date') && !request('end_date') ? 'selected' : '' }}>All Time</option>
                            <option value="current_month" {{ request('period') == 'current_month' ? 'selected' : '' }}>Current Month</option>
                            <option value="last_month" {{ request('period') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="current_quarter" {{ request('period') == 'current_quarter' ? 'selected' : '' }}>Current Quarter</option>
                            <option value="last_quarter" {{ request('period') == 'last_quarter' ? 'selected' : '' }}>Last Quarter</option>
                            <option value="current_year" {{ request('period') == 'current_year' ? 'selected' : '' }}>Current Year</option>
                            <option value="custom" {{ request('start_date') || request('end_date') ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" value="{{ request('end_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-filter me-1"></i>Apply Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Filter Active Indicator -->
@if(request('end_date'))
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="mdi mdi-information me-2"></i>
            <strong>Date Filter Active:</strong> Showing account balances as of <strong>{{ request('end_date') }}</strong>
            @if(request('start_date'))
                (from {{ request('start_date') }})
            @endif
            <a href="{{ route('admin.accounting.chart-of-accounts') }}" class="alert-link ms-2">Clear Filter</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

<script>
function updateDateInputs() {
    const period = document.getElementById('period_filter').value;
    const today = new Date();
    let startDate = '';
    let endDate = today.toISOString().split('T')[0];
    
    if (period === 'all') {
        startDate = '';
        endDate = '';
    } else if (period === 'current_month') {
        startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    } else if (period === 'last_month') {
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        startDate = lastMonth.toISOString().split('T')[0];
        endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
    } else if (period === 'current_quarter') {
        const quarter = Math.floor(today.getMonth() / 3);
        startDate = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
    } else if (period === 'last_quarter') {
        const quarter = Math.floor(today.getMonth() / 3) - 1;
        const year = quarter < 0 ? today.getFullYear() - 1 : today.getFullYear();
        const adjustedQuarter = quarter < 0 ? 3 : quarter;
        startDate = new Date(year, adjustedQuarter * 3, 1).toISOString().split('T')[0];
        endDate = new Date(year, (adjustedQuarter + 1) * 3, 0).toISOString().split('T')[0];
    } else if (period === 'current_year') {
        startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
    }
    
    document.getElementById('start_date').value = startDate;
    document.getElementById('end_date').value = endDate;
}
</script>

<!-- Chart of Accounts -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3 class="mb-0">EBMIS Chart of Accounts</h3>
                    <p class="text-muted">
                        @if(request('start_date') || request('end_date'))
                            Balance as of {{ request('end_date', date('Y-m-d')) }}
                            @if(request('start_date'))
                                (Transactions from {{ request('start_date') }} to {{ request('end_date', date('Y-m-d')) }})
                            @endif
                        @else
                            Complete Account Hierarchy - All Time
                        @endif
                    </p>
                </div>

                @foreach($accounts as $category => $categoryAccounts)
                <div class="mb-4">
                    <h5 class="bg-primary text-white p-2 mb-0">
                        <i class="mdi mdi-folder me-2"></i>{{ strtoupper($category) }}
                    </h5>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="12%">Code</th>
                                    <th width="12%">Sub Code</th>
                                    <th width="40%">Account Name</th>
                                    <th width="12%">Type</th>
                                    <th width="12%">Current Balance</th>
                                    <th width="12%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryAccounts as $account)
                                <tr class="{{ $account->parent_account ? '' : 'table-secondary fw-bold' }}">
                                    <td>{{ $account->code }}</td>
                                    <td>{{ $account->sub_code ?? '-' }}</td>
                                    <td>
                                        @if($account->parent_account)
                                        <span class="ms-3">└─</span>
                                        @endif
                                        {{ $account->name }}
                                        @if($account->is_cash_bank)
                                        <span class="badge badge-success badge-sm ms-1">Cash/Bank</span>
                                        @endif
                                    </td>
                                    <td>{{ $account->category }}</td>
                                    <td class="text-end">
                                        @if($account->running_balance != 0)
                                        <span class="{{ $account->running_balance > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format(abs($account->running_balance), 2) }}
                                        </span>
                                        @else
                                        <span class="text-muted">0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->status == 1)
                                        <span class="badge badge-success">Active</span>
                                        @else
                                        <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach

                <div class="mt-4">
                    <h5>Summary Statistics</h5>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h2>{{ $accounts->flatten()->count() }}</h2>
                                    <p class="mb-0">Total Accounts</p>
                                </div>
                            </div>
                        </div>
                        @foreach($accounts as $category => $categoryAccounts)
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h2>{{ $categoryAccounts->count() }}</h2>
                                    <p class="mb-0">{{ $category }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i><strong>Chart of Accounts:</strong> A complete listing of all general ledger accounts used in your organization. Each account has a unique code and is categorized as Asset, Liability, Equity, Income, or Expense.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
