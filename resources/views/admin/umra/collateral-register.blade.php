@extends('layouts.admin')

@section('title', 'UMRA Collateral Register')

@section('content')
@php
    $activeQuery = array_filter([
        'q' => $filters['q'] ?? '',
        'branch' => $filters['branch'] ?? '',
        'collateral_status' => $filters['collateral_status'] ?? 'all',
    ], fn ($value) => $value !== '' && $value !== null && $value !== 'all');
@endphp
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title">UMRA Collateral Register</h4>
                    <p class="text-muted mb-0">
                        Collateral register generated {{ $generatedDate->format('d M Y H:i') }}.
                        Showing {{ number_format($collateralRegister->count()) }} of {{ number_format($totalCollateralRows ?? $collateralRegister->count()) }} rows.
                    </p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.umra.collateral-register.export', $activeQuery) }}" class="btn btn-sm btn-outline-success">
                        <i class="mdi mdi-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.umra.collateral-register.pdf', $activeQuery) }}" class="btn btn-sm btn-outline-danger">
                        <i class="mdi mdi-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.umra.collateral-register') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Loan, client, officer, collateral...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Collateral Status</label>
                        <select name="collateral_status" class="form-select">
                            @foreach(($filterOptions['statuses'] ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['collateral_status'] ?? 'all') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select name="branch" class="form-select">
                            <option value="">All branches</option>
                            @foreach(($filterOptions['branches'] ?? []) as $branch)
                                <option value="{{ $branch }}" {{ ($filters['branch'] ?? '') === $branch ? 'selected' : '' }}>
                                    {{ $branch }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.umra.collateral-register') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Loan Account</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Assigned Officer</th>
                            <th>Collateral Type</th>
                            <th>Description</th>
                            <th>Collateral Values</th>
                            <th>Source / Reference</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collateralRegister as $collateral)
                        <tr>
                            <td><strong>{{ $collateral['loan_account_no'] }}</strong></td>
                            <td>{{ $collateral['client_name'] }}</td>
                            <td>{{ $collateral['branch'] }}</td>
                            <td>{{ $collateral['assigned_officer'] }}</td>
                            <td>{{ $collateral['collateral_type'] }}</td>
                            <td>{{ $collateral['description'] }}</td>
                            <td>
                                <div>
                                    <span class="text-muted small d-block">Estimated</span>
                                    @if($collateral['estimated_value'] !== null)
                                        <strong>{{ number_format($collateral['estimated_value'], 2) }}</strong>
                                    @else
                                        <span class="text-muted">Not valued</span>
                                    @endif
                                </div>
                                <div class="mt-1">
                                    <span class="text-muted small d-block">FSV</span>
                                    @if(($collateral['forced_sale_value'] ?? null) !== null)
                                        <strong>{{ number_format($collateral['forced_sale_value'], 2) }}</strong>
                                    @else
                                        <span class="text-muted">Not valued</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $collateral['source'] }}</td>
                            <td>
                                @php
                                    $statusClass = match ($collateral['status']) {
                                        'Registered', 'Documented', 'Paid' => 'bg-success',
                                        'Pending' => 'bg-warning text-dark',
                                        'Missing', 'Missing document' => 'bg-danger',
                                        'Returned' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $collateral['status'] }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.loans.repayments.schedules', $collateral['loan_id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No collateral records found for active loans.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
