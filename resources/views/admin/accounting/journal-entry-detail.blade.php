@extends('layouts.admin')

@section('title', 'Journal Entry Detail')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Journal Entry: {{ $entry->journal_number }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.accounting.journal-entries') }}">Journal Entries</a></li>
                        <li class="breadcrumb-item active">{{ $entry->journal_number }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.accounting.journal-entries') }}" class="btn btn-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Journal Entry Header -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title mb-3">Journal Entry Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Journal Number:</th>
                                <td><strong>{{ $entry->journal_number }}</strong></td>
                            </tr>
                            <tr>
                                <th>Transaction Date:</th>
                                <td>{{ \Carbon\Carbon::parse($entry->transaction_date)->format('l, F d, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Reference Type:</th>
                                <td><span class="badge badge-info">{{ $entry->reference_type }}</span></td>
                            </tr>
                            <tr>
                                <th>Reference ID:</th>
                                <td>#{{ $entry->reference_id }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($entry->status == 'posted')
                                    <span class="badge badge-success"><i class="mdi mdi-check-circle me-1"></i>Posted</span>
                                    @elseif($entry->status == 'reversed')
                                    <span class="badge badge-danger"><i class="mdi mdi-close-circle me-1"></i>Reversed</span>
                                    @else
                                    <span class="badge badge-warning">{{ ucfirst($entry->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="card-title mb-3">Additional Details</h5>
                        <table class="table table-sm">
                            @if($entry->costCenter)
                            <tr>
                                <th width="40%">Cost Center (Branch):</th>
                                <td>{{ $entry->costCenter->name }}</td>
                            </tr>
                            @endif
                            @if($entry->product)
                            <tr>
                                <th>Product:</th>
                                <td>{{ $entry->product->name }}</td>
                            </tr>
                            @endif
                            @if($entry->officer)
                            <tr>
                                <th>Officer:</th>
                                <td>{{ $entry->officer->name }}</td>
                            </tr>
                            @endif
                            @if($entry->fund)
                            <tr>
                                <th>Fund:</th>
                                <td>{{ $entry->fund->name }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $entry->postedBy->name ?? 'System' }}</td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $entry->created_at->format('M d, Y H:i:s') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 class="card-title mb-2">Narrative</h5>
                        <div class="alert alert-light">
                            {{ $entry->narrative }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Journal Lines (Detailed Entries) -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="mdi mdi-format-list-bulleted me-2"></i>Journal Lines</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Account Code</th>
                                <th width="35%">Account Name</th>
                                <th width="20%">Narrative</th>
                                <th width="12%" class="text-end">Debit</th>
                                <th width="12%" class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entry->lines->sortBy('line_number') as $line)
                            <tr>
                                <td class="text-center">{{ $line->line_number }}</td>
                                <td>
                                    <strong>{{ $line->account->code }}</strong>
                                    @if($line->account->sub_code)
                                    <br><small class="text-muted">{{ $line->account->sub_code }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $line->account->name }}
                                    <br><small class="text-muted"><i class="mdi mdi-tag"></i> {{ $line->account->category }}</small>
                                </td>
                                <td>
                                    <small>{{ $line->narrative }}</small>
                                </td>
                                <td class="text-end">
                                    @if($line->debit_amount > 0)
                                    <strong class="text-success">{{ number_format($line->debit_amount, 2) }}</strong>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($line->credit_amount > 0)
                                    <strong class="text-danger">{{ number_format($line->credit_amount, 2) }}</strong>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-active">
                            <tr>
                                <th colspan="4" class="text-end">TOTALS:</th>
                                <th class="text-end text-success">
                                    UGX {{ number_format($entry->total_debit, 2) }}
                                </th>
                                <th class="text-end text-danger">
                                    UGX {{ number_format($entry->total_credit, 2) }}
                                </th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">DIFFERENCE:</th>
                                <th colspan="2" class="text-center">
                                    @php
                                        $diff = abs($entry->total_debit - $entry->total_credit);
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
                
                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i><strong>Double-Entry Accounting:</strong> Every journal entry must have equal debits and credits. This ensures the accounting equation (Assets = Liabilities + Equity) remains balanced.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
