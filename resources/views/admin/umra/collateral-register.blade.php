@extends('layouts.admin')

@section('title', 'UMRA Collateral Register')

@section('content')
@php
    $activeQuery = array_filter([
        'q' => $filters['q'] ?? '',
        'branch' => $filters['branch'] ?? '',
        'collateral_status' => $filters['collateral_status'] ?? 'all',
    ], fn ($value) => $value !== '' && $value !== null && $value !== 'all');
@endphp
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title">UMRA Collateral Register</h4>
                    <p class="text-muted mb-0">
                        Collateral register generated {{ $generatedDate->format('d M Y H:i') }}.
                        Showing {{ number_format($collateralRegister->count()) }} of {{ number_format($totalCollateralRows ?? $collateralRegister->count()) }} rows.
                    </p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.umra.collateral-register.export', $activeQuery) }}" class="btn btn-sm btn-outline-success">
                        <i class="mdi mdi-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.umra.collateral-register.pdf', $activeQuery) }}" class="btn btn-sm btn-outline-danger">
                        <i class="mdi mdi-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('admin.umra.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.umra.collateral-register') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Loan, client, officer, collateral...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Collateral Status</label>
                        <select name="collateral_status" class="form-select">
                            @foreach(($filterOptions['statuses'] ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['collateral_status'] ?? 'all') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select name="branch" class="form-select">
                            <option value="">All branches</option>
                            @foreach(($filterOptions['branches'] ?? []) as $branch)
                                <option value="{{ $branch }}" {{ ($filters['branch'] ?? '') === $branch ? 'selected' : '' }}>
                                    {{ $branch }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="mdi mdi-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.umra.collateral-register') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="table-responsive umra-collateral-table-wrap">
                <table class="table table-sm table-hover align-middle umra-collateral-table">
                    <thead class="table-light">
                        <tr>
                            <th class="umra-col-loan">Loan &amp; client</th>
                            <th class="umra-col-branch">Branch &amp; officer</th>
                            <th class="umra-col-collateral">Collateral</th>
                            <th class="umra-col-values">Values</th>
                            <th class="umra-col-source">Source &amp; status</th>
                            <th class="umra-col-actions text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collateralRegister as $collateral)
                        <tr>
                            <td data-label="Loan & client">
                                <div class="umra-primary-value">{{ $collateral['loan_account_no'] }}</div>
                                <div class="umra-secondary-value" title="{{ $collateral['client_name'] }}">
                                    {{ $collateral['client_name'] }}
                                </div>
                            </td>
                            <td data-label="Branch & officer">
                                <div class="umra-primary-value" title="{{ $collateral['branch'] }}">{{ $collateral['branch'] }}</div>
                                <div class="umra-secondary-value" title="{{ $collateral['assigned_officer'] }}">
                                    <i class="mdi mdi-account-outline"></i> {{ $collateral['assigned_officer'] }}
                                </div>
                            </td>
                            <td data-label="Collateral">
                                <div class="umra-primary-value">{{ $collateral['collateral_type'] }}</div>
                                @if(filled($collateral['description']))
                                    <div class="umra-secondary-value"><i class="mdi mdi-text-box-outline"></i> Description available</div>
                                @else
                                    <div class="umra-secondary-value">No description recorded</div>
                                @endif
                            </td>
                            <td data-label="Values">
                                <div>
                                    <span class="text-muted small d-block">Estimated</span>
                                    @if($collateral['estimated_value'] !== null)
                                        <strong>{{ number_format($collateral['estimated_value'], 2) }}</strong>
                                    @else
                                        <span class="text-muted">Not valued</span>
                                    @endif
                                </div>
                                <div class="mt-1">
                                    <span class="text-muted small d-block">FSV</span>
                                    @if(($collateral['forced_sale_value'] ?? null) !== null)
                                        <strong>{{ number_format($collateral['forced_sale_value'], 2) }}</strong>
                                    @else
                                        <span class="text-muted">Not valued</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Source & status">
                                @php
                                    $statusClass = match ($collateral['status']) {
                                        'Registered', 'Documented', 'Paid' => 'bg-success',
                                        'Pending' => 'bg-warning text-dark',
                                        'Missing', 'Missing document' => 'bg-danger',
                                        'Returned' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <div class="umra-primary-value" title="{{ $collateral['source'] }}">{{ $collateral['source'] }}</div>
                                <span class="badge {{ $statusClass }} mt-1">{{ $collateral['status'] }}</span>
                            </td>
                            <td class="text-center" data-label="Actions">
                                <div class="umra-action-buttons">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary umra-view-collateral"
                                        data-bs-toggle="modal"
                                        data-bs-target="#collateralDetailsModal"
                                        data-loan-account="{{ $collateral['loan_account_no'] }}"
                                        data-client="{{ $collateral['client_name'] }}"
                                        data-branch="{{ $collateral['branch'] }}"
                                        data-officer="{{ $collateral['assigned_officer'] }}"
                                        data-type="{{ $collateral['collateral_type'] }}"
                                        data-source="{{ $collateral['source'] }}"
                                        data-status="{{ $collateral['status'] }}"
                                        data-estimated-value="{{ $collateral['estimated_value'] !== null ? number_format($collateral['estimated_value'], 2) : 'Not valued' }}"
                                        data-forced-sale-value="{{ ($collateral['forced_sale_value'] ?? null) !== null ? number_format($collateral['forced_sale_value'], 2) : 'Not valued' }}"
                                        data-description="{{ $collateral['description'] }}"
                                        data-schedule-url="{{ route('admin.loans.repayments.schedules', $collateral['loan_id']) }}"
                                    >
                                        <i class="mdi mdi-eye-outline"></i> View details
                                    </button>
                                    <a href="{{ route('admin.loans.repayments.schedules', $collateral['loan_id']) }}" class="btn btn-sm btn-outline-secondary" title="Open loan schedule" aria-label="Open loan schedule">
                                        <i class="mdi mdi-calendar-text-outline"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No collateral records found for active loans.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <div class="modal fade umra-collateral-modal" id="collateralDetailsModal" tabindex="-1" aria-labelledby="collateralDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-1" id="collateralDetailsModalLabel">Collateral details</h5>
                                    <div class="small text-muted" id="collateralModalLoanClient"></div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Loan account</div>
                                        <div class="umra-detail-value" id="collateralModalLoanAccount"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Client</div>
                                        <div class="umra-detail-value" id="collateralModalClient"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Branch</div>
                                        <div class="umra-detail-value" id="collateralModalBranch"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Assigned officer</div>
                                        <div class="umra-detail-value" id="collateralModalOfficer"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Collateral type</div>
                                        <div class="umra-detail-value" id="collateralModalType"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Source / reference</div>
                                        <div class="umra-detail-value" id="collateralModalSource"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Estimated value</div>
                                        <div class="umra-detail-value" id="collateralModalEstimatedValue"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="umra-detail-label">Forced sale value</div>
                                        <div class="umra-detail-value" id="collateralModalForcedSaleValue"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="umra-detail-label">Status</div>
                                        <span class="badge" id="collateralModalStatus"></span>
                                    </div>
                                    <div class="col-12">
                                        <div class="umra-detail-label">Description</div>
                                        <div class="umra-description-box" id="collateralModalDescription"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="#" class="btn btn-primary" id="collateralModalScheduleLink">
                                    <i class="mdi mdi-calendar-text-outline"></i> Open loan schedule
                                </a>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .umra-collateral-table-wrap {
        overflow-x: hidden;
    }

    .umra-collateral-table {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
    }

    .umra-collateral-table th,
    .umra-collateral-table td {
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    .umra-collateral-table th {
        white-space: nowrap;
        font-size: .72rem;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .umra-col-loan { width: 17%; }
    .umra-col-branch { width: 17%; }
    .umra-col-collateral { width: 18%; }
    .umra-col-values { width: 17%; }
    .umra-col-source { width: 16%; }
    .umra-col-actions { width: 15%; }

    .umra-primary-value {
        color: #1f2937;
        font-weight: 600;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .umra-secondary-value {
        color: #64748b;
        font-size: .78rem;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .umra-secondary-value .mdi {
        font-size: .85rem;
    }

    .umra-action-buttons {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        flex-wrap: wrap;
    }

    .umra-action-buttons .btn {
        white-space: nowrap;
    }

    /* Keep action controls readable even when the admin theme changes button hover styles. */
    .umra-action-buttons .umra-view-collateral,
    .umra-action-buttons .umra-view-collateral:visited {
        color: #ffffff !important;
        background-color: #111827 !important;
        border-color: #111827 !important;
    }

    .umra-action-buttons .umra-view-collateral:hover,
    .umra-action-buttons .umra-view-collateral:focus,
    .umra-action-buttons .umra-view-collateral:focus-visible {
        color: #ffffff !important;
        background-color: #2563eb !important;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .2);
    }

    .umra-action-buttons .umra-view-collateral:active {
        color: #ffffff !important;
        background-color: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
    }

    .umra-action-buttons .btn-outline-secondary,
    .umra-action-buttons .btn-outline-secondary:visited {
        color: #334155 !important;
        background-color: #ffffff !important;
        border-color: #94a3b8 !important;
    }

    .umra-action-buttons .btn-outline-secondary:hover,
    .umra-action-buttons .btn-outline-secondary:focus,
    .umra-action-buttons .btn-outline-secondary:focus-visible {
        color: #1e293b !important;
        background-color: #e2e8f0 !important;
        border-color: #64748b !important;
    }

    .umra-collateral-modal {
        position: fixed !important;
        inset: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 1rem !important;
        overflow-x: hidden;
        overflow-y: auto;
        z-index: 10060 !important;
    }

    .umra-collateral-modal .modal-dialog {
        display: flex;
        align-items: center;
        width: 100%;
        max-width: 980px;
        min-height: calc(100% - 2rem);
        margin: 0 auto;
    }

    .umra-collateral-modal .modal-content {
        width: 100%;
        border: 0;
        border-radius: .8rem;
        box-shadow: 0 1rem 3rem rgba(15, 23, 42, .25);
    }

    .umra-collateral-modal .modal-body {
        max-height: min(70vh, 42rem);
        overflow-y: auto;
    }

    .umra-detail-label {
        margin-bottom: .25rem;
        color: #64748b;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .umra-detail-value {
        min-height: 2.3rem;
        padding: .55rem .7rem;
        color: #1e293b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .45rem;
    }

    .umra-description-box {
        min-height: 5rem;
        padding: .75rem;
        color: #334155;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .45rem;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    @media (max-width: 767.98px) {
        .umra-collateral-table thead {
            display: none;
        }

        .umra-collateral-table,
        .umra-collateral-table tbody,
        .umra-collateral-table tr,
        .umra-collateral-table td {
            display: block;
            width: 100%;
        }

        .umra-collateral-table tr {
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: .6rem;
            overflow: hidden;
        }

        .umra-collateral-table td {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border: 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .umra-collateral-table td::before {
            flex: 0 0 36%;
            color: #64748b;
            content: attr(data-label);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .umra-collateral-table td:last-child {
            border-bottom: 0;
        }

        .umra-collateral-table td > *:not(.umra-action-buttons) {
            min-width: 0;
            text-align: right;
        }

        .umra-action-buttons {
            justify-content: flex-end;
        }

        .umra-collateral-modal {
            padding: .5rem !important;
        }

        .umra-collateral-modal .modal-dialog {
            min-height: calc(100% - 1rem);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('collateralDetailsModal');

        if (!modal) {
            return;
        }

        const fields = {
            loanAccount: document.getElementById('collateralModalLoanAccount'),
            client: document.getElementById('collateralModalClient'),
            branch: document.getElementById('collateralModalBranch'),
            officer: document.getElementById('collateralModalOfficer'),
            type: document.getElementById('collateralModalType'),
            source: document.getElementById('collateralModalSource'),
            estimatedValue: document.getElementById('collateralModalEstimatedValue'),
            forcedSaleValue: document.getElementById('collateralModalForcedSaleValue'),
            status: document.getElementById('collateralModalStatus'),
            description: document.getElementById('collateralModalDescription'),
            loanClient: document.getElementById('collateralModalLoanClient'),
            scheduleLink: document.getElementById('collateralModalScheduleLink'),
        };

        document.querySelectorAll('.umra-view-collateral').forEach(function (button) {
            button.addEventListener('click', function () {
                const data = button.dataset;
                const status = data.status || 'Unknown';

                fields.loanAccount.textContent = data.loanAccount || 'Not recorded';
                fields.client.textContent = data.client || 'Not recorded';
                fields.branch.textContent = data.branch || 'Not recorded';
                fields.officer.textContent = data.officer || 'Not assigned';
                fields.type.textContent = data.type || 'Not recorded';
                fields.source.textContent = data.source || 'Not recorded';
                fields.estimatedValue.textContent = data.estimatedValue || 'Not valued';
                fields.forcedSaleValue.textContent = data.forcedSaleValue || 'Not valued';
                fields.description.textContent = data.description || 'No description recorded.';
                fields.loanClient.textContent = [data.loanAccount, data.client].filter(Boolean).join(' - ');
                fields.scheduleLink.href = data.scheduleUrl || '#';
                fields.scheduleLink.className = data.scheduleUrl ? 'btn btn-primary' : 'btn btn-primary disabled';
                fields.status.textContent = status;
                fields.status.className = 'badge ' + ({
                    Registered: 'bg-success',
                    Documented: 'bg-success',
                    Paid: 'bg-success',
                    Pending: 'bg-warning text-dark',
                    Missing: 'bg-danger',
                    'Missing document': 'bg-danger',
                    Returned: 'bg-secondary',
                }[status] || 'bg-secondary');
            });
        });

        modal.addEventListener('show.bs.modal', function () {
            window.setTimeout(function () {
                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.style.zIndex = '10050';
                });
            }, 0);
        });
    });
</script>
@endpush
