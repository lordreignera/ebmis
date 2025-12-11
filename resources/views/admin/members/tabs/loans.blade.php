<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="mdi mdi-bank"></i> Loan History</h5>
    <a href="{{ route('admin.loans.create', ['member_id' => $member->id]) }}" class="btn btn-success btn-sm">
        <i class="mdi mdi-plus"></i> Apply for Loan
    </a>
</div>

@if($member->personalLoans->count() > 0)
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <tr>
                    <th style="color: white;">LOAN CODE</th>
                    <th style="color: white;">PRODUCT</th>
                    <th style="color: white;">PRINCIPAL</th>
                    <th style="color: white;">INTEREST</th>
                    <th style="color: white;">PERIOD</th>
                    <th style="color: white;">STATUS</th>
                    <th style="color: white;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($member->personalLoans as $loan)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $loan->code }}</span></td>
                        <td>{{ $loan->product->name ?? 'N/A' }}</td>
                        <td>UGX {{ number_format($loan->principal, 2) }}</td>
                        <td>{{ $loan->interest }}%</td>
                        <td>{{ $loan->period }}</td>
                        <td>
                            @php
                                $actualStatus = $loan->getActualStatus();
                                $badges = [
                                    'pending' => '<span class="badge bg-warning">Pending</span>',
                                    'approved' => '<span class="badge bg-info">Approved</span>',
                                    'running' => '<span class="badge bg-success">Running</span>',
                                    'closed' => '<span class="badge bg-secondary">Closed</span>',
                                    'rejected' => '<span class="badge bg-danger">Rejected</span>',
                                ];
                            @endphp
                            {!! $badges[$actualStatus] ?? '<span class="badge bg-light text-dark">Unknown</span>' !!}
                        </td>
                        <td>
                            @php
                                // Determine the correct route based on loan status
                                $loanType = 'personal'; // Assuming personal loans
                                if ($actualStatus === 'pending') {
                                    $viewUrl = route('admin.loans.show', ['id' => $loan->id]) . '?type=' . $loanType;
                                } elseif ($actualStatus === 'rejected') {
                                    $viewUrl = route('admin.loans.rejected') . '?loan_id=' . $loan->id;
                                } else {
                                    $viewUrl = route('admin.loans.show', ['id' => $loan->id]) . '?type=' . $loanType;
                                }
                            @endphp
                            <a href="{{ $viewUrl }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="mdi mdi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        <i class="mdi mdi-information"></i> No loan history. Click "Apply for Loan" to create a new loan application.
    </div>
@endif

<div class="mt-4">
    <h6>Loan Summary</h6>
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Total Loans</h6>
                    <h4>{{ $member->personalLoans->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Active Loans</h6>
                    <h4>{{ $member->personalLoans->whereIn('status', [1, 2])->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Total Borrowed</h6>
                    <h4>{{ number_format($member->personalLoans->sum('principal'), 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>Outstanding</h6>
                    <h4>{{ number_format($member->personalLoans->sum('outstanding_balance'), 0) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
