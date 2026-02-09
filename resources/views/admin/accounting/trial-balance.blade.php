@extends('layouts.admin')

@section('title', 'Trial Balance')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Trial Balance</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Trial Balance</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.accounting.trial-balance') }}" class="row align-items-end">
                    <div class="col-md-4">
                        <label><i class="mdi mdi-calendar me-1"></i>As of Date</label>
                        <input type="date" class="form-control" name="as_of_date" value="{{ $asOfDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-refresh me-1"></i>Refresh</button>
                        <button type="button" class="btn btn-success" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print</button>
                        <a href="{{ route('admin.accounting.trial-balance.download', ['as_of_date' => $asOfDate]) }}" class="btn btn-info"><i class="mdi mdi-download me-1"></i>Download PDF</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Trial Balance Report -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3 class="mb-0">EBMIS Trial Balance</h3>
                    <p class="text-muted">As of {{ \Carbon\Carbon::parse($asOfDate)->format('F d, Y') }}</p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Account Code</th>
                                <th width="45%">Account Name</th>
                                <th width="20%" class="text-end">Debit Balance</th>
                                <th width="20%" class="text-end">Credit Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accountsByCategory as $category => $accounts)
                            <tr class="table-secondary">
                                <td colspan="4"><strong><i class="mdi mdi-folder me-1"></i>{{ strtoupper($category) }}</strong></td>
                            </tr>
                            @foreach($accounts as $account)
                            @if($account->debit_balance > 0 || $account->credit_balance > 0)
                            <tr>
                                <td>
                                    {{ $account->code }}
                                    @if($account->sub_code)
                                    <br><small class="text-muted">{{ $account->sub_code }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($account->parent_id)
                                    <span class="ms-3">└─</span>
                                    @endif
                                    {{ $account->name }}
                                </td>
                                <td class="text-end">
                                    @if($account->debit_balance > 0)
                                    <strong>{{ number_format($account->debit_balance, 2) }}</strong>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($account->credit_balance > 0)
                                    <strong>{{ number_format($account->credit_balance, 2) }}</strong>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @endforeach
                            <!-- Category Subtotal -->
                            <tr class="table-active">
                                <td colspan="2" class="text-end"><strong>{{ $category }} Subtotal:</strong></td>
                                <td class="text-end"><strong>{{ number_format($accounts->sum('debit_balance'), 2) }}</strong></td>
                                <td class="text-end"><strong>{{ number_format($accounts->sum('credit_balance'), 2) }}</strong></td>
                            </tr>
                            <tr><td colspan="4" class="p-1"></td></tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="2" class="text-end">GRAND TOTAL:</th>
                                <th class="text-end">UGX {{ number_format($totalDebits, 2) }}</th>
                                <th class="text-end">UGX {{ number_format($totalCredits, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-end">DIFFERENCE:</th>
                                <th colspan="2" class="text-center">
                                    @php
                                        $diff = abs($totalDebits - $totalCredits);
                                    @endphp
                                    @if($diff < 0.01)
                                    <span class="badge badge-success"><i class="mdi mdi-check-circle me-1"></i>BALANCED</span>
                                    @else
                                    <span class="badge badge-danger"><i class="mdi mdi-alert me-1"></i>OUT OF BALANCE: UGX {{ number_format($diff, 2) }}</span>
                                    @endif
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i><strong>Trial Balance:</strong> This report lists all general ledger accounts and their balances. Total debits must equal total credits, verifying that the accounting equation is balanced.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
