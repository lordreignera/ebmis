@extends('layouts.admin')

@section('title', 'Staff Remuneration Review')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex justify-content-between align-items-center">
        <div>
            <h4 class="page-title mb-1">Staff remuneration review</h4>
            <p class="text-muted mb-0">Review and save one weekly calculation before payment approval.</p>
        </div>
        <span class="badge bg-warning text-dark">{{ ucfirst($policy->status) }} policy · {{ $policy->version }}</span>
    </div>

    <div class="d-flex justify-content-end mb-3"><a href="{{ route('admin.expenditures.remuneration.policies.index') }}" class="btn btn-outline-dark"><i class="mdi mdi-cog-outline me-1"></i> Manage policy versions</a></div>
    @include('admin.expenditures.partials.alerts')

    <div class="alert alert-info border-0">
        <strong>Review before payment.</strong> Save the weekly result after checking its inputs. A payout request requires an active policy and resolved scores. Missing policy rules and expense verification are listed with the calculation.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.expenditures.remuneration') }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="officer_id">Officer</label>
                    <select class="form-select" id="officer_id" name="officer_id">
                        <option value="">Select an officer</option>
                        @foreach($officers as $officer)
                            <option value="{{ $officer->id }}" @selected((string) request('officer_id') === (string) $officer->id)>
                                {{ $officer->name }}{{ $officer->designation ? ' — ' . $officer->designation : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label" for="period_start">Week start</label>
                    <input class="form-control" type="date" id="period_start" name="period_start" value="{{ $periodStart->toDateString() }}" required>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label" for="period_end">Week end</label>
                    <input class="form-control" type="date" id="period_end" name="period_end" value="{{ $periodEnd->toDateString() }}" readonly aria-describedby="week-end-help">
                    <small class="text-muted" id="week-end-help">Calculated Sunday for the selected week.</small>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label" for="manual_budget">Manual first-week budget</label>
                    <input class="form-control" type="text" id="manual_budget" name="manual_budget" value="{{ request('manual_budget') }}" placeholder="Optional UGX">
                </div>
                <div class="col-lg-2 col-md-6">
                    <button class="btn btn-dark w-100" type="submit"><i class="mdi mdi-eye-outline me-1"></i>Preview</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All officer performance</h5>
            <small class="text-muted">{{ $periodStart->toDateString() }} to {{ $periodEnd->toDateString() }}</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead><tr><th>Officer</th><th>Qualified revenue</th><th>Current-loan score</th><th>Base</th><th>Bonus rate</th><th>Budget overrun</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($summaryRows as $row)
                    @php
                        $summary = $row['preview'];
                    @endphp
                    <tr>
                        <td><strong>{{ $row['officer']->name }}</strong><small class="d-block text-muted">{{ $row['officer']->designation ?: 'Staff' }} · {{ ucfirst($row['officer']->status) }}</small></td>
                        <td>{{ $summary ? 'UGX ' . number_format($summary['qualified_revenue']['qualified_revenue'], 2) : '—' }}</td>
                        <td>{{ !$summary || $summary['current_loan_score'] === null ? 'Unresolved' : number_format($summary['current_loan_score'], 2) . '%' }}</td>
                        <td>{{ !$summary || $summary['base_amount'] === null ? 'Unresolved' : 'UGX ' . number_format($summary['base_amount'], 2) }}<small class="d-block text-muted">{{ !$summary || $summary['base_rate'] === null ? '—' : number_format($summary['base_rate'], 2) . '%' }}</small></td>
                        <td>{{ !$summary || !$row['bonuses_resolved'] ? 'Unresolved' : number_format($summary['total_bonus_rate'], 2) . '% / 12%' }}</td>
                        <td>{{ !$summary ? '—' : 'UGX ' . number_format($summary['budget']['budget_overrun'], 2) }}</td>
                        <td>
                            @if($row['calculation'])
                                <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $row['calculation']->status)) }}</span>
                            @else
                                <span class="badge {{ $summary && $summary['ready_for_payment'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $summary && $summary['ready_for_payment'] ? 'Ready to save' : 'Review inputs' }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#remunerationDetailsModal{{ $row['officer']->id }}">
                                <i class="mdi mdi-eye-outline me-1"></i>Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted">No officer activity was found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($summaryRows as $row)
        @php
            $modalOfficer = $row['officer'];
            $modalSummary = $row['preview'];
            $modalCalculation = $row['calculation'];
            $modalNeedsReview = !$modalCalculation || $modalCalculation->isReviewable();
            $modalDetailsUrl = route('admin.expenditures.remuneration', [
                'officer_id' => $modalOfficer->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);
        @endphp
        <div class="modal fade" id="remunerationDetailsModal{{ $modalOfficer->id }}" tabindex="-1" aria-labelledby="remunerationDetailsLabel{{ $modalOfficer->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title mb-1" id="remunerationDetailsLabel{{ $modalOfficer->id }}">
                                {{ $modalOfficer->name }} — performance summary
                            </h5>
                            <div class="small text-muted">
                                {{ $modalOfficer->designation ?: 'Staff officer' }} · {{ ucfirst($modalOfficer->status) }} ·
                                {{ $periodStart->toDateString() }} to {{ $periodEnd->toDateString() }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <span class="badge {{ $modalSummary && $modalSummary['ready_for_payment'] ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $modalSummary && $modalSummary['ready_for_payment'] ? 'Ready for approval' : 'Not ready for payment' }}
                            </span>
                            <span class="small text-muted">
                                Calculation:
                                {{ $modalCalculation ? ucfirst(str_replace('_', ' ', $modalCalculation->status)) : 'Not saved yet' }}
                            </span>
                        </div>

                        @if($policy->status !== 'active')
                            <div class="alert alert-warning border-warning">
                                <div class="d-flex gap-2">
                                    <i class="mdi mdi-alert-outline mdi-24px"></i>
                                    <div>
                                        <strong>The current remuneration policy is still {{ $policy->status }}.</strong>
                                        <p class="mb-2">
                                            This summary can be reviewed and saved, but it cannot be paid until the policy is completed,
                                            approved, and activated.
                                        </p>
                                        <ol class="mb-2 ps-3 small">
                                            <li>Open policy management and complete the missing policy rules.</li>
                                            <li>Save the policy draft.</li>
                                            <li>Approve it, then activate it.</li>
                                            <li>Return here, refresh the officer, and save the calculation again.</li>
                                        </ol>
                                        <a href="{{ route('admin.expenditures.remuneration.policies.index') }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-cog-outline me-1"></i>Open policy management
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($modalSummary)
                            <div class="row g-3 mb-4">
                                <div class="col-md-3"><div class="remuneration-summary-stat"><span>Qualified revenue</span><strong>UGX {{ number_format($modalSummary['qualified_revenue']['qualified_revenue'], 2) }}</strong></div></div>
                                <div class="col-md-3"><div class="remuneration-summary-stat"><span>Base remuneration</span><strong>{{ $modalSummary['base_amount'] === null ? 'Unresolved' : 'UGX '.number_format($modalSummary['base_amount'], 2) }}</strong></div></div>
                                <div class="col-md-3"><div class="remuneration-summary-stat"><span>Bonus amount</span><strong>{{ $modalSummary['bonus_amount'] === null ? 'Unresolved' : 'UGX '.number_format($modalSummary['bonus_amount'], 2) }}</strong></div></div>
                                <div class="col-md-3"><div class="remuneration-summary-stat"><span>Net preview</span><strong>{{ $modalSummary['net_remuneration'] === null ? 'Unresolved' : 'UGX '.number_format($modalSummary['net_remuneration'], 2) }}</strong></div></div>
                            </div>

                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="card h-100 border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3">Performance at a glance</h6>
                                            <dl class="row mb-0 small">
                                                <dt class="col-7">Active loans</dt><dd class="col-5 text-end">{{ number_format($modalSummary['portfolio']['total_active_loans']) }}</dd>
                                                <dt class="col-7">Current active loans</dt><dd class="col-5 text-end">{{ number_format($modalSummary['portfolio']['current_active_loans']) }}</dd>
                                                <dt class="col-7">Current-loan score</dt><dd class="col-5 text-end">{{ $modalSummary['current_loan_score'] === null ? 'Unresolved' : number_format($modalSummary['current_loan_score'], 2).'%' }}</dd>
                                                <dt class="col-7">PAR</dt><dd class="col-5 text-end">{{ $modalSummary['portfolio']['par_percent'] === null ? 'Unresolved' : number_format($modalSummary['portfolio']['par_percent'], 2).'%' }}</dd>
                                                <dt class="col-7">Follow-ups completed</dt><dd class="col-5 text-end">{{ number_format($modalSummary['follow_ups']['completed_count']) }} / {{ number_format($modalSummary['follow_ups']['due_count']) }}</dd>
                                                <dt class="col-7">Client growth</dt><dd class="col-5 text-end">{{ number_format($modalSummary['growth']['opening_active_clients']) }} → {{ number_format($modalSummary['growth']['closing_active_clients']) }}</dd>
                                                <dt class="col-7">Budget overrun</dt><dd class="col-5 text-end">UGX {{ number_format($modalSummary['budget']['budget_overrun'], 2) }}</dd>
                                                <dt class="col-7">Policy version</dt><dd class="col-5 text-end">{{ $policy->version }}<small class="d-block text-muted">{{ ucfirst($policy->status) }}</small></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card h-100 border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3">Why this result is not payable</h6>
                                            @if($modalSummary['issues'])
                                                <ul class="mb-0 small ps-3">
                                                    @foreach($modalSummary['issues'] as $issue)
                                                        <li class="mb-2">{{ $issue }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-success mb-0 small"><i class="mdi mdi-check-circle-outline me-1"></i>No calculation blockers were found.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mt-4">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Bonus measure</th><th>Score</th><th>Earned rate</th><th>Status</th></tr></thead>
                                    <tbody>
                                    @foreach($modalSummary['metrics'] as $metric)
                                        <tr>
                                            <td>{{ ucwords(str_replace('_', ' ', $metric['metric_code'])) }}</td>
                                            <td>{{ $metric['score'] === null ? 'Unresolved' : number_format($metric['score'], 2).'%' }}</td>
                                            <td>{{ number_format($metric['earned_rate'], 2) }}% / {{ number_format($metric['maximum_rate'], 2) }}%</td>
                                            <td><span class="badge {{ $metric['is_resolved'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $metric['is_resolved'] ? 'Resolved' : 'Needs attention' }}</span></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">There is no performance preview for this officer and period yet.</div>
                        @endif

                        @if($modalNeedsReview)
                            <div class="alert alert-info mt-4 mb-0">
                                <strong>Next step:</strong> Save this officer's calculation for an auditable review record. Saving does not create a payment.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if($modalNeedsReview)
                            <form method="POST" action="{{ route('admin.expenditures.remuneration.calculations.store') }}" class="me-auto">
                                @csrf
                                <input type="hidden" name="officer_id" value="{{ $modalOfficer->id }}">
                                <input type="hidden" name="period_start" value="{{ $periodStart->toDateString() }}">
                                <input type="hidden" name="period_end" value="{{ $periodEnd->toDateString() }}">
                                <input type="hidden" name="manual_budget" value="{{ request('manual_budget') }}">
                                <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save-outline me-1"></i>Save calculation</button>
                            </form>
                        @endif
                        <a href="{{ $modalDetailsUrl }}" class="btn btn-outline-dark">Open full review</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if($preview)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">{{ $selectedOfficer->name }}</h5>
                <span class="text-muted">{{ $preview['period_start'] }} to {{ $preview['period_end'] }}</span>
            </div>
            <div class="text-end"><a href="{{ route('admin.expenditures.remuneration.policies.index') }}" class="btn btn-sm btn-outline-dark mb-2">Manage policy versions</a><br><span class="badge {{ $preview['ready_for_payment'] ? 'bg-success' : 'bg-secondary' }}">{{ $preview['ready_for_payment'] ? 'Ready for approval' : 'Review only — not payable' }}</span></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="metric-card"><span>Qualified revenue</span><strong>UGX {{ number_format($preview['qualified_revenue']['qualified_revenue'], 2) }}</strong></div></div>
            <div class="col-md-3"><div class="metric-card"><span>Current-loan score</span><strong>{{ $preview['current_loan_score'] === null ? 'Unresolved' : number_format($preview['current_loan_score'], 3) . '%' }}</strong></div></div>
            <div class="col-md-3"><div class="metric-card"><span>Base remuneration</span><strong>{{ $preview['base_amount'] === null ? 'Unresolved' : 'UGX ' . number_format($preview['base_amount'], 2) }}</strong></div></div>
            <div class="col-md-3"><div class="metric-card"><span>Net preview</span><strong>{{ $preview['net_remuneration'] === null ? 'Unresolved' : 'UGX ' . number_format($preview['net_remuneration'], 2) }}</strong></div></div>
        </div>

        @if($preview['issues'])
            <div class="alert alert-warning">
                <strong>Items blocking payment:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($preview['issues'] as $issue)<li>{{ $issue }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="card border-primary mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong>Calculation record</strong>
                    <div class="text-muted small">
                        @if($savedCalculation)
                            Saved as calculation #{{ $savedCalculation->id }} with status {{ str_replace('_', ' ', $savedCalculation->status) }}.
                        @else
                            This preview has not yet been saved. Saving creates an auditable record but does not create a payment.
                        @endif
                    </div>
                </div>
                @if(!$savedCalculation || $savedCalculation->isReviewable())
                    <form method="POST" action="{{ route('admin.expenditures.remuneration.calculations.store') }}">
                        @csrf
                        <input type="hidden" name="officer_id" value="{{ $selectedOfficer->id }}">
                        <input type="hidden" name="period_start" value="{{ $periodStart->toDateString() }}">
                        <input type="hidden" name="period_end" value="{{ $periodEnd->toDateString() }}">
                        <input type="hidden" name="manual_budget" value="{{ request('manual_budget') }}">
                        <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save-outline me-1"></i>{{ $savedCalculation ? 'Update saved calculation' : 'Save calculation for review' }}</button>
                    </form>
                @endif
            </div>
        </div>

        @if($savedCalculation && !$savedCalculation->expenditure_id && $savedCalculation->status === \App\Models\StaffRemunerationCalculation::STATUS_REVIEW && $policy->status === 'active' && data_get($savedCalculation->input_snapshot, 'ready_for_payment') && (float) $savedCalculation->net_remuneration > 0)
            <div class="card border-success mb-4">
                <div class="card-body">
                    <h6 class="mb-1">Create payment approval request</h6>
                    <p class="text-muted small">This creates a pending expenditure linked to calculation #{{ $savedCalculation->id }}. Approval and mobile-money payment continue through the existing expenditure workflow.</p>
                    <form method="POST" action="{{ route('admin.expenditures.remuneration.calculations.payout', $savedCalculation) }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label" for="expense_account_id">Expense account</label>
                            <select class="form-select" id="expense_account_id" name="expense_account_id" required>
                                <option value="">Select expense account</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->Id }}">{{ $account->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="payout_notes">Notes</label>
                            <input class="form-control" id="payout_notes" name="notes" placeholder="Optional approval note">
                        </div>
                        <div class="col-md-2"><button class="btn btn-success w-100" type="submit">Create request</button></div>
                    </form>
                </div>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0">Revenue and base</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-7">Interest revenue</dt><dd class="col-5 text-end">UGX {{ number_format($preview['qualified_revenue']['interest_revenue'], 2) }}</dd>
                            <dt class="col-7">Administration fees</dt><dd class="col-5 text-end">UGX {{ number_format($preview['qualified_revenue']['administration_fee_revenue'], 2) }}</dd>
                            <dt class="col-7">Registration fees</dt><dd class="col-5 text-end">UGX {{ number_format($preview['qualified_revenue']['registration_fee_revenue'], 2) }}</dd>
                            <dt class="col-7">Late/restructuring/other qualified fees</dt><dd class="col-5 text-end">UGX {{ number_format($preview['qualified_revenue']['late_fee_revenue'] + $preview['qualified_revenue']['late_fee_from_repayments'] + $preview['qualified_revenue']['restructuring_fee_revenue'] + $preview['qualified_revenue']['other_qualified_revenue'], 2) }}</dd>
                            <dt class="col-7 border-top pt-2">Qualified revenue</dt><dd class="col-5 text-end border-top pt-2 fw-bold">UGX {{ number_format($preview['qualified_revenue']['qualified_revenue'], 2) }}</dd>
                            <dt class="col-7">Base rate</dt><dd class="col-5 text-end">{{ $preview['base_rate'] === null ? 'Unresolved' : number_format($preview['base_rate'], 3) . '%' }}</dd>
                            <dt class="col-7">Base remuneration</dt><dd class="col-5 text-end fw-bold">{{ $preview['base_amount'] === null ? 'Unresolved' : 'UGX ' . number_format($preview['base_amount'], 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0">Portfolio and budget</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-7">Active loans</dt><dd class="col-5 text-end">{{ $preview['portfolio']['total_active_loans'] }}</dd>
                            <dt class="col-7">Current active loans</dt><dd class="col-5 text-end">{{ $preview['portfolio']['current_active_loans'] }}</dd>
                            <dt class="col-7">PAR (principal)</dt><dd class="col-5 text-end">{{ $preview['portfolio']['par_percent'] === null ? 'Unresolved' : number_format($preview['portfolio']['par_percent'], 3) . '%' }}</dd>
                            <dt class="col-7">Follow-ups completed / assigned loans</dt><dd class="col-5 text-end">{{ $preview['follow_ups']['completed_count'] }} / {{ $preview['follow_ups']['due_count'] }}</dd>
                            <dt class="col-7">Opening / closing clients</dt><dd class="col-5 text-end">{{ $preview['growth']['opening_active_clients'] }} / {{ $preview['growth']['closing_active_clients'] }}</dd>
                            <dt class="col-7">Previous-week revenue</dt><dd class="col-5 text-end">UGX {{ number_format($preview['budget']['previous_week_qualified_revenue'], 2) }}</dd>
                            <dt class="col-7">Budget / overrun</dt><dd class="col-5 text-end">UGX {{ number_format($preview['budget']['allocated_budget'], 2) }} / UGX {{ number_format($preview['budget']['budget_overrun'], 2) }}</dd>
                            <dt class="col-7">Approved operating expenses</dt><dd class="col-5 text-end">UGX {{ number_format($preview['budget']['approved_expenses'], 2) }}</dd>
                            <dt class="col-7">Budget utilization</dt><dd class="col-5 text-end">{{ $preview['budget']['utilization_percent'] === null ? 'Requires review' : number_format($preview['budget']['utilization_percent'], 3).'%' }}</dd>
                            <dt class="col-7">Gross remuneration</dt><dd class="col-5 text-end">{{ $preview['gross_remuneration'] === null ? 'Unresolved' : 'UGX '.number_format($preview['gross_remuneration'], 2) }}</dd>
                            <dt class="col-7">Deduction from gross / new deficit</dt><dd class="col-5 text-end">UGX {{ number_format($preview['gross_overrun_deduction'] ?? 0, 2) }} / UGX {{ number_format($preview['new_deficit_amount'] ?? 0, 2) }}</dd>
                            <dt class="col-7">Prior deficit recovered / closing</dt><dd class="col-5 text-end">UGX {{ number_format($preview['deficit_recovery_amount'] ?? 0, 2) }} / UGX {{ number_format($preview['closing_deficit'] ?? 0, 2) }}</dd>
                            <dt class="col-7">Funds actually received</dt><dd class="col-5 text-end">{{ $preview['budget']['received_amount'] === null ? 'Awaiting approved report' : 'UGX '.number_format($preview['budget']['received_amount'], 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="card-title mb-0">Four independent bonuses</h5></div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Metric</th><th>Score</th><th>Earned rate</th><th>Bonus amount</th><th>Status</th><th>Reason</th></tr></thead>
                    <tbody>
                    @foreach($preview['metrics'] as $metric)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $metric['metric_code'])) }}</td>
                            <td>{{ $metric['score'] === null ? 'Unresolved' : number_format($metric['score'], 3) . '%' }}</td>
                            <td>{{ number_format($metric['earned_rate'], 3) }}% / {{ number_format($metric['maximum_rate'], 3) }}%</td>
                            <td>UGX {{ number_format($metric['bonus_amount'], 2) }}</td>
                            <td><span class="badge {{ $metric['is_resolved'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $metric['is_resolved'] ? 'Resolved' : 'Unresolved' }}</span></td>
                            <td class="text-muted">{{ $metric['notes'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.staff-remuneration.partials.administrative-review')
        @include('admin.staff-remuneration.partials.fund-statement')
    @endif
</div>
@endsection

@push('styles')
<style>
.metric-card { border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; padding: 1rem; height: 100%; }
.metric-card span { display: block; color: #6b7280; font-size: .8rem; margin-bottom: .35rem; }
.metric-card strong { font-size: 1.15rem; }
.remuneration-summary-stat { border: 1px solid #e5e7eb; border-radius: .65rem; background: #fff; padding: .85rem; height: 100%; }
.remuneration-summary-stat span { display: block; color: #6b7280; font-size: .75rem; margin-bottom: .25rem; }
.remuneration-summary-stat strong { display: block; color: #111827; font-size: 1rem; }
</style>
@endpush
