@php
    $reportLocked = $calculation && (!$calculation->isReviewable() || data_get($report, 'status') === 'approved');
    $reportItems = old('items', data_get($report, 'items', []));
    if (!$reportItems) $reportItems = [['date' => $periodStart->toDateString(), 'category' => 'transport', 'description' => '', 'amount' => '', 'reference' => '', 'expenditure_id' => '']];
@endphp
<div class="card mt-4" id="weekly-spending-report">
    <div class="card-header d-flex justify-content-between"><h5 class="mb-0">My weekly spending report</h5><span class="badge bg-secondary">{{ ucfirst(data_get($report, 'status', 'Not submitted')) }}</span></div>
    <div class="card-body">
        <p class="text-muted">Report the money you actually received and every expense incurred from {{ $periodStart->toDateString() }} to {{ $periodEnd->toDateString() }}. Include receipt or evidence references. An unreconciled cash advance is funding received, not an expense.</p>
        @if(data_get($report, 'review_reason'))<div class="alert alert-info">Administrator: {{ $report['review_reason'] }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.remuneration.spending-report.store') }}">
            @csrf
            <input type="hidden" name="week_start" value="{{ $periodStart->toDateString() }}">
            <fieldset @disabled($reportLocked)>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label" for="received_amount">Amount received this week (UGX)</label><input class="form-control" id="received_amount" name="received_amount" type="number" min="0" step="0.01" required value="{{ old('received_amount', data_get($report, 'received_amount')) }}"></div>
                    <div class="col-md-8"><label class="form-label" for="funding_reference">Funding/payment reference</label><input class="form-control" id="funding_reference" name="funding_reference" maxlength="500" value="{{ old('funding_reference', data_get($report, 'funding_reference')) }}" placeholder="Payment reference, voucher or evidence of funds received"></div>
                </div>
                <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount (UGX)</th><th>Receipt/evidence reference</th><th>Existing expense #</th><th></th></tr></thead><tbody id="spending-report-lines">
                    @foreach($reportItems as $index => $item)
                        @include('admin.staff-remuneration.partials.spending-report-line', ['index' => $index, 'item' => $item])
                    @endforeach
                </tbody></table></div>
                @unless($reportLocked)<button class="btn btn-sm btn-outline-secondary mb-3" type="button" id="add-spending-line">Add expense line</button>@endunless
                <p class="small text-muted">Enter an existing expense number only when that expense is already recorded in E-BIMS; it will be counted once. Leave all expense rows empty if you spent nothing.</p>
                <label class="d-block mb-2"><input type="checkbox" name="activities_completed" value="1" @checked(old('activities_completed', data_get($report, 'activities_completed')))> I completed the required activities for this week.</label>
                <label class="d-block mb-3"><input type="checkbox" name="report_complete" value="1" required @checked(old('report_complete', data_get($report, 'report_complete')))> This is my complete expense report, including any spending above funds received.</label>
                <label class="form-label" for="spending_notes">Notes</label><textarea class="form-control mb-3" name="notes" id="spending_notes" rows="2">{{ old('notes', data_get($report, 'notes')) }}</textarea>
                @unless($reportLocked)<button class="btn btn-primary" type="submit">Submit weekly report</button>@endunless
            </fieldset>
        </form>
        @if($reportLocked)<p class="text-muted mb-0">This report is locked. Ask an administrator to return it before making changes.</p>@endif
    </div>
</div>
@unless($reportLocked)
<template id="spending-line-template">@include('admin.staff-remuneration.partials.spending-report-line', ['index' => '__index__', 'item' => ['date' => $periodStart->toDateString()]])</template>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lines = document.getElementById('spending-report-lines');
    let nextIndex = {{ count($reportItems) }};
    document.getElementById('add-spending-line').addEventListener('click', () => {
        lines.insertAdjacentHTML('beforeend', document.getElementById('spending-line-template').innerHTML.replaceAll('__index__', String(nextIndex++)));
    });
    lines.addEventListener('click', event => { const button = event.target.closest('[data-remove-expense]'); if (button) button.closest('tr').remove(); });
});
</script>
@endpush
@endunless
