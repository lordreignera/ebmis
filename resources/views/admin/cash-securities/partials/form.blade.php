@php
    $isEdit = $cashSecurity->exists;
    $canEditFinancials = !$isEdit || $cashSecurity->can_edit_financials;
    $selectedMember = old('member_id', $cashSecurity->member_id);
    $selectedLoan = old('loan_id', $cashSecurity->loan_id);
    $selectedPaymentType = old('payment_type', $cashSecurity->payment_type ?: \App\Models\CashSecurity::PAYMENT_MOBILE_MONEY);
@endphp

@if(!$canEditFinancials)
    <div class="alert alert-warning">
        This cash security is already paid or returned. You can update the loan link, phone/reference notes, and description, but not the member, amount, or payment method.
    </div>
@endif

<input type="hidden" name="loan_type" value="personal">
@unless($isEdit)
    <input type="hidden" name="manager_form" value="1">
@endunless

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Member <span class="text-danger">*</span></label>
        <select name="member_id" class="form-select" @disabled(!$canEditFinancials) required>
            <option value="">Select member</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected((string) $selectedMember === (string) $member->id)>
                    {{ $member->code }} - {{ $member->full_name }}{{ $member->contact ? ' (' . $member->contact . ')' : '' }}
                </option>
            @endforeach
        </select>
        @if(!$canEditFinancials)
            <input type="hidden" name="member_id" value="{{ $cashSecurity->member_id }}">
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label">Related Personal Loan</label>
        <select name="loan_id" class="form-select">
            <option value="">Not linked to a loan</option>
            @foreach($loans as $loan)
                <option value="{{ $loan->id }}" @selected((string) $selectedLoan === (string) $loan->id)>
                    {{ $loan->code }} - {{ $loan->member->full_name ?? 'Unknown member' }} - UGX {{ number_format((float) $loan->principal, 0) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">The selected loan must belong to the selected member.</small>
    </div>

    <div class="col-md-4">
        <label class="form-label">Amount (UGX) <span class="text-danger">*</span></label>
        <input type="number" name="amount" class="form-control" min="0.01" step="0.01"
               value="{{ old('amount', $cashSecurity->amount) }}" @disabled(!$canEditFinancials) required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
        <select name="payment_type" class="form-select" @disabled(!$canEditFinancials) required>
            <option value="{{ \App\Models\CashSecurity::PAYMENT_MOBILE_MONEY }}" @selected((int) $selectedPaymentType === \App\Models\CashSecurity::PAYMENT_MOBILE_MONEY)>Mobile Money</option>
            @if(auth()->user()->isSuperAdmin())
                <option value="{{ \App\Models\CashSecurity::PAYMENT_CASH }}" @selected((int) $selectedPaymentType === \App\Models\CashSecurity::PAYMENT_CASH)>Cash</option>
                <option value="{{ \App\Models\CashSecurity::PAYMENT_BANK_TRANSFER }}" @selected((int) $selectedPaymentType === \App\Models\CashSecurity::PAYMENT_BANK_TRANSFER)>Bank Transfer</option>
            @endif
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Payment Phone</label>
        <input type="text" name="member_phone" class="form-control"
               value="{{ old('member_phone', $cashSecurity->payment_phone ?: optional($cashSecurity->member)->contact) }}"
               placeholder="2567XXXXXXXX">
        <small class="text-muted">Used for mobile-money collection.</small>
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Security notes, receipt reference, or context">{{ old('description', $cashSecurity->description) }}</textarea>
    </div>
</div>
