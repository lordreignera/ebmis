@extends('layouts.admin')

@section('title', 'UMRA Schedule 3 - Risk Classification of Assets and Advances')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title">UMRA Schedule 3</h4>
                    <p class="text-muted mb-0">Risk Classification of Assets and Advances</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.umra.schedule3.export') }}" class="btn btn-sm btn-outline-success">
                        <i class="mdi mdi-download"></i> Download Risk Classification
                    </a>
                    <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Generate Date -->
                <div class="alert alert-info mb-4">
                    <strong>Report Generated:</strong> {{ $generatedDate->format('d M Y H:i') }}
                    <br>
                    <small>Per UMRA Tier 4 ND-MFI Regulations 2018, Regulation 20(2). Classification uses the stricter result between days past due and overdue instalment count.</small>
                </div>

                <div class="mb-4">
                    <h5 class="mb-3">Portfolio Ageing Report</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>Classification</th>
                                    <th>No. of A/Cs</th>
                                    <th>Outstanding Loan Portfolio (UGX)</th>
                                    <th>Required Provision</th>
                                    <th>Required Provision Amount (UGX)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedule3Summary['standard'] as $index => $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row['classification'] }}</td>
                                    <td>{{ $row['accounts'] }}</td>
                                    <td>{{ number_format($row['outstanding'], 2) }}</td>
                                    <td>{{ number_format($row['provision_rate'] * 100, 0) }}%</td>
                                    <td>{{ number_format($row['required_provision'], 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td></td>
                                    <td>Sub Total</td>
                                    <td>{{ $schedule3Summary['standard_total']['accounts'] }}</td>
                                    <td>{{ number_format($schedule3Summary['standard_total']['outstanding'], 2) }}</td>
                                    <td></td>
                                    <td>{{ number_format($schedule3Summary['standard_total']['required_provision'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="fw-bold">Rescheduling or reclassification of loans</td>
                                </tr>
                                @foreach($schedule3Summary['rescheduled'] as $row)
                                <tr>
                                    <td>{{ $loop->iteration + 5 }}</td>
                                    <td>{{ $row['classification'] }}</td>
                                    <td>{{ $row['accounts'] }}</td>
                                    <td>{{ number_format($row['outstanding'], 2) }}</td>
                                    <td>{{ number_format($row['provision_rate'] * 100, 0) }}%</td>
                                    <td>{{ number_format($row['required_provision'], 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td></td>
                                    <td>Sub Total</td>
                                    <td>{{ $schedule3Summary['rescheduled_total']['accounts'] }}</td>
                                    <td>{{ number_format($schedule3Summary['rescheduled_total']['outstanding'], 2) }}</td>
                                    <td></td>
                                    <td>{{ number_format($schedule3Summary['rescheduled_total']['required_provision'], 2) }}</td>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <td></td>
                                    <td>GRAND TOTAL</td>
                                    <td>{{ $schedule3Summary['grand_total']['accounts'] }}</td>
                                    <td>{{ number_format($schedule3Summary['grand_total']['outstanding'], 2) }}</td>
                                    <td></td>
                                    <td>{{ number_format($schedule3Summary['grand_total']['required_provision'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Risk Classification Summary -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center p-3 border-start border-4 border-success">
                            <h2 class="text-success">{{ count($riskClassifications['performing']) }}</h2>
                            <small class="text-muted">PERFORMING</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center p-3 border-start border-4 border-info">
                            <h2 class="text-info">{{ count($riskClassifications['watch']) }}</h2>
                            <small class="text-muted">WATCH (1-30 days or 1 instalment)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center p-3 border-start border-4 border-warning">
                            <h2 class="text-warning">{{ count($riskClassifications['substandard']) }}</h2>
                            <small class="text-muted">SUBSTANDARD (31-90 days or 2-6 instalments)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center p-3 border-start border-4 border-danger">
                            <h2 class="text-danger">{{ count($riskClassifications['doubtful']) }}</h2>
                            <small class="text-muted">DOUBTFUL (91-180 days or 4-6 instalments)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="text-center p-3 border-start border-4 border-dark">
                            <h2 class="text-dark">{{ count($riskClassifications['loss']) }}</h2>
                            <small class="text-muted">LOSS (&gt;180 days or &gt;6 instalments)</small>
                        </div>
                    </div>
                </div>

                <!-- Tabs for each risk category -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#performing" role="tab">
                            <i class="mdi mdi-check-circle text-success"></i> Performing ({{ count($riskClassifications['performing']) }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#watch" role="tab">
                            <i class="mdi mdi-alert-circle text-info"></i> Watch ({{ count($riskClassifications['watch']) }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#substandard" role="tab">
                            <i class="mdi mdi-alert text-warning"></i> Substandard ({{ count($riskClassifications['substandard']) }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#doubtful" role="tab">
                            <i class="mdi mdi-alert-octagon text-danger"></i> Doubtful ({{ count($riskClassifications['doubtful']) }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#loss" role="tab">
                            <i class="mdi mdi-close-circle text-danger"></i> Loss ({{ count($riskClassifications['loss']) }})
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- PERFORMING -->
                    <div class="tab-pane fade show active" id="performing" role="tabpanel">
                        @if(count($riskClassifications['performing']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Member</th>
                                            <th>Branch</th>
                                            <th>Assigned Officer</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Overdue Inst.</th>
                                            <th>Basis</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($riskClassifications['performing'] as $loan)
                                        <tr>
                                            <td><strong>{{ $loan->code ?? $loan->id }}</strong></td>
                                            <td>{{ $loan->member->full_name ?? 'N/A' }}</td>
                                            <td>{{ $loan->branch->name ?? 'Unknown' }}</td>
                                            <td>{{ $loan->assignedTo->name ?? 'Unassigned' }}</td>
                                            <td>{{ number_format($loan->principal, 2) }}</td>
                                            <td>{{ number_format($loan->interest, 2) }}</td>
                                            <td>{{ $loan->umra_overdue_installments }}</td>
                                            <td><small>{{ $loan->umra_classification_basis }}</small></td>
                                            <td><span class="badge bg-success">Performing</span></td>
                                            <td>
                                                <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-xs btn-outline-primary" title="View repayment schedules">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">No performing loans.</div>
                        @endif
                    </div>

                    <!-- WATCH -->
                    <div class="tab-pane fade" id="watch" role="tabpanel">
                        @if(count($riskClassifications['watch']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-info">
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Member</th>
                                            <th>Branch</th>
                                            <th>Assigned Officer</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Days Overdue</th>
                                            <th>Overdue Inst.</th>
                                            <th>Basis</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($riskClassifications['watch'] as $loan)
                                        <tr>
                                            <td><strong>{{ $loan->code ?? $loan->id }}</strong></td>
                                            <td>{{ $loan->member->full_name ?? 'N/A' }}</td>
                                            <td>{{ $loan->branch->name ?? 'Unknown' }}</td>
                                            <td>{{ $loan->assignedTo->name ?? 'Unassigned' }}</td>
                                            <td>{{ number_format($loan->principal, 2) }}</td>
                                            <td>{{ number_format($loan->interest, 2) }}</td>
                                            <td><span class="badge bg-info">{{ $loan->umra_dpd }} days</span></td>
                                            <td>{{ $loan->umra_overdue_installments }}</td>
                                            <td><small>{{ $loan->umra_classification_basis }}</small></td>
                                            <td>
                                                <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-xs btn-outline-primary" title="View repayment schedules">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">No watch list loans.</div>
                        @endif
                    </div>

                    <!-- SUBSTANDARD -->
                    <div class="tab-pane fade" id="substandard" role="tabpanel">
                        @if(count($riskClassifications['substandard']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Member</th>
                                            <th>Branch</th>
                                            <th>Assigned Officer</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Days Overdue</th>
                                            <th>Overdue Inst.</th>
                                            <th>Basis</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($riskClassifications['substandard'] as $loan)
                                        <tr>
                                            <td><strong>{{ $loan->code ?? $loan->id }}</strong></td>
                                            <td>{{ $loan->member->full_name ?? 'N/A' }}</td>
                                            <td>{{ $loan->branch->name ?? 'Unknown' }}</td>
                                            <td>{{ $loan->assignedTo->name ?? 'Unassigned' }}</td>
                                            <td>{{ number_format($loan->principal, 2) }}</td>
                                            <td>{{ number_format($loan->interest, 2) }}</td>
                                            <td><span class="badge bg-warning">{{ $loan->umra_dpd }} days</span></td>
                                            <td>{{ $loan->umra_overdue_installments }}</td>
                                            <td><small>{{ $loan->umra_classification_basis }}</small></td>
                                            <td>
                                                <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-xs btn-outline-primary" title="View repayment schedules">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">No substandard loans.</div>
                        @endif
                    </div>

                    <!-- DOUBTFUL -->
                    <div class="tab-pane fade" id="doubtful" role="tabpanel">
                        @if(count($riskClassifications['doubtful']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Member</th>
                                            <th>Branch</th>
                                            <th>Assigned Officer</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Days Overdue</th>
                                            <th>Overdue Inst.</th>
                                            <th>Basis</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($riskClassifications['doubtful'] as $loan)
                                        <tr>
                                            <td><strong>{{ $loan->code ?? $loan->id }}</strong></td>
                                            <td>{{ $loan->member->full_name ?? 'N/A' }}</td>
                                            <td>{{ $loan->branch->name ?? 'Unknown' }}</td>
                                            <td>{{ $loan->assignedTo->name ?? 'Unassigned' }}</td>
                                            <td>{{ number_format($loan->principal, 2) }}</td>
                                            <td>{{ number_format($loan->interest, 2) }}</td>
                                            <td><span class="badge bg-danger">{{ $loan->umra_dpd }} days</span></td>
                                            <td>{{ $loan->umra_overdue_installments }}</td>
                                            <td><small>{{ $loan->umra_classification_basis }}</small></td>
                                            <td>
                                                <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-xs btn-outline-primary" title="View repayment schedules">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">No doubtful loans.</div>
                        @endif
                    </div>

                    <!-- LOSS -->
                    <div class="tab-pane fade" id="loss" role="tabpanel">
                        @if(count($riskClassifications['loss']) > 0)
                            <div class="alert alert-danger">
                                <strong>Loss Classification:</strong> Loans over 180 days overdue
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Member</th>
                                            <th>Branch</th>
                                            <th>Assigned Officer</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Days Overdue</th>
                                            <th>Overdue Inst.</th>
                                            <th>Basis</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($riskClassifications['loss'] as $loan)
                                        <tr class="table-danger">
                                            <td><strong>{{ $loan->code ?? $loan->id }}</strong></td>
                                            <td>{{ $loan->member->full_name ?? 'N/A' }}</td>
                                            <td>{{ $loan->branch->name ?? 'Unknown' }}</td>
                                            <td>{{ $loan->assignedTo->name ?? 'Unassigned' }}</td>
                                            <td>{{ number_format($loan->principal, 2) }}</td>
                                            <td>{{ number_format($loan->interest, 2) }}</td>
                                            <td><span class="badge bg-danger">{{ $loan->umra_dpd }} days</span></td>
                                            <td>{{ $loan->umra_overdue_installments }}</td>
                                            <td><small>{{ $loan->umra_classification_basis }}</small></td>
                                            <td>
                                                <a href="{{ route('admin.loans.repayments.schedules', $loan->id) }}" class="btn btn-xs btn-outline-primary" title="View repayment schedules">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">No loans in loss classification.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
