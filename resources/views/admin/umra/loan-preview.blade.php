@extends('layouts.app')

@section('title', 'UMRA - Loan Preview & Risk Classification')

@section('content')

<div class="container-fluid">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1">
                UMRA Loan Preview & Risk Classification
            </h1>

            <p class="text-muted mb-1">
                Detailed loan-level UMRA risk assessment,
                provisioning, PAR monitoring and write-off review.
            </p>

            <small class="text-muted">
                Generated:
                {{ $generatedDate->format('d M Y H:i A') }}
            </small>
        </div>

        <div class="col-md-4 text-end">

            <a href="{{ route('admin.umra.dashboard') }}"
               class="btn btn-secondary btn-sm me-2">
                ← Dashboard
            </a>

            <button class="btn btn-primary btn-sm"
                    onclick="window.print()">
                🖨️ Print
            </button>

            <a href="{{ route('admin.umra.export-preview') }}"
               class="btn btn-success btn-sm">
                📥 Export CSV
            </a>

        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">
                        Total Active Loans
                    </h6>

                    <h3 class="mb-0">
                        {{ count($loanPreview) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">
                        Outstanding Principal
                    </h6>

                    <h4 class="mb-0">
                        UGX {{ number_format($loanPreview->sum('outstanding_principal'), 0) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">
                        Required Provision
                    </h6>

                    <h4 class="mb-0 text-danger">
                        UGX {{ number_format($loanPreview->sum('required_provision'), 0) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">
                        Restructured Loans
                    </h6>

                    <h3 class="mb-0">
                        {{ $loanPreview->where('is_restructured', true)->count() }}
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm border-0">

        <div class="card-header bg-light">
            <h5 class="mb-0">
                Filters
            </h5>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">
                        Classification
                    </label>

                    <select id="classification"
                            class="form-select"
                            onchange="filterTable()">

                        <option value="">
                            All
                        </option>

                        <option value="Performing">
                            Performing
                        </option>

                        <option value="Watch">
                            Watch
                        </option>

                        <option value="Substandard">
                            Substandard
                        </option>

                        <option value="Doubtful">
                            Doubtful
                        </option>

                        <option value="Loss">
                            Loss
                        </option>

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Branch
                    </label>

                    <select id="branch"
                            class="form-select"
                            onchange="filterTable()">

                        <option value="">
                            All Branches
                        </option>

                        @foreach($loanPreview->groupBy('branch')->keys() as $branch)
                            <option value="{{ $branch }}">
                                {{ $branch }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Loan Type
                    </label>

                    <select id="restructured"
                            class="form-select"
                            onchange="filterTable()">

                        <option value="">
                            All Loans
                        </option>

                        <option value="normal">
                            Normal
                        </option>

                        <option value="restructured">
                            Restructured
                        </option>

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Search
                    </label>

                    <input type="text"
                           id="search"
                           class="form-control"
                           placeholder="Client / Account"
                           onkeyup="filterTable()">
                </div>

            </div>

        </div>

    </div>

    <!-- Loan Table -->
    <div class="card shadow-sm border-0">

        <div class="table-responsive">

            <table class="table table-hover table-bordered align-middle mb-0"
                   id="loanPreviewTable">

                <thead class="table-light">

                    <tr>
                        <th>Client ID</th>
                        <th>Client Name</th>
                        <th>Loan Account</th>
                        <th>DPD</th>
                        <th>PAR Status</th>
                        <th>Classification</th>
                        <th class="text-end">Principal</th>
                        <th class="text-end">Interest</th>
                        <th class="text-end">Total Outstanding</th>
                        <th>Provision %</th>
                        <th class="text-end">Required Provision</th>
                        <th>Write-off Status</th>
                        <th>Branch</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($loanPreview as $loan)

                        <tr class="@if($loan['is_restructured']) table-primary @endif">

                            <td>
                                {{ $loan['client_id'] }}
                            </td>

                            <td>

                                {{ $loan['client_name'] }}

                                @if($loan['is_restructured'])
                                    <span class="badge bg-primary">
                                        Restructured
                                    </span>
                                @endif

                            </td>

                            <td>
                                <code>
                                    {{ $loan['loan_account_no'] }}
                                </code>
                            </td>

                            <!-- DPD -->
                            <td class="text-center">

                                @if($loan['dpd'] > 0)

                                    <span class="text-danger fw-bold">
                                        {{ $loan['dpd'] }} Days
                                    </span>

                                @else

                                    <span class="text-success fw-bold">
                                        Current
                                    </span>

                                @endif

                            </td>

                            <!-- PAR STATUS -->
                            <td>

                                @if($loan['dpd'] > 30)

                                    <span class="badge bg-danger">
                                        PAR30
                                    </span>

                                @else

                                    <span class="badge bg-success">
                                        Normal
                                    </span>

                                @endif

                            </td>

                            <!-- Classification -->
                            <td>

                                <span class="badge bg-{{ $loan['badge_color'] }}">
                                    {{ $loan['classification'] }}
                                </span>

                            </td>

                            <!-- Principal -->
                            <td class="text-end">
                                UGX {{ number_format($loan['outstanding_principal'], 0) }}
                            </td>

                            <!-- Interest -->
                            <td class="text-end">
                                UGX {{ number_format($loan['outstanding_interest'], 0) }}
                            </td>

                            <!-- Total -->
                            <td class="text-end fw-bold">
                                UGX {{ number_format($loan['total_outstanding'], 0) }}
                            </td>

                            <!-- Provision -->
                            <td class="text-center">
                                {{ $loan['provision_rate'] }}
                            </td>

                            <!-- Required Provision -->
                            <td class="text-end text-danger fw-bold">
                                UGX {{ number_format($loan['required_provision'], 0) }}
                            </td>

                            <!-- Writeoff -->
                            <td>

                                @if($loan['writeoff_basis'])

                                    <small class="text-danger">
                                        {{ $loan['writeoff_basis'] }}
                                    </small>

                                @else

                                    <small class="text-success">
                                        Active
                                    </small>

                                @endif

                            </td>

                            <!-- Branch -->
                            <td>
                                {{ $loan['branch'] }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="13"
                                class="text-center text-muted py-4">

                                No loans found.

                            </td>
                        </tr>

                    @endforelse

                </tbody>

                <tfoot class="table-light">

                    <tr>

                        <th colspan="6"
                            class="text-end">

                            TOTALS

                        </th>

                        <th class="text-end">
                            UGX {{ number_format($loanPreview->sum('outstanding_principal'), 0) }}
                        </th>

                        <th class="text-end">
                            UGX {{ number_format($loanPreview->sum('outstanding_interest'), 0) }}
                        </th>

                        <th class="text-end">
                            UGX {{ number_format($loanPreview->sum('total_outstanding'), 0) }}
                        </th>

                        <th></th>

                        <th class="text-end text-danger">
                            UGX {{ number_format($loanPreview->sum('required_provision'), 0) }}
                        </th>

                        <th colspan="2"></th>

                    </tr>

                </tfoot>

            </table>

        </div>

    </div>

    <!-- Risk Summary -->
    <div class="row mt-4">

        <div class="col-md-12 mb-3">
            <h5>
                Risk Classification Summary
            </h5>
        </div>

        @php

            $summary = [
                'Performing' => ['color' => 'success'],
                'Watch' => ['color' => 'info'],
                'Substandard' => ['color' => 'warning'],
                'Doubtful' => ['color' => 'danger'],
                'Loss' => ['color' => 'dark'],
            ];

        @endphp

        @foreach($summary as $class => $data)

            @php

                $classLoans = $loanPreview->filter(
                    fn($loan) => $loan['classification'] === $class
                );

            @endphp

            <div class="col-md-4 mb-3">

                <div class="card border-{{ $data['color'] }} shadow-sm">

                    <div class="card-body">

                        <h6 class="mb-2">
                            {{ $class }}
                        </h6>

                        <p class="mb-1">
                            <strong>Accounts:</strong>
                            {{ $classLoans->count() }}
                        </p>

                        <p class="mb-1">
                            <strong>Exposure:</strong>
                            UGX {{ number_format($classLoans->sum('total_outstanding'), 0) }}
                        </p>

                        <p class="mb-0">
                            <strong>Provision:</strong>
                            UGX {{ number_format($classLoans->sum('required_provision'), 0) }}
                        </p>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <!-- UMRA Notes -->
    <div class="alert alert-info mt-4">

        <h6 class="alert-heading">
            UMRA Risk Classification Guidelines
        </h6>

        <ul class="mb-0 small">

            <li>
                <strong>Performing:</strong>
                Current loans with minimal risk (1%)
            </li>

            <li>
                <strong>Watch:</strong>
                1–30 DPD with early repayment stress (5%)
            </li>

            <li>
                <strong>Substandard:</strong>
                31–90 DPD requiring close monitoring (25%)
            </li>

            <li>
                <strong>Doubtful:</strong>
                91–180 DPD with uncertain recovery (50%)
            </li>

            <li>
                <strong>Loss:</strong>
                Above 180 DPD considered unrecoverable (100%)
            </li>

        </ul>

    </div>

</div>

<script>

function filterTable()
{
    const classification = document
        .getElementById('classification')
        .value
        .toLowerCase();

    const branch = document
        .getElementById('branch')
        .value
        .toLowerCase();

    const restructured = document
        .getElementById('restructured')
        .value
        .toLowerCase();

    const search = document
        .getElementById('search')
        .value
        .toLowerCase();

    const rows = document
        .querySelectorAll('#loanPreviewTable tbody tr');

    rows.forEach(row => {

        let show = true;

        if (
            classification &&
            !row.cells[5].textContent.toLowerCase().includes(classification)
        ) {
            show = false;
        }

        if (
            branch &&
            !row.cells[12].textContent.toLowerCase().includes(branch)
        ) {
            show = false;
        }

        if (
            restructured === 'restructured' &&
            !row.classList.contains('table-primary')
        ) {
            show = false;
        }

        if (
            restructured === 'normal' &&
            row.classList.contains('table-primary')
        ) {
            show = false;
        }

        if (
            search &&
            !row.textContent.toLowerCase().includes(search)
        ) {
            show = false;
        }

        row.style.display = show ? '' : 'none';

    });
}

</script>

<style>

.table td,
.table th {
    vertical-align: middle;
    white-space: nowrap;
}

.card {
    border-radius: 10px;
}

.badge {
    font-size: 12px;
}

@media print {

    .btn,
    .card-header,
    .form-control,
    .form-select {
        display: none !important;
    }

    body {
        font-size: 11px;
    }

    table {
        font-size: 10px;
    }

}

</style>

@endsection