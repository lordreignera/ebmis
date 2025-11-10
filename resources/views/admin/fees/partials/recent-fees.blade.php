<div class="recent-fees">
    @if($fees->count() > 0)
        @foreach($fees as $fee)
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div>
                    <small class="text-muted">{{ $fee->feeType->name }}</small>
                    <br>
                    <strong>UGX {{ number_format($fee->amount) }}</strong>
                </div>
                <div class="text-end">
                    @if($fee->status == 1)
                        <span class="badge bg-success">Paid</span>
                    @else
                        <span class="badge bg-warning">Pending</span>
                    @endif
                    <br>
                    <small class="text-muted">{{ $fee->datecreated ? $fee->datecreated->format('M d') : 'N/A' }}</small>
                </div>
            </div>
        @endforeach
        
        <div class="mt-3">
            <a href="{{ route('admin.fees.index', ['search' => $fees->first()->member->code]) }}" 
               class="btn btn-sm btn-outline-primary w-100">
                View All Fees
            </a>
        </div>
    @else
        <div class="text-center py-3">
            <i class="mdi mdi-credit-card-off mdi-48px text-muted"></i>
            <br>
            <small class="text-muted">No recent fees found</small>
        </div>
    @endif
</div>