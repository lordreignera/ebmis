@extends('layouts.admin')

@section('title', $loanTypeDisplay . ' Loan Portfolio')

@section('content')
@php
    $statusMeta = [
        0 => ['Pending', 'warning', 'text-dark'],
        1 => ['Approved', 'info', 'text-dark'],
        2 => ['Active / Disbursed', 'success', ''],
        3 => ['Closed', 'secondary', ''],
        4 => ['Rejected', 'danger', ''],
    ];
@endphp
<div class="container-fluid">
    @include('admin.partials.page-header', [
        'title' => $loanTypeDisplay . ' Loan Portfolio',
        'subtitle' => 'Lifecycle totals and loan records for ' . strtolower($loanTypeDisplay) . ' lending.',
        'icon' => 'mdi mdi-school',
        'backFallback' => route('admin.modules.dashboard'),
        'backLabel' => 'Back to EBIMS Modules',
    ])

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">TOTAL LOANS</div><div class="h3 mb-0">{{ number_format($stats['total_loans']) }}</div></div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small fw-bold">PRINCIPAL</div><div class="h5 mb-0">UGX {{ number_format($stats['total_principal'], 0) }}</div></div></div></div>
        @foreach([0 => 'pending', 1 => 'approved', 2 => 'disbursed', 3 => 'completed'] as $status => $key)
            <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><span class="badge bg-{{ $statusMeta[$status][1] }} {{ $statusMeta[$status][2] }} mb-2">Status {{ $status }}</span><div class="h3 mb-0">{{ number_format($stats[$key]) }}</div><small class="text-muted">{{ $statusMeta[$status][0] }}</small></div></div></div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ $loanTypeDisplay }} loans</h5>
                <small class="text-muted">Status values match the main loan lifecycle.</small>
            </div>
            <span class="badge bg-danger">{{ number_format($stats['rejected']) }} rejected</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Loan</th><th>Borrower</th><th>Product</th><th>Branch</th><th class="text-end">Principal</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($loans as $loan)
                            @php
                                $meta = $statusMeta[(int) $loan->status] ?? ['Unknown', 'light', 'text-dark'];
                                $borrower = match($loanType) {
                                    'school' => $loan->school->school_name ?? 'Unknown school',
                                    'student' => trim(($loan->student->first_name ?? '') . ' ' . ($loan->student->last_name ?? '')) ?: ($loan->school->school_name ?? 'Unknown student'),
                                    'staff' => trim(($loan->staff->first_name ?? '') . ' ' . ($loan->staff->last_name ?? '')) ?: ($loan->school->school_name ?? 'Unknown staff member'),
                                    default => 'Unknown borrower',
                                };
                            @endphp
                            <tr>
                                <td><strong>{{ $loan->code }}</strong></td>
                                <td>{{ $borrower }}</td>
                                <td>{{ $loan->product->name ?? 'N/A' }}</td>
                                <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                                <td class="text-end fw-semibold">UGX {{ number_format((float) $loan->principal, 0) }}</td>
                                <td><span class="badge bg-{{ $meta[1] }} {{ $meta[2] }}">{{ $meta[0] }}</span></td>
                                <td>{{ optional($loan->datecreated)->format('Y-m-d') ?? optional($loan->created_at)->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No {{ strtolower($loanTypeDisplay) }} loans are recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
