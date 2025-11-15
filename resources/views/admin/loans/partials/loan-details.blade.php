<!-- Compact Loan Details -->
<div class="loan-details-compact" style="background: #ffffff;">
    <!-- Header with Icon -->
    <div class="text-center mb-3">
        <div class="avatar-lg mx-auto mb-3" style="width: 60px; height: 60px;">
            <div class="avatar-title rounded-circle bg-primary-subtle text-primary fs-2">
                <i class="mdi mdi-cash-multiple"></i>
            </div>
        </div>
        <h5 class="mb-1 text-dark">{{ $borrowerName }}</h5>
        <p class="text-muted mb-0">{{ $loan->code }}</p>
    </div>

    <!-- Key Loan Info -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background-color: #f8f9fa;">
                <small class="text-muted d-block">Principal:</small>
                <strong class="text-primary">UGX {{ number_format($loan->principal, 0) }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background-color: #f8f9fa;">
                <small class="text-muted d-block">Interest Rate:</small>
                <strong class="text-dark">{{ $loan->interest }}%</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background-color: #f8f9fa;">
                <small class="text-muted d-block">Period:</small>
                <strong class="text-dark">{{ $loan->period }} installments</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background-color: #f8f9fa;">
                <small class="text-muted d-block">Status:</small>
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
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="card border mb-3" style="background-color: #ffffff;">
        <div class="card-body p-3">
            <h6 class="mb-3 text-dark"><i class="mdi mdi-chart-line me-2"></i>Payment Summary</h6>
            <div class="row text-center g-2">
                <div class="col-4">
                    <p class="text-muted mb-1 small">Total Loan</p>
                    <h6 class="text-primary mb-0">UGX {{ number_format($loan->principal, 0) }}</h6>
                </div>
                <div class="col-4">
                    <p class="text-muted mb-1 small">Total Paid</p>
                    <h6 class="text-success mb-0">UGX {{ number_format($totalPaid, 0) }}</h6>
                </div>
                <div class="col-4">
                    <p class="text-muted mb-1 small">Balance</p>
                    <h6 class="text-danger mb-0">UGX {{ number_format($loan->principal - $totalPaid, 0) }}</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Payment - Compact -->
    @if($nextDue)
    <div class="alert {{ \Carbon\Carbon::parse($nextDue->payment_date)->isPast() ? 'alert-danger' : 'alert-info' }} py-2 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="d-block">Next Payment</small>
                <strong>{{ \Carbon\Carbon::parse($nextDue->payment_date)->format('d M Y') }}</strong>
            </div>
            <div class="text-end">
                <small class="d-block">Amount Due</small>
                <strong>UGX {{ number_format($nextDue->payment, 0) }}</strong>
            </div>
        </div>
    </div>
    @endif

    <!-- Additional Info -->
    <div class="row g-2 mb-3">
        <div class="col-12">
            <small class="text-muted">Product:</small>
            <p class="mb-0 text-dark">{{ $loan->product->name ?? 'N/A' }}</p>
        </div>
        <div class="col-12">
            <small class="text-muted">Branch:</small>
            <p class="mb-0 text-dark">{{ $loan->branch->name ?? 'N/A' }}</p>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-grid gap-2">
        <a href="{{ route('admin.loans.show', ['id' => $loan->id, 'type' => $loanType]) }}" class="btn btn-primary btn-sm">
            <i class="mdi mdi-eye me-1"></i> View Full Details
        </a>
    </div>
</div>

<style>
.loan-details-compact {
    padding: 0.5rem;
    background-color: #ffffff !important;
}
.loan-details-compact .avatar-lg {
    width: 60px;
    height: 60px;
}
.loan-details-compact .avatar-title {
    font-size: 1.5rem;
}
.modal-body {
    background-color: #ffffff !important;
}
.modal-content {
    background-color: #ffffff !important;
}
</style>
