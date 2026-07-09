@extends('layouts.admin')

@section('title', 'New Expenditure')

@section('content')
<div class="container-fluid expenditure-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">New Money Request</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.expenditures.index') }}">Expenditures</a></li>
                    <li class="breadcrumb-item active">New request</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>

    @include('admin.expenditures.partials.alerts')

    <form method="POST" action="{{ route('admin.expenditures.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="operational" @selected(old('type') === 'operational')>Operational</option>
                        <option value="performance_payout" @selected(old('type') === 'performance_payout')>Staff payment</option>
                        <option value="allowance" @selected(old('type') === 'allowance')>Allowance</option>
                        <option value="other" @selected(old('type') === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expense Account <span class="text-danger">*</span></label>
                    <select name="expense_account_id" class="form-select" required>
                        <option value="">Select expense account</option>
                        @foreach($expenseAccounts as $account)
                            <option value="{{ $account->Id }}" @selected((string) old('expense_account_id') === (string) $account->Id)>{{ $account->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Account</label>
                    <select name="payment_account_id" class="form-select">
                        <option value="">Choose after approval when paying</option>
                        @foreach($paymentAccounts as $account)
                            <option value="{{ $account->Id }}" @selected((string) old('payment_account_id') === (string) $account->Id)>{{ $account->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Investment Account</label>
                    <select name="investment_id" class="form-select">
                        <option value="">Choose after approval when paying</option>
                        @foreach($investments as $investment)
                            <option value="{{ $investment->id }}" @selected((string) old('investment_id') === (string) $investment->id)>
                                {{ $investment->name }} - UGX {{ number_format((float) $investment->amount, 0) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                    <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Responsible User</label>
                    <select name="assigned_user_id" class="form-select">
                        <option value="">General expense</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('assigned_user_id') === (string) $user->id)>{{ $user->name }}{{ $user->designation ? ' - ' . $user->designation : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Method</label>
                    <input type="text" name="payment_method" class="form-control" value="{{ old('payment_method') }}" placeholder="Cash, bank, mobile money">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.expenditures.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-dark"><i class="mdi mdi-content-save-outline me-1"></i> Submit Request for Approval</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.expenditure-page .card { border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 6px rgba(17, 24, 39, 0.05); }
</style>
@endpush
