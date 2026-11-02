@if($savedCalculation)
    @if($savedCalculation->expenditure && in_array($savedCalculation->expenditure->status, ['rejected', 'cancelled']))
        <div class="card mt-4 border-warning"><div class="card-body">
            <h5>Rejected payout: reopen for correction</h5>
            <p>Request {{ $savedCalculation->expenditure->expense_number }} remains preserved. Reopening reverses any posted allocation journal, unlocks review and requires a fresh calculation before a replacement request.</p>
            <form method="POST" action="{{ route('admin.expenditures.remuneration.calculations.reopen', $savedCalculation) }}">@csrf
                <label class="form-label">Correction reason</label><input class="form-control mb-2" name="reason" required minlength="10" maxlength="1000">
                <button class="btn btn-outline-warning">Reopen rejected payout for correction</button>
            </form>
        </div></div>
    @endif
    @if(data_get($savedCalculation->input_snapshot, 'payout_history'))
        <div class="card mt-4"><div class="card-body"><h5>Previous payout requests</h5><ul class="mb-0">
            @foreach($savedCalculation->input_snapshot['payout_history'] as $previousPayout)
                <li><a href="{{ route('admin.expenditures.show', $previousPayout['expenditure_id']) }}">{{ $previousPayout['expense_number'] }}</a> — {{ $previousPayout['status'] }}. {{ $previousPayout['reason'] }}</li>
            @endforeach
        </ul></div></div>
    @endif
    @php($spendingReport = data_get($savedCalculation->budget_snapshot, 'report'))
    <div class="card mt-4"><div class="card-header"><h5 class="mb-0">Weekly spending report review</h5></div><div class="card-body">
        @if($spendingReport)
            <p><strong>{{ ucfirst($spendingReport['status']) }}</strong> &middot; Received UGX {{ number_format($spendingReport['received_amount'], 2) }} &middot; Reported spending UGX {{ number_format($spendingReport['reported_expenses'], 2) }}</p>
            <p class="small">Funding evidence: {{ $spendingReport['funding_reference'] ?: 'No funds received' }}<br>Officer note: {{ $spendingReport['notes'] ?: 'None' }}</p>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Category</th><th>Description</th><th>UGX</th><th>Evidence</th><th>Expense #</th></tr></thead><tbody>
            @foreach($spendingReport['items'] as $item)<tr><td>{{ $item['date'] }}</td><td>{{ str_replace('_', ' ', $item['category']) }}</td><td>{{ $item['description'] }}</td><td>{{ number_format($item['amount'], 2) }}</td><td>{{ $item['reference'] }}</td><td>{{ $item['expenditure_id'] ?: 'Reported actual cost' }}</td></tr>@endforeach
            </tbody></table></div>
            @if($savedCalculation->status === 'review' && !$savedCalculation->expenditure_id && in_array($spendingReport['status'], ['submitted', 'approved']))
                <form method="POST" action="{{ route('admin.expenditures.remuneration.calculations.report-review', $savedCalculation) }}" class="row g-3">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Decision</label><select class="form-select" name="decision"><option value="approve">Approve verified report</option><option value="return">Return for correction</option></select></div>
                    <div class="col-md-6"><label class="form-label">Review evidence / correction reason</label><input class="form-control" name="reason" required maxlength="2000"></div>
                    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-dark w-100">Record review</button></div>
                    <div class="col-12"><label class="me-3"><input type="checkbox" name="funding_verified" value="1"> I verified funds received against the funding evidence.</label><label><input type="checkbox" name="expenses_verified" value="1"> I verified actual expenses, activities and completeness.</label></div>
                </form>
            @endif
        @else
            <p class="text-muted mb-0">The officer has not submitted their weekly spending report. They can submit it from My remuneration performance.</p>
        @endif
    </div></div>
    @if($savedCalculation->status === 'review' && $policy->status === 'active' && data_get($savedCalculation->input_snapshot, 'ready_for_payment') && $savedCalculation->net_remuneration !== null && (float) $savedCalculation->net_remuneration === 0.0)
        <form class="my-3" method="POST" action="{{ route('admin.expenditures.remuneration.calculations.settle-zero', $savedCalculation) }}">@csrf<button class="btn btn-outline-dark">Authorize zero-net week and carry closing deficit</button></form>
    @endif
    @if(data_get($savedCalculation->input_snapshot, 'fund_allocations.enabled'))
        <div class="card mt-4"><div class="card-body">
            @if(data_get($savedCalculation->input_snapshot, 'fund_posting.journal_id'))
                <p class="mb-0">Fund allocations posted in journal #{{ data_get($savedCalculation->input_snapshot, 'fund_posting.journal_id') }}.</p>
            @elseif(in_array($savedCalculation->status, ['approved', 'payment_pending', 'payment_failed', 'paid', 'settled']))
                <p>Post the saved officer allocations using the ledger accounts configured in this policy version.</p>
                <form method="POST" action="{{ route('admin.expenditures.remuneration.calculations.funds', $savedCalculation) }}">@csrf<button class="btn btn-outline-primary">Post officer fund allocations</button></form>
            @else
                <p class="mb-0 text-muted">Fund posting becomes available after the payout expenditure is approved, or after independent authorization of a zero-net week.</p>
            @endif
            @if($savedCalculation->expenditure_id)<a class="btn btn-sm btn-outline-dark mt-3" href="{{ route('admin.expenditures.show', $savedCalculation->expenditure_id) }}">Open approval and mobile-money payment</a>@endif
        </div></div>
    @endif
@endif
