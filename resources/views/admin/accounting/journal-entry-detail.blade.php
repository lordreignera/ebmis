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
                            <tr>
                                <th width="40%">Branch / Cost Centre:</th>
                                <td>
                                    @if($entry->costCenter)
                                        <span class="badge" style="background:#1a73e8;color:#fff;font-size:0.85em;padding:4px 8px;">{{ $entry->costCenter->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Product:</th>
                                <td>
                                    @if($entry->product)
                                        <span class="badge" style="background:#0f9d58;color:#fff;font-size:0.85em;padding:4px 8px;">{{ $entry->product->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Loan Officer:</th>
                                <td>
                                    @if($entry->officer)
                                        <span class="badge" style="background:#6f42c1;color:#fff;font-size:0.85em;padding:4px 8px;">{{ $entry->officer->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Fund:</th>
                                <td>
                                    @if($entry->fund)
                                        <span class="badge badge-info">{{ $entry->fund->name }}</span>
                                    @elseif($entry->inv_id)
                                        @php
                                            $inv = DB::table('investment')->where('id', $entry->inv_id)->first();
                                        @endphp
                                        <span class="badge badge-info">{{ $inv->name ?? 'Investor #'.$entry->inv_id }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
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

                @if($entry->reference_type === 'Repayment' && $entry->lines && $entry->lines->count() > 0)
                @php
                    $sortedLines = $entry->lines->sortBy('line_number');
                    $cashReceived = (float) $sortedLines
                        ->filter(fn($line) => ($line->account->code ?? null) === '10000')
                        ->sum('debit_amount');

                    $principalPaid = (float) $sortedLines
                        ->filter(fn($line) => ($line->account->code ?? null) === '11000')
                        ->sum('credit_amount');

                    $interestPaid = (float) $sortedLines
                        ->filter(function($line) {
                            $code = $line->account->code ?? null;
                            return $code === '11200' || $code === '41000';
                        })
                        ->sum('credit_amount');

                    $lateFeesPaid = (float) $sortedLines
                        ->filter(function($line) {
                            return ($line->account->code ?? null) === '42000'
                                && ($line->account->sub_code ?? null) === '42020';
                        })
                        ->sum('credit_amount');

                    $reclassPrincipalEffect = (float) (($relatedReclassEntries ?? collect())
                        ->flatMap(function($reclass) {
                            return $reclass->lines ?? collect();
                        })
                        ->filter(function($line) {
                            return ($line->account->code ?? null) === '11000';
                        })
                        ->sum(function($line) {
                            return (float) $line->credit_amount - (float) $line->debit_amount;
                        }));

                    $reclassLateFees = (float) (($relatedReclassEntries ?? collect())
                        ->flatMap(function($reclass) {
                            return $reclass->lines ?? collect();
                        })
                        ->filter(function($line) {
                            return ($line->account->code ?? null) === '42000'
                                && ($line->account->sub_code ?? null) === '42020';
                        })
                        ->sum(function($line) {
                            return (float) $line->credit_amount - (float) $line->debit_amount;
                        }));

                    $lateFeesPaidTotal = $lateFeesPaid + $reclassLateFees;
                    $principalPaidTotal = $principalPaid + $reclassPrincipalEffect;
                @endphp
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-success mb-0">
                            <h6 class="mb-2"><i class="mdi mdi-cash-multiple me-1"></i>Repayment Breakdown (Simple View)</h6>
                            <div class="row">
                                <div class="col-md-3"><strong>Customer Paid:</strong><br>UGX {{ number_format($cashReceived, 2) }}</div>
                                <div class="col-md-3"><strong>Principal Paid (Net):</strong><br>UGX {{ number_format($principalPaidTotal, 2) }}</div>
                                <div class="col-md-3"><strong>Interest Paid:</strong><br>UGX {{ number_format($interestPaid, 2) }}</div>
                                <div class="col-md-3"><strong>Late Fees Paid:</strong><br>UGX {{ number_format($lateFeesPaidTotal, 2) }}</div>
                            </div>
                            @if(($relatedReclassEntries ?? collect())->count() > 0)
                            <div class="mt-2 small text-muted">
                                Includes linked late-fee reclass journal(s):
                                @foreach($relatedReclassEntries as $idx => $reclass)
                                    <a href="{{ route('admin.accounting.journal-entry', $reclass->Id) }}">{{ $reclass->journal_number }}</a>@if($idx < $relatedReclassEntries->count() - 1), @endif
                                @endforeach
                            </div>
                            @endif
                            <div class="mt-2 small text-muted">
                                Net principal reflects linked adjustments posted to Loan Receivable (11000 series).
                            </div>
                            <div class="mt-2 small text-muted">
                                Note: Journal totals include FAN transit lines, so they can look higher than the customer paid amount.
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if(($relatedReclassEntries ?? collect())->count() > 0)
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-warning mb-0">
                            <h6 class="mb-2"><i class="mdi mdi-link-variant me-1"></i>Linked Late Fee Reclass Entries</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Journal #</th>
                                            <th>Date</th>
                                            <th>Narrative</th>
                                            <th class="text-end">Loan Receivable Effect (11000)</th>
                                            <th class="text-end">Late Fee Effect (42020)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($relatedReclassEntries as $reclass)
                                        @php
                                            $principalEffect = (float) ($reclass->lines ?? collect())
                                                ->filter(function($line) {
                                                    return ($line->account->code ?? null) === '11000';
                                                })
                                                ->sum(function($line) {
                                                    return (float) $line->credit_amount - (float) $line->debit_amount;
                                                });

                                            $lateFeeEffect = (float) ($reclass->lines ?? collect())
                                                ->filter(function($line) {
                                                    return ($line->account->code ?? null) === '42000'
                                                        && ($line->account->sub_code ?? null) === '42020';
                                                })
                                                ->sum(function($line) {
                                                    return (float) $line->credit_amount - (float) $line->debit_amount;
                                                });
                                        @endphp
                                        <tr>
                                            <td><a href="{{ route('admin.accounting.journal-entry', $reclass->Id) }}">{{ $reclass->journal_number }}</a></td>
                                            <td>{{ \Carbon\Carbon::parse($reclass->transaction_date)->format('Y-m-d') }}</td>
                                            <td>{{ $reclass->narrative }}</td>
                                            <td class="text-end">UGX {{ number_format($principalEffect, 2) }}</td>
                                            <td class="text-end">UGX {{ number_format($lateFeeEffect, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
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
