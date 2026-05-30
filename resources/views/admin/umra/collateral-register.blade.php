@extends('layouts.admin')

@section('title', 'UMRA Collateral Register')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title">UMRA Collateral Register</h4>
                    <p class="text-muted mb-0">Collateral register generated {{ $generatedDate->format('d M Y H:i') }}</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Loan Account</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Assigned Officer</th>
                            <th>Collateral Type</th>
                            <th>Description</th>
                            <th>Estimated Value</th>
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
                                @if($collateral['estimated_value'] !== null)
                                    {{ number_format($collateral['estimated_value'], 2) }}
                                @else
                                    <span class="text-muted">Not valued</span>
                                @endif
                            </td>
                            <td>{{ $collateral['source'] }}</td>
                            <td><span class="badge bg-secondary">{{ $collateral['status'] }}</span></td>
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
@endsection
