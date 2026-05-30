@extends('layouts.admin')

@section('title', 'UMRA Loan Records')

@push('styles')
<style>
    .umra-loan-records {
        color: #1f2937;
    }

    .umra-page-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1.25rem;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: 5px solid #b91c1c;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .umra-page-head h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .umra-page-head p {
        margin: 0.3rem 0 0;
        color: #6b7280;
    }

    .umra-head-actions,
    .umra-filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .umra-stat {
        height: 100%;
        padding: 0.9rem 1rem;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .umra-stat span {
        display: block;
        color: #6b7280;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .umra-stat strong {
        display: block;
        margin-top: 0.35rem;
        color: #111827;
        font-size: 1.25rem;
        line-height: 1.2;
        word-break: break-word;
    }

    .umra-filter-card,
    .umra-table-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .umra-filter-card {
        padding: 1rem;
        margin: 1rem 0;
    }

    .umra-filter-card label {
        color: #374151;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .umra-table-wrap {
        max-height: none;
        overflow: visible;
        border-radius: 8px;
    }

    .umra-record-list {
        display: grid;
        gap: 0.75rem;
        padding: 0.9rem;
        overflow-x: visible;
    }

    .umra-record-head,
    .umra-record-row {
        display: grid;
        grid-template-columns: 1.15fr 0.9fr 0.95fr 0.95fr 0.9fr 0.8fr 0.95fr 1.05fr 0.75fr;
        gap: 0.75rem;
        align-items: center;
    }

    .umra-record-head {
        padding: 0.65rem 0.85rem;
        color: #6b7280;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
    }

    .umra-record-row {
        padding: 0.9rem;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .umra-record-row.is-loss,
    .umra-record-row.is-writeoff {
        border-color: #fecaca;
        background: #fff7f7;
    }

    .umra-record-cell {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .umra-cell-label {
        display: none;
        margin-bottom: 0.2rem;
        color: #6b7280;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .umra-primary-text {
        color: #111827;
        font-weight: 800;
        line-height: 1.25;
    }

    .umra-muted-text {
        color: #6b7280;
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .umra-money {
        color: #111827;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .umra-record-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .umra-detail-grid {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.5rem;
        padding-top: 0.75rem;
        margin-top: 0.1rem;
        border-top: 1px solid #eef2f7;
    }

    .umra-detail-item {
        min-width: 0;
        padding: 0.55rem 0.65rem;
        background: #f9fafb;
        border: 1px solid #eef2f7;
        border-radius: 8px;
    }

    .umra-detail-item.is-wide {
        grid-column: span 2;
    }

    .umra-detail-item span {
        display: block;
        color: #6b7280;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .umra-detail-item strong,
    .umra-detail-item p {
        display: block;
        margin: 0.2rem 0 0;
        color: #111827;
        font-size: 0.82rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .umra-table-footer {
        padding: 0.8rem 1rem;
        border-top: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 0.85rem;
    }

    @media (max-width: 767px) {
        .umra-page-head {
            align-items: stretch;
            flex-direction: column;
        }

        .umra-head-actions,
        .umra-filter-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 1399px) {
        .umra-record-head {
            display: none;
        }

        .umra-record-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .umra-cell-label {
            display: block;
        }

        .umra-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .umra-detail-item.is-wide {
            grid-column: span 2;
        }
    }

    @media (max-width: 575px) {
        .umra-record-row,
        .umra-detail-grid {
            grid-template-columns: 1fr;
        }

        .umra-detail-item.is-wide {
            grid-column: span 1;
        }

        .umra-record-list {
            padding: 0.65rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $activeQuery = array_filter($filters, fn($value) => $value !== '');
@endphp

<div class="umra-loan-records">
    <div class="umra-page-head">
        <div>
            <h3>UMRA Loan Records</h3>
            <p>Monthly loan book return generated {{ $generatedDate->format('d M Y H:i') }}</p>
        </div>
        <div class="umra-head-actions">
            <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="mdi mdi-arrow-left"></i> Dashboard
            </a>
            <a href="{{ route('admin.umra.export-preview', $activeQuery) }}" class="btn btn-sm btn-outline-success">
                <i class="mdi mdi-download"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl col-md-6 mb-3">
            <div class="umra-stat">
                <span>Records</span>
                <strong>{{ number_format($loanRecords->count()) }} / {{ number_format($totalLoanRecords) }}</strong>
            </div>
        </div>
        <div class="col-xl col-md-6 mb-3">
            <div class="umra-stat">
                <span>Outstanding Principal</span>
                <strong>UGX {{ number_format($loanRecords->sum('outstanding_principal'), 0) }}</strong>
            </div>
        </div>
        <div class="col-xl col-md-6 mb-3">
            <div class="umra-stat">
                <span>Required Provision</span>
                <strong>UGX {{ number_format($loanRecords->sum('required_provision'), 0) }}</strong>
            </div>
        </div>
        <div class="col-xl col-md-6 mb-3">
            <div class="umra-stat">
                <span>Followed Up</span>
                <strong>{{ number_format($loanRecords->where('has_follow_up', true)->count()) }}</strong>
            </div>
        </div>
        <div class="col-xl col-md-6 mb-3">
            <div class="umra-stat">
                <span>Write-off Review</span>
                <strong>{{ number_format($loanRecords->where('writeoff_flag', 'Write-off review')->count()) }}</strong>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.umra.loan-records') }}" class="umra-filter-card">
        <div class="row g-3">
            <div class="col-xl-3 col-lg-4 col-md-6">
                <label for="q" class="form-label">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Client, account, officer">
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="branch" class="form-label">Branch</label>
                <select id="branch" name="branch" class="form-select form-select-sm">
                    <option value="">All branches</option>
                    @foreach($filterOptions['branches'] as $branch)
                        <option value="{{ $branch }}" @selected($filters['branch'] === $branch)>{{ $branch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="officer" class="form-label">Officer</label>
                <select id="officer" name="officer" class="form-select form-select-sm">
                    <option value="">All officers</option>
                    @foreach($filterOptions['officers'] as $officer)
                        <option value="{{ $officer }}" @selected($filters['officer'] === $officer)>{{ $officer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="product" class="form-label">Product</label>
                <select id="product" name="product" class="form-select form-select-sm">
                    <option value="">All products</option>
                    @foreach($filterOptions['products'] as $product)
                        <option value="{{ $product }}" @selected($filters['product'] === $product)>{{ $product }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="classification" class="form-label">Risk Class</label>
                <select id="classification" name="classification" class="form-select form-select-sm">
                    <option value="">All classes</option>
                    @foreach($filterOptions['classifications'] as $classification)
                        <option value="{{ $classification }}" @selected($filters['classification'] === $classification)>{{ $classification }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-1 col-lg-4 col-md-6">
                <label for="loan_status" class="form-label">Status</label>
                <select id="loan_status" name="loan_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($filterOptions['statuses'] as $status)
                        <option value="{{ $status }}" @selected($filters['loan_status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="follow_up" class="form-label">Follow-up</label>
                <select id="follow_up" name="follow_up" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="yes" @selected($filters['follow_up'] === 'yes')>Has follow-up</option>
                    <option value="no" @selected($filters['follow_up'] === 'no')>No follow-up</option>
                </select>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="date_from" class="form-label">Disbursed From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <label for="date_to" class="form-label">Disbursed To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-xl-8 col-lg-8 col-md-12 d-flex align-items-end justify-content-end">
                <div class="umra-filter-actions">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-filter"></i> Filter
                    </button>
                    <a href="{{ route('admin.umra.loan-records') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-refresh"></i> Reset
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="umra-table-card">
        <div class="umra-table-wrap">
            <div class="umra-record-list">
                @if($loanRecords->count() > 0)
                    <div class="umra-record-head">
                        <div>Client</div>
                        <div>Branch / Officer</div>
                        <div>Loan Account</div>
                        <div>Product</div>
                        <div>Outstanding</div>
                        <div>Risk</div>
                        <div>Provision</div>
                        <div>Follow-up</div>
                        <div>Action</div>
                    </div>
                @endif

                @forelse($loanRecords as $loan)
                    <div class="umra-record-row {{ $loan['classification'] === 'Loss' ? 'is-loss' : '' }} {{ $loan['writeoff_flag'] === 'Write-off review' ? 'is-writeoff' : '' }}">
                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Client</span>
                            <div class="umra-primary-text">{{ $loan['client_name'] }}</div>
                            <div class="umra-muted-text">ID: {{ $loan['client_id'] }}</div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Branch / Officer</span>
                            <div class="umra-primary-text">{{ $loan['branch'] }}</div>
                            <div class="umra-muted-text">{{ $loan['field_officer'] }}</div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Loan Account</span>
                            <div class="umra-primary-text">{{ $loan['loan_account_no'] }}</div>
                            <div class="umra-muted-text">FAN: {{ $loan['financing_account_number'] }}</div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Product</span>
                            <div class="umra-primary-text">{{ $loan['loan_product'] }}</div>
                            <div class="umra-muted-text">Disbursed {{ $loan['disbursement_date'] }}</div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Outstanding</span>
                            <div class="umra-money">UGX {{ number_format($loan['outstanding_principal'], 0) }}</div>
                            <div class="umra-muted-text">
                                Interest {{ number_format($loan['outstanding_interest'], 0) }}
                            </div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Risk</span>
                            <span class="badge bg-{{ $loan['badge_color'] }}">{{ $loan['classification'] }}</span>
                            <div class="umra-muted-text">{{ number_format($loan['dpd']) }} DPD, {{ number_format($loan['missed_installments']) }} missed</div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Provision</span>
                            <div class="umra-money">UGX {{ number_format($loan['required_provision'], 0) }}</div>
                            <div class="umra-muted-text">Rate {{ $loan['provision_rate'] }}</div>
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Follow-up</span>
                            @if($loan['has_follow_up'])
                                <span class="badge bg-success">{{ $loan['follow_up_count'] }} recorded</span>
                                <div class="umra-muted-text">{{ $loan['latest_follow_up_outcome'] }}</div>
                            @else
                                <span class="badge bg-danger">None</span>
                                <div class="umra-muted-text">No follow-up yet</div>
                            @endif
                        </div>

                        <div class="umra-record-cell">
                            <span class="umra-cell-label">Action</span>
                            <div class="umra-record-actions">
                                <a href="{{ route('admin.loans.repayments.schedules', $loan['loan_id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-eye"></i> View
                                </a>
                            </div>
                        </div>

                        <div class="umra-detail-grid">
                            <div class="umra-detail-item">
                                <span>Original Principal</span>
                                <strong>UGX {{ number_format($loan['original_principal'], 0) }}</strong>
                            </div>
                            <div class="umra-detail-item">
                                <span>Accrued Interest</span>
                                <strong>UGX {{ number_format($loan['accrued_interest'], 0) }}</strong>
                            </div>
                            <div class="umra-detail-item">
                                <span>Collateral Type</span>
                                <p>{{ $loan['collateral_type'] }}</p>
                            </div>
                            <div class="umra-detail-item">
                                <span>Forced Sale Value</span>
                                <strong>{{ $loan['forced_sale_value'] ? 'UGX ' . number_format($loan['forced_sale_value'], 0) : 'N/A' }}</strong>
                            </div>
                            <div class="umra-detail-item">
                                <span>FSV Coverage</span>
                                <strong>
                                    @if($loan['fsv_coverage_ratio'] !== null)
                                        {{ number_format($loan['fsv_coverage_ratio'], 1) }}%
                                        <span class="badge bg-{{ $loan['fsv_coverage_ratio'] >= 100 ? 'success' : 'warning' }}">
                                            {{ $loan['fsv_coverage_ratio'] >= 100 ? 'Normal' : 'Shortfall' }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </strong>
                            </div>
                            <div class="umra-detail-item">
                                <span>Loan Status</span>
                                <strong>{{ ucfirst($loan['loan_status']) }}</strong>
                            </div>
                            <div class="umra-detail-item">
                                <span>Interest Treatment</span>
                                <p>{{ $loan['interest_treatment'] }}</p>
                            </div>
                            <div class="umra-detail-item">
                                <span>Collection Action</span>
                                <p>{{ $loan['required_collection_action'] }}</p>
                            </div>
                            <div class="umra-detail-item">
                                <span>Write-off Flag</span>
                                @if($loan['writeoff_flag'] === 'Write-off review')
                                    <strong><span class="badge bg-danger">{{ $loan['writeoff_flag'] }}</span></strong>
                                @else
                                    <strong><span class="badge bg-secondary">{{ $loan['writeoff_flag'] }}</span></strong>
                                @endif
                            </div>
                            <div class="umra-detail-item">
                                <span>Latest Follow-up</span>
                                @if($loan['has_follow_up'])
                                    <strong>{{ $loan['latest_follow_up_date'] ?: 'N/A' }}</strong>
                                    <p>{{ $loan['latest_follow_up_method'] }} by {{ $loan['latest_follow_up_by'] ?: 'Staff' }}</p>
                                @else
                                    <strong>No follow-up recorded</strong>
                                @endif
                            </div>
                            <div class="umra-detail-item">
                                <span>Next Follow-up</span>
                                <strong>{{ $loan['next_follow_up_date'] ?: 'N/A' }}</strong>
                            </div>
                            <div class="umra-detail-item">
                                <span>SMS Sent</span>
                                <strong>{{ $loan['latest_follow_up_sms_sent'] ? 'Yes' : 'No' }}</strong>
                            </div>
                            <div class="umra-detail-item is-wide">
                                <span>Latest Follow-up Notes</span>
                                <p>{{ $loan['latest_follow_up_notes'] ?: 'No notes recorded.' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No loan records found.</div>
                @endforelse
            </div>
        </div>
        <div class="umra-table-footer">
            Showing {{ number_format($loanRecords->count()) }} of {{ number_format($totalLoanRecords) }} generated loan records.
        </div>
    </div>
</div>
@endsection
