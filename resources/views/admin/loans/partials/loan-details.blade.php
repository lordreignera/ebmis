<div class="loan-details-content">
    <!-- Loan Header -->
    <div class="card border-0 mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="mdi mdi-account-circle text-primary me-2"></i>Borrower Information</h5>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted" width="40%">Name:</td>
                            <td class="fw-semibold">{{ $borrowerName }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Loan Code:</td>
                            <td class="fw-semibold">{{ $loan->code }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Product:</td>
                            <td>{{ $loan->product->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Branch:</td>
                            <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="mdi mdi-cash-multiple text-success me-2"></i>Loan Details</h5>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted" width="40%">Principal:</td>
                            <td class="fw-semibold">UGX {{ number_format($loan->principal, 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Interest Rate:</td>
                            <td>{{ $loan->interest }}%</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Period:</td>
                            <td>{{ $loan->period }} installments</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status:</td>
                            <td>
                                @if($loan->status == 0)
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($loan->status == 1)
                                    <span class="badge bg-info">Approved</span>
                                @elseif($loan->status == 2)
                                    <span class="badge bg-primary">Disbursed</span>
                                @elseif($loan->status == 3)
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="card border-0 mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="mdi mdi-chart-line me-2"></i>Payment Summary</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="p-3 border-end">
                        <p class="text-muted mb-1">Total Loan</p>
                        <h4 class="text-primary mb-0">UGX {{ number_format($loan->principal, 0) }}</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border-end">
                        <p class="text-muted mb-1">Total Paid</p>
                        <h4 class="text-success mb-0">UGX {{ number_format($totalPaid, 0) }}</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <p class="text-muted mb-1">Balance</p>
                        <h4 class="text-danger mb-0">UGX {{ number_format($loan->principal - $totalPaid, 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Payment -->
    @if($nextDue)
    <div class="card border-0 mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="mdi mdi-calendar-clock me-2"></i>Next Payment Due</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-1 text-muted">Due Date</p>
                    <h5 class="mb-0">{{ \Carbon\Carbon::parse($nextDue->payment_date)->format('d M Y') }}</h5>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-1 text-muted">Amount Due</p>
                    <h4 class="mb-0 text-primary">UGX {{ number_format($nextDue->payment, 0) }}</h4>
                </div>
            </div>
            @if(\Carbon\Carbon::parse($nextDue->payment_date)->isPast())
                <div class="alert alert-danger mt-3 mb-0">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    This payment is <strong>{{ \Carbon\Carbon::parse($nextDue->payment_date)->diffForHumans() }}</strong>
                </div>
            @else
                <div class="alert alert-info mt-3 mb-0">
                    <i class="mdi mdi-information me-2"></i>
                    Payment due <strong>{{ \Carbon\Carbon::parse($nextDue->payment_date)->diffForHumans() }}</strong>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Payment Schedule -->
    @if($schedules->count() > 0)
    <div class="card border-0">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="mdi mdi-format-list-bulleted me-2"></i>Upcoming Payments ({{ $schedules->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Principal</th>
                            <th class="text-end">Interest</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $schedule)
                        <tr class="{{ \Carbon\Carbon::parse($schedule->payment_date)->isPast() ? 'table-danger' : '' }}">
                            <td>{{ \Carbon\Carbon::parse($schedule->payment_date)->format('d M Y') }}</td>
                            <td class="text-end">{{ number_format($schedule->principal, 0) }}</td>
                            <td class="text-end">{{ number_format($schedule->interest, 0) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($schedule->payment, 0) }}</td>
                            <td class="text-center">
                                @if(\Carbon\Carbon::parse($schedule->payment_date)->isPast())
                                    <span class="badge bg-danger">Overdue</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-success">
        <i class="mdi mdi-check-circle me-2"></i>
        All payments have been completed!
    </div>
    @endif
</div>

<style>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
