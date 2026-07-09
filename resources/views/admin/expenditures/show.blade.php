@extends('layouts.admin')

@section('title', $expenditure->expense_number)

@section('content')
<div class="container-fluid expenditure-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">{{ $expenditure->expense_number }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                    <li class="breadcrumb-item active">{{ $expenditure->expense_number }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.expenditures.partials.alerts')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light text-dark">
                    <strong>{{ $expenditure->title }}</strong>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 detail-list">
                        <dt class="col-md-4">Status</dt>
                        <dd class="col-md-8"><span class="badge bg-light text-dark border">{{ $expenditure->status_label }}</span></dd>
                        <dt class="col-md-4">Type</dt>
                        <dd class="col-md-8">{{ $expenditure->type === 'performance_payout' ? 'Staff payment' : str_replace('_', ' ', ucfirst($expenditure->type)) }}</dd>
                        <dt class="col-md-4">Amount</dt>
                        <dd class="col-md-8 fw-bold">UGX {{ number_format((float) $expenditure->amount, 0) }}</dd>
                        <dt class="col-md-4">Expense Account</dt>
                        <dd class="col-md-8">{{ $expenditure->expenseAccount->full_name ?? 'N/A' }}</dd>
                        <dt class="col-md-4">Payment Account</dt>
                        <dd class="col-md-8">{{ $expenditure->paymentAccount->full_name ?? 'Not selected' }}</dd>
                        <dt class="col-md-4">Investment Account</dt>
                        <dd class="col-md-8">
                            {{ $expenditure->investment->name ?? 'Not selected' }}
                            @if($expenditure->investment)
                                <small class="d-block text-muted">Balance: UGX {{ number_format((float) $expenditure->investment->amount, 0) }}</small>
                            @endif
                        </dd>
                        <dt class="col-md-4">Payment Channel</dt>
                        <dd class="col-md-8">{{ $expenditure->payment_channel ? str_replace('_', ' ', ucfirst($expenditure->payment_channel)) : 'Not selected' }}</dd>
                        @if($expenditure->payment_channel === 'mobile_money' || $expenditure->mobile_money_reference)
                            <dt class="col-md-4">Mobile Money</dt>
                            <dd class="col-md-8">
                                {{ $expenditure->mobile_money_phone ?? 'N/A' }} {{ $expenditure->mobile_money_network ? '(' . $expenditure->mobile_money_network . ')' : '' }}
                                <small class="d-block text-muted">Ref: {{ $expenditure->mobile_money_reference ?? 'N/A' }}</small>
                                <small class="d-block text-muted">Status: {{ $expenditure->mobile_money_status ?? $expenditure->status }}</small>
                                @if($expenditure->mobile_money_message)
                                    <small class="d-block text-muted">{{ $expenditure->mobile_money_message }}</small>
                                @endif
                            </dd>
                        @endif
                        <dt class="col-md-4">Branch</dt>
                        <dd class="col-md-8">{{ $expenditure->branch->name ?? 'All branches' }}</dd>
                        <dt class="col-md-4">Responsible User</dt>
                        <dd class="col-md-8">{{ $expenditure->assignedUser->name ?? 'General expense' }}</dd>
                        <dt class="col-md-4">Expense Date</dt>
                        <dd class="col-md-8">{{ optional($expenditure->expense_date)->format('Y-m-d') }}</dd>
                        <dt class="col-md-4">Approved</dt>
                        <dd class="col-md-8">{{ $expenditure->approved_at ? $expenditure->approved_at->format('Y-m-d H:i') . ' by ' . ($expenditure->approvedBy->name ?? 'Staff') : 'Not approved' }}</dd>
                        <dt class="col-md-4">Paid</dt>
                        <dd class="col-md-8">{{ $expenditure->paid_at ? $expenditure->paid_at->format('Y-m-d H:i') . ' by ' . ($expenditure->paidBy->name ?? 'Staff') : 'Not paid' }}</dd>
                    </dl>
                    @if($expenditure->description || $expenditure->notes || $expenditure->rejection_reason)
                        <hr>
                        @if($expenditure->description)<p><strong>Description:</strong> {{ $expenditure->description }}</p>@endif
                        @if($expenditure->notes)
                            @if(!empty($staffPaymentNotes['basis']))
                                @if($staffPaymentNotes['note'])
                                    <p><strong>Notes:</strong> {{ $staffPaymentNotes['note'] }}</p>
                                @endif
                                @php($basis = $staffPaymentNotes['basis'])
                                <div class="staff-payment-breakdown">
                                    <strong class="d-block mb-2">Staff Payment Calculation</strong>
                                    <div class="row g-2">
                                        <div class="col-md-4"><small>Principal Collected</small><span>UGX {{ number_format((float) data_get($basis, 'principal_collected', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Interest Collected</small><span>UGX {{ number_format((float) data_get($basis, 'interest_collected', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Late Fees</small><span>UGX {{ number_format((float) data_get($basis, 'late_fees_collected', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Fees Collected</small><span>UGX {{ number_format((float) data_get($basis, 'fees_collected', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Qualified Revenue</small><span>UGX {{ number_format((float) data_get($basis, 'qualified_revenue', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Base Wage</small><span>UGX {{ number_format((float) data_get($basis, 'minimum_wage', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Stewardship Score</small><span>{{ number_format((float) data_get($basis, 'stewardship_score', 0), 1) }}%</span></div>
                                        <div class="col-md-4"><small>Stewardship Level</small><span>{{ data_get($basis, 'stewardship_level', 'N/A') }}</span></div>
                                        <div class="col-md-4"><small>Compensation Rate</small><span>{{ number_format((float) data_get($basis, 'compensation_rate', 0), 0) }}%</span></div>
                                        <div class="col-md-4"><small>Stewardship Comp.</small><span>UGX {{ number_format((float) data_get($basis, 'stewardship_compensation', 0), 0) }}</span></div>
                                        <div class="col-md-4"><small>Weekly Periods</small><span>{{ number_format((int) data_get($basis, 'weekly_periods_count', 1)) }}</span></div>
                                        @if(data_get($basis, 'calculation_notes'))
                                            <div class="col-md-12"><small>Calculation Notes</small><span>{{ data_get($basis, 'calculation_notes') }}</span></div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p><strong>Notes:</strong> {{ $expenditure->notes }}</p>
                            @endif
                        @endif
                        @if($expenditure->rejection_reason)<p class="text-danger"><strong>Rejection:</strong> {{ $expenditure->rejection_reason }}</p>@endif
                    @endif
                </div>
            </div>

            @if($expenditure->journalEntry)
                <div class="card mt-4">
                    <div class="card-header bg-light text-dark"><strong>Accounting Entry</strong></div>
                    <div class="card-body">
                        <p class="mb-2">{{ $expenditure->journalEntry->journal_number }} - {{ $expenditure->journalEntry->narrative }}</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($expenditure->journalEntry->lines as $line)
                                        <tr>
                                            <td>{{ $line->account_id }} - {{ $line->narrative }}</td>
                                            <td class="text-end">{{ number_format((float) $line->debit_amount, 0) }}</td>
                                            <td class="text-end">{{ number_format((float) $line->credit_amount, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light text-dark"><strong>Actions</strong></div>
                <div class="card-body">
                    @if($expenditure->status === \App\Models\Expenditure::STATUS_PENDING)
                        <div class="alert alert-warning small">
                            This request is pending approval. Payment cannot be sent until it is approved.
                        </div>
                    @elseif($expenditure->status === \App\Models\Expenditure::STATUS_APPROVED)
                        <div class="alert alert-info small">
                            This request is approved and waiting for mobile-money payment.
                        </div>
                    @elseif($expenditure->status === \App\Models\Expenditure::STATUS_PAID)
                        <div class="alert alert-success small">
                            This request is approved and paid.
                        </div>
                    @endif

                    @if($expenditure->canBeApproved())
                        <form method="POST" action="{{ route('admin.expenditures.approve', $expenditure) }}" class="mb-3">
                            @csrf
                            <button class="btn btn-outline-dark w-100"><i class="mdi mdi-check me-1"></i> Approve</button>
                        </form>
                    @endif

                    @if($expenditure->canBePaid())
                        <form method="POST" action="{{ route('admin.expenditures.pay', $expenditure) }}" class="mb-3">
                            @csrf
                            <input type="hidden" name="payment_channel" id="payment_channel" value="mobile_money">
                            <label class="form-label">Payment Channel</label>
                            <input type="text" class="form-control bg-light mb-2" value="Mobile Money" readonly>
                            <label class="form-label">Investment Account</label>
                            <select name="investment_id" class="form-select mb-2" required>
                                <option value="">Select investment funding account</option>
                                @foreach($investments as $investment)
                                    <option value="{{ $investment->id }}" @selected((string) old('investment_id', $expenditure->investment_id) === (string) $investment->id)>
                                        {{ $investment->name }} - UGX {{ number_format((float) $investment->amount, 0) }}
                                    </option>
                                @endforeach
                            </select>
                            <label class="form-label">GL Payment Account</label>
                            <select name="payment_account_id" class="form-select mb-2" required>
                                <option value="">Select settlement account</option>
                                @foreach($paymentAccounts as $account)
                                    <option value="{{ $account->Id }}" @selected((string) old('payment_account_id', $expenditure->payment_account_id) === (string) $account->Id)>{{ $account->full_name }}</option>
                                @endforeach
                            </select>
                            <label class="form-label">Payment Method</label>
                            <input type="text" name="payment_method" class="form-control mb-3" value="{{ old('payment_method', $expenditure->payment_method ?: 'Mobile Money') }}">
                            <div id="mobile_money_fields" class="mobile-money-fields">
                                <label class="form-label">Mobile Money Phone</label>
                                <input type="text" name="mobile_money_phone" class="form-control mb-2" value="{{ old('mobile_money_phone', $expenditure->mobile_money_phone) }}" placeholder="2567XXXXXXXX">
                                <label class="form-label">Network</label>
                                <select name="mobile_money_network" class="form-select mb-3">
                                    <option value="">Auto detect</option>
                                    <option value="MTN" @selected(old('mobile_money_network', $expenditure->mobile_money_network) === 'MTN')>MTN</option>
                                    <option value="AIRTEL" @selected(old('mobile_money_network', $expenditure->mobile_money_network) === 'AIRTEL')>Airtel</option>
                                </select>
                            </div>
                            <button class="btn btn-dark w-100">
                                <i class="mdi mdi-cellphone-arrow-down me-1"></i>
                                {{ $expenditure->status === \App\Models\Expenditure::STATUS_PAYMENT_FAILED ? 'Retry Mobile Money' : 'Send Mobile Money' }}
                            </button>
                        </form>
                    @endif

                    @if($expenditure->status === \App\Models\Expenditure::STATUS_PAYMENT_PENDING && $expenditure->mobile_money_reference)
                        <form method="POST" action="{{ route('admin.expenditures.mobile-money.status', $expenditure) }}" class="mb-3">
                            @csrf
                            <button class="btn btn-outline-dark w-100"><i class="mdi mdi-refresh me-1"></i> Check Mobile Money Status</button>
                        </form>
                    @endif

                    @if($expenditure->canBeRejected())
                        <form method="POST" action="{{ route('admin.expenditures.reject', $expenditure) }}">
                            @csrf
                            <label class="form-label">Rejection Reason</label>
                            <textarea name="rejection_reason" class="form-control mb-2" rows="3" required></textarea>
                            <button class="btn btn-outline-danger w-100"><i class="mdi mdi-close me-1"></i> Reject</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.expenditure-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }
.detail-list dt { color: #6b7280; font-weight: 600; }
.detail-list dd { color: #111827; }
.mobile-money-fields { display: none; }
.staff-payment-breakdown {
    border: 1px solid #dbeafe;
    border-radius: 8px;
    background: #f8fbff;
    padding: 14px;
    margin-top: 12px;
}
.staff-payment-breakdown small {
    display: block;
    color: #6b7280;
    font-size: 11px;
    text-transform: uppercase;
}
.staff-payment-breakdown span {
    display: block;
    color: #111827;
    font-weight: 600;
    overflow-wrap: anywhere;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const channel = document.getElementById('payment_channel');
    const mobileFields = document.getElementById('mobile_money_fields');

    function toggleMobileFields() {
        if (!channel || !mobileFields) return;
        mobileFields.style.display = channel.value === 'mobile_money' ? 'block' : 'none';
    }

    toggleMobileFields();
    if (channel) {
        channel.addEventListener('change', toggleMobileFields);
    }
});
</script>
@endpush
