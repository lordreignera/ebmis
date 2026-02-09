@extends('layouts.admin')

@section('title', 'Income Statement')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Income Statement (Profit & Loss)</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Income Statement</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.accounting.income-statement') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <label><i class="mdi mdi-calendar me-1"></i>Date From</label>
                        <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label><i class="mdi mdi-calendar me-1"></i>Date To</label>
                        <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-refresh me-1"></i>Refresh</button>
                        <button type="button" class="btn btn-success" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print</button>
                        <a href="{{ route('admin.accounting.income-statement.download', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-info"><i class="mdi mdi-download me-1"></i>Download PDF</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Income Statement Report -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3 class="mb-0">EBMIS Income Statement</h3>
                    <p class="text-muted">
                        For the Period: {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}
                    </p>
                </div>

                <!-- INCOME SECTION -->
                <h5 class="bg-success text-white p-2 mb-0">
                    <i class="mdi mdi-cash-plus me-2"></i>REVENUE / INCOME
                </h5>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        @foreach($income as $incomeAccount)
                        @if($incomeAccount->balance != 0)
                        <tr class="{{ $incomeAccount->parent_id ? '' : 'table-light fw-bold' }}">
                            <td width="15%">{{ $incomeAccount->code }} {{ $incomeAccount->sub_code ? '- ' . $incomeAccount->sub_code : '' }}</td>
                            <td width="55%">
                                @if($incomeAccount->parent_id)
                                <span class="ms-3">└─</span>
                                @endif
                                {{ $incomeAccount->name }}
                            </td>
                            <td width="30%" class="text-end">
                                {{ number_format($incomeAccount->balance, 2) }}
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="2">Total Revenue</th>
                            <th class="text-end">UGX {{ number_format($totalIncome, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>

                <!-- EXPENSES SECTION -->
                <h5 class="bg-danger text-white p-2 mb-0">
                    <i class="mdi mdi-cash-minus me-2"></i>EXPENSES
                </h5>
                <table class="table table-sm table-bordered mb-4">
                    <tbody>
                        @if($expenses->count() > 0 && $expenses->sum('balance') > 0)
                        @foreach($expenses as $expenseAccount)
                        @if($expenseAccount->balance != 0)
                        <tr class="{{ $expenseAccount->parent_id ? '' : 'table-light fw-bold' }}">
                            <td width="15%">{{ $expenseAccount->code }} {{ $expenseAccount->sub_code ? '- ' . $expenseAccount->sub_code : '' }}</td>
                            <td width="55%">
                                @if($expenseAccount->parent_id)
                                <span class="ms-3">└─</span>
                                @endif
                                {{ $expenseAccount->name }}
                            </td>
                            <td width="30%" class="text-end">
                                {{ number_format($expenseAccount->balance, 2) }}
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        @else
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                <i class="mdi mdi-information me-1"></i>No expenses recorded for this period
                            </td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="2">Total Expenses</th>
                            <th class="text-end">UGX {{ number_format($totalExpenses, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>

                <!-- NET INCOME -->
                <table class="table table-bordered">
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="2">
                                <h5 class="mb-0">NET INCOME (PROFIT/LOSS)</h5>
                            </th>
                            <th class="text-end">
                                <h5 class="mb-0 {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                    UGX {{ number_format($netIncome, 2) }}
                                </h5>
                            </th>
                        </tr>
                    </tfoot>
                </table>

                <!-- Performance Indicator -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert {{ $netIncome >= 0 ? 'alert-success' : 'alert-warning' }}">
                            <i class="mdi {{ $netIncome >= 0 ? 'mdi-trending-up' : 'mdi-trending-down' }} me-2"></i>
                            <strong>Performance:</strong> 
                            @if($netIncome >= 0)
                            The organization generated a profit of UGX {{ number_format($netIncome, 2) }} during this period.
                            @else
                            The organization recorded a loss of UGX {{ number_format(abs($netIncome), 2) }} during this period.
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6 class="mb-0">Total Revenue</h6>
                                <h3 class="mb-0">{{ number_format($totalIncome, 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h6 class="mb-0">Total Expenses</h6>
                                <h3 class="mb-0">{{ number_format($totalExpenses, 0) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card {{ $netIncome >= 0 ? 'bg-primary' : 'bg-warning' }} text-white">
                            <div class="card-body text-center">
                                <h6 class="mb-0">Net {{ $netIncome >= 0 ? 'Profit' : 'Loss' }}</h6>
                                <h3 class="mb-0">{{ number_format(abs($netIncome), 0) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i><strong>Income Statement:</strong> Shows the financial performance over a period of time. Revenue minus Expenses equals Net Income (Profit or Loss).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
