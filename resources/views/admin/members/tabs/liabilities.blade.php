<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="mdi mdi-credit-card"></i> Liabilities Register</h5>
    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addLiabilityModal">
        <i class="mdi mdi-plus"></i> Add Liability
    </button>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6>Total Liabilities</h6>
                <h3>UGX {{ number_format($member->total_liabilities, 0) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Net Worth</h6>
                <h3>UGX {{ number_format($member->net_worth, 0) }}</h3>
            </div>
        </div>
    </div>
</div>

@if($member->liabilities->count() > 0)
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Liability Type</th>
                    <th>Business</th>
                    <th>Value (UGX)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($member->liabilities as $index => $liability)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $liability->liabilityType->name ?? 'N/A' }}</td>
                        <td>{{ $liability->business->name ?? 'Personal' }}</td>
                        <td><strong>{{ number_format($liability->value, 0) }}</strong></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editLiabilityModal"
                                    data-liability-id="{{ $liability->id }}"
                                    data-liability-type="{{ $liability->liability_type }}"
                                    data-business-id="{{ $liability->business_id ?? '' }}"
                                    data-value="{{ $liability->value }}">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <form action="{{ route('admin.members.liabilities.destroy', [$member, $liability]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this liability?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        <i class="mdi mdi-information"></i> No liabilities registered.
    </div>
@endif

<!-- All modals moved to main show.blade.php outside tab content to fix blinking issue -->
