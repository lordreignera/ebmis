@extends('layouts.admin')

@section('title', 'Cash Security CS-' . str_pad((string) $cashSecurity->id, 6, '0', STR_PAD_LEFT))

@section('content')
<div class="container-fluid cash-security-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">CS-{{ str_pad((string) $cashSecurity->id, 6, '0', STR_PAD_LEFT) }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.cash-securities.index') }}">Cash Securities</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.cash-securities.edit', $cashSecurity) }}" class="btn btn-outline-primary">
                <i class="mdi mdi-pencil-outline me-1"></i> Edit
            </a>
            @if((int) $cashSecurity->status === \App\Models\CashSecurity::STATUS_PAID)
                <a href="{{ route('admin.cash-securities.receipt', $cashSecurity) }}" class="btn btn-outline-success" target="_blank">
                    <i class="mdi mdi-receipt-outline me-1"></i> Receipt
                </a>
            @endif
            <a href="{{ route('admin.cash-securities.index') }}" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @include('admin.cash-securities.partials.alerts')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light text-dark"><strong>Security Details</strong></div>
                <div class="card-body">
                    <dl class="row mb-0 detail-list">
                        <dt class="col-md-4">Status</dt>
                        <dd class="col-md-8">{!! $cashSecurity->status_badge !!}</dd>
                        <dt class="col-md-4">Amount</dt>
                        <dd class="col-md-8 fw-bold">UGX {{ number_format((float) $cashSecurity->amount, 0) }}</dd>
                        <dt class="col-md-4">Payment Method</dt>
                        <dd class="col-md-8">{{ $cashSecurity->payment_type_name }}</dd>
                        <dt class="col-md-4">Payment Phone</dt>
                        <dd class="col-md-8">{{ $cashSecurity->payment_phone ?: ($cashSecurity->member->contact ?? 'N/A') }}</dd>
                        <dt class="col-md-4">Reference</dt>
                        <dd class="col-md-8">{{ $cashSecurity->transaction_reference ?: ($cashSecurity->pay_ref ?: 'N/A') }}</dd>
                        <dt class="col-md-4">Payment Status</dt>
                        <dd class="col-md-8">{{ $cashSecurity->payment_status ?: 'N/A' }}</dd>
                        <dt class="col-md-4">Date</dt>
                        <dd class="col-md-8">{{ optional($cashSecurity->datecreated ?? $cashSecurity->created_at)->format('Y-m-d H:i') }}</dd>
                        <dt class="col-md-4">Added By</dt>
                        <dd class="col-md-8">{{ $cashSecurity->addedBy->name ?? 'N/A' }}</dd>
                        <dt class="col-md-4">Related Loan</dt>
                        <dd class="col-md-8">{{ $cashSecurity->loan->code ?? 'Not linked' }}</dd>
                        <dt class="col-md-4">Returned</dt>
                        <dd class="col-md-8">
                            @if((int) $cashSecurity->returned === 1)
                                {{ optional($cashSecurity->returned_at)->format('Y-m-d H:i') }} by {{ $cashSecurity->returnedBy->name ?? 'Staff' }}
                                <small class="d-block text-muted">{{ $cashSecurity->return_transaction_reference }}</small>
                            @else
                                Not returned
                            @endif
                        </dd>
                        <dt class="col-md-4">Description</dt>
                        <dd class="col-md-8">{{ $cashSecurity->description ?: 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light text-dark"><strong>Member</strong></div>
                <div class="card-body">
                    <h5>{{ $cashSecurity->member->full_name ?? 'Unknown member' }}</h5>
                    <p class="mb-1"><strong>Code:</strong> {{ $cashSecurity->member->code ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Phone:</strong> {{ $cashSecurity->member->contact ?? 'N/A' }}</p>
                    <p class="mb-3"><strong>Security Account:</strong> {{ $cashSecurity->member->cash_security_account_number ?? 'N/A' }}</p>
                    @if($cashSecurity->member)
                        <a href="{{ route('admin.members.show', $cashSecurity->member) }}" class="btn btn-outline-dark w-100">
                            <i class="mdi mdi-account-eye-outline me-1"></i> Open Member Profile
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cash-security-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }
.detail-list dt { color: #6b7280; font-weight: 600; }
.detail-list dd { color: #111827; overflow-wrap: anywhere; }
</style>
@endpush
