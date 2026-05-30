@extends('layouts.admin')

@section('title', 'UMRA Internal Prudential Pack')

@push('styles')
<style>
    .prudential-page {
        color: #0f172a;
    }

    .prudential-header {
        align-items: flex-start;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .prudential-header h3 {
        font-weight: 800;
        margin-bottom: .25rem;
    }

    .prudential-section {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        margin-bottom: 1.25rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    .prudential-section-title {
        background: #020617;
        color: #fff;
        font-size: 1rem;
        font-weight: 800;
        padding: .65rem .85rem;
    }

    .prudential-table {
        margin: 0;
        table-layout: fixed;
        width: 100%;
    }

    .prudential-table th {
        background: #020617;
        color: #fff;
        font-weight: 800;
        padding: .65rem;
    }

    .prudential-table td {
        border-color: #d1d5db;
        padding: .55rem .65rem;
        vertical-align: middle;
    }

    .prudential-table .amount {
        font-variant-numeric: tabular-nums;
        text-align: right;
        white-space: nowrap;
    }

    .prudential-table .negative {
        color: #dc2626;
    }

    .prudential-note {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #475569;
        font-size: .85rem;
        margin-bottom: 1rem;
        padding: .85rem;
    }

    .prudential-filter {
        align-items: end;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 1rem;
        padding: .85rem;
    }

    .prudential-filter label {
        color: #475569;
        font-size: .78rem;
        font-weight: 700;
        margin-bottom: .25rem;
        text-transform: uppercase;
    }

    .prudential-mapping-list {
        color: #334155;
        font-size: .84rem;
        line-height: 1.55;
        margin: 0;
        padding-left: 1rem;
    }

    .prudential-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: .74rem;
        font-weight: 800;
        padding: .25rem .55rem;
        white-space: nowrap;
    }

    .prudential-status.mapped {
        background: #dcfce7;
        color: #166534;
    }

    .prudential-status.missing {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (max-width: 767px) {
        .prudential-header {
            flex-direction: column;
        }

        .prudential-filter {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@php
    $formatAmount = function ($value) {
        $value = (float) $value;
        $formatted = number_format(abs($value), 0);
        return $value < 0 ? '(' . $formatted . ')' : $formatted;
    };

    $mappingLabels = [
        'cash_bank' => 'Cash/Bank Balances',
        'fixed_assets' => 'Fixed Assets',
        'other_assets' => 'Other Assets',
        'borrowings' => 'Borrowings',
        'accounts_payable' => 'Accounts Payable',
        'other_liabilities' => 'Other Liabilities',
        'core_capital' => 'Core Capital',
        'retained_earnings' => 'Retained Earnings',
        'interest_income' => 'Interest Income',
        'fee_income' => 'Fee Income',
        'recovery_income' => 'Recovery Income',
        'operating_expenses' => 'Operating Expenses',
    ];
@endphp

@section('content')
<div class="container-fluid prudential-page">
    <div class="prudential-header">
        <div>
            <h3>BOU-Style Internal Prudential Pack</h3>
            <p class="text-muted mb-0">Management draft as at <strong>{{ $asOfDate->format('d M Y') }}</strong></p>
            <p class="text-muted mb-0">Income period: <strong>{{ $pack['period_label'] }}</strong></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="mdi mdi-arrow-left"></i> UMRA Dashboard
            </a>
            <a href="{{ route('admin.umra.prudential-pack.export', ['as_of_date' => $asOfDate->format('Y-m-d'), 'period' => $periodMode]) }}" class="btn btn-sm btn-outline-success">
                <i class="mdi mdi-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('admin.umra.prudential-pack.pdf', ['as_of_date' => $asOfDate->format('Y-m-d'), 'period' => $periodMode]) }}" class="btn btn-sm btn-outline-danger">
                <i class="mdi mdi-file-pdf-box"></i> Export PDF
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.umra.prudential-pack') }}" class="prudential-filter">
        <div>
            <label for="as_of_date">As of date</label>
            <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate->format('Y-m-d') }}" class="form-control form-control-sm">
        </div>
        <div>
            <label for="period">Income period</label>
            <select id="period" name="period" class="form-control form-control-sm">
                <option value="month" {{ $periodMode === 'month' ? 'selected' : '' }}>Current month only</option>
                <option value="ytd" {{ $periodMode === 'ytd' ? 'selected' : '' }}>Year to date</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="mdi mdi-filter"></i> Apply
            </button>
        </div>
        <div class="text-muted small">
            Balance sheet accounts remain as-of balances; income and expense accounts use the selected period.
        </div>
    </form>

    <div class="prudential-note">
        This is an internal management pack. Gross loan portfolio, allowance and PAR 30 are generated from the UMRA Schedule 3 loan classification engine. Cash, assets, liabilities, equity, income and expenses use posted GL balances where the chart of accounts is mapped.
    </div>

    <div class="prudential-section">
        <div class="prudential-section-title">Statement of Financial Position - Management Draft</div>
        <table class="table prudential-table">
            <thead>
                <tr>
                    <th>Assets</th>
                    <th class="amount">UGX</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pack['assets'] as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td class="amount {{ $row[1] < 0 ? 'negative' : '' }}">{{ $formatAmount($row[1]) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="prudential-section">
        <div class="prudential-section-title">Liabilities and Equity - Management Draft</div>
        <table class="table prudential-table">
            <thead>
                <tr>
                    <th>Liabilities and Equity</th>
                    <th class="amount">UGX</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pack['liabilities_equity'] as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td class="amount {{ $row[1] < 0 ? 'negative' : '' }}">{{ $formatAmount($row[1]) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="prudential-section">
        <div class="prudential-section-title">Income Statement - Management Draft</div>
        <table class="table prudential-table">
            <thead>
                <tr>
                    <th>Income / Expense</th>
                    <th class="amount">UGX</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pack['income_statement'] as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td class="amount {{ $row[1] < 0 ? 'negative' : '' }}">{{ $formatAmount($row[1]) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="prudential-section">
        <div class="prudential-section-title">Prudential Ratios - Internal Monitoring</div>
        <table class="table prudential-table">
            <thead>
                <tr>
                    <th>Ratio</th>
                    <th class="amount">Result</th>
                    <th>Comment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pack['ratios'] as $ratio)
                    <tr>
                        <td>{{ $ratio['label'] }}</td>
                        <td class="amount">{{ $ratio['result'] }}</td>
                        <td>{{ $ratio['comment'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="prudential-section">
        <div class="prudential-section-title">Confirmed Repayments by Payment Method</div>
        <table class="table prudential-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="amount">Transactions</th>
                    <th class="amount">UGX</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pack['payment_methods'] as $method)
                    <tr>
                        <td>{{ $method['label'] }}</td>
                        <td class="amount">{{ number_format($method['transactions']) }}</td>
                        <td class="amount">{{ $formatAmount($method['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($pack['sources']))
        <div class="prudential-note">
            <strong>Source note:</strong> {{ implode(' ', $pack['sources']) }}
        </div>
    @endif

    <div class="prudential-section">
        <div class="prudential-section-title">Chart of Accounts Mapping Check</div>
        <table class="table prudential-table">
            <thead>
                <tr>
                    <th style="width: 24%;">Report Bucket</th>
                    <th>Mapped GL Accounts</th>
                    <th style="width: 14%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mappingLabels as $key => $label)
                    @php
                        $accounts = $pack['mapping'][$key] ?? [];
                    @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td>
                            @if(!empty($accounts))
                                <ul class="prudential-mapping-list">
                                    @foreach($accounts as $account)
                                        <li>{{ $account }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">No active mapped account found.</span>
                            @endif
                        </td>
                        <td>
                            <span class="prudential-status {{ !empty($accounts) ? 'mapped' : 'missing' }}">
                                {{ !empty($accounts) ? 'Mapped' : 'Review' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
