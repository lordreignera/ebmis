@extends('layouts.admin')

@section('title', 'Investor Profile - ' . $investor->full_name)

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Investor Profile</h4>
                <p class="text-muted mb-0">{{ $investor->full_name }} - {{ ucfirst($investor->type) }} Investor</p>
            </div>
            <div>
                <a href="{{ route('admin.investments.create-investment', $investor->id) }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-plus"></i> Add Investment
                </a>
                <a href="{{ route('admin.investments.edit-investor', $investor->id) }}" class="btn btn-success btn-sm">
                    <i class="mdi mdi-pencil"></i> Edit
                </a>
                <a href="{{ route('admin.investments.investors') }}" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Investor Information -->
    <div class="col-lg-4">
        <div class="card card-bordered">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-primary text-white rounded-circle" style="width: 80px; height: 80px; line-height: 80px; font-size: 32px;">
                            {{ strtoupper(substr($investor->first_name, 0, 1) . substr($investor->last_name, 0, 1)) }}
                        </div>
                    </div>
                    <h5 class="mb-1">{{ $investor->full_name }}</h5>
                    <p class="text-muted mb-2">{{ ucfirst($investor->type) }} Investor</p>
                    <span class="badge {{ $investor->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $investor->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mb-3">
                    <h6 class="mb-2">Contact Information</h6>
                    <div class="d-flex align-items-center mb-2">
                        <i class="mdi mdi-email text-muted mr-2"></i>
                        <span>{{ $investor->email }}</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="mdi mdi-phone text-muted mr-2"></i>
                        <span>{{ $investor->phone }}</span>
                    </div>
                    @if($investor->address)
                    <div class="d-flex align-items-start mb-2">
                        <i class="mdi mdi-map-marker text-muted mr-2 mt-1"></i>
                        <span>{{ $investor->address }}</span>
                    </div>
                    @endif
                </div>

                <div class="mb-3">
                    <h6 class="mb-2">Personal Details</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Date of Birth:</span>
                        <span>{{ $investor->dob ? \Carbon\Carbon::parse($investor->dob)->format('M d, Y') : 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Age:</span>
                        <span>{{ $investor->dob ? \Carbon\Carbon::parse($investor->dob)->age . ' years' : 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Gender:</span>
                        <span>{{ $investor->gender ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Marital Status:</span>
                        <span>{{ $investor->marital_status ?? 'N/A' }}</span>
                    </div>
                    @if($investor->occupation)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Occupation:</span>
                        <span>{{ $investor->occupation }}</span>
                    </div>
                    @endif
                </div>

                <div class="mb-3">
                    <h6 class="mb-2">Location</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Country:</span>
                        <span>{{ $investor->country->name ?? 'N/A' }}</span>
                    </div>
                    @if($investor->state)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">State:</span>
                        <span>{{ $investor->state->name }}</span>
                    </div>
                    @endif
                    @if($investor->city)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">City:</span>
                        <span>{{ $investor->city->name }}</span>
                    </div>
                    @endif
                    @if($investor->postal_code)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Postal Code:</span>
                        <span>{{ $investor->postal_code }}</span>
                    </div>
                    @endif
                </div>

                @if($investor->investment_goal || $investor->risk_tolerance || $investor->experience)
                <div class="mb-3">
                    <h6 class="mb-2">Investment Profile</h6>
                    @if($investor->investment_goal)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Goal:</span>
                        <span>{{ $investor->investment_goal }}</span>
                    </div>
                    @endif
                    @if($investor->risk_tolerance)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Risk Tolerance:</span>
                        <span>{{ $investor->risk_tolerance }}</span>
                    </div>
                    @endif
                    @if($investor->experience)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Experience:</span>
                        <span>{{ $investor->experience }}</span>
                    </div>
                    @endif
                    @if($investor->income_range)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Income Range:</span>
                        <span>{{ $investor->income_range }}</span>
                    </div>
                    @endif
                </div>
                @endif

                @if($investor->notes)
                <div class="mb-3">
                    <h6 class="mb-2">Notes</h6>
                    <p class="small text-muted mb-0">{{ $investor->notes }}</p>
                </div>
                @endif

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Registered:</span>
                        <span>{{ $investor->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Last Updated:</span>
                        <span>{{ $investor->updated_at->diffForHumans() }}</span>
                    </div>
                </div>

                @if(!$investor->is_active)
                <div class="mt-3">
                    <form action="{{ route('admin.investments.activate-investor', $investor->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Are you sure you want to activate this investor?')">
                            <i class="mdi mdi-check"></i> Activate Investor
                        </button>
                    </form>
                </div>
                @else
                <div class="mt-3">
                    <button type="button" class="btn btn-warning btn-sm w-100" data-toggle="modal" data-target="#deactivateModal">
                        <i class="mdi mdi-pause"></i> Deactivate Investor
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Investment Portfolio -->
    <div class="col-lg-8">
        <!-- Portfolio Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-bordered">
                    <div class="card-body text-center">
                        <h3 class="text-primary mb-1">{{ $portfolio_stats['total_investments'] }}</h3>
                        <p class="text-muted mb-0">Total Investments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-bordered">
                    <div class="card-body text-center">
                        <h3 class="text-success mb-1">${{ number_format($portfolio_stats['total_amount'], 2) }}</h3>
                        <p class="text-muted mb-0">Total Invested</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-bordered">
                    <div class="card-body text-center">
                        <h3 class="text-info mb-1">${{ number_format($portfolio_stats['total_interest'], 2) }}</h3>
                        <p class="text-muted mb-0">Expected Returns</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-bordered">
                    <div class="card-body text-center">
                        <h3 class="text-warning mb-1">{{ $portfolio_stats['active_investments'] }}</h3>
                        <p class="text-muted mb-0">Active Investments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investments List -->
        <div class="card card-bordered">
            <div class="card-header">
                <h6 class="mb-0">Investment Portfolio</h6>
            </div>
            <div class="card-body">
                @if($investments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Investment Name</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Returns</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($investments as $investment)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="font-weight-medium">{{ $investment->inv_name }}</span>
                                        @if($investment->areas)
                                        <small class="text-muted">{{ implode(', ', json_decode($investment->areas, true) ?: []) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="font-weight-medium">${{ number_format($investment->inv_amt, 2) }}</span>
                                </td>
                                <td>
                                    @if($investment->inv_term == 1)
                                        <span class="badge badge-primary">Standard</span>
                                    @else
                                        <span class="badge badge-info">Compound</span>
                                    @endif
                                </td>
                                <td>{{ $investment->inv_period }} year{{ $investment->inv_period > 1 ? 's' : '' }}</td>
                                <td>
                                    <span class="text-success font-weight-medium">
                                        ${{ number_format($investment->total_return, 2) }}
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        ${{ number_format($investment->interest_amt, 2) }} profit
                                    </small>
                                </td>
                                <td>
                                    @php
                                        $startDate = \Carbon\Carbon::parse($investment->term_start);
                                        $endDate = \Carbon\Carbon::parse($investment->term_end);
                                        $now = \Carbon\Carbon::now();
                                        
                                        if ($now->lt($startDate)) {
                                            $statusClass = 'badge-secondary';
                                            $statusText = 'Pending';
                                        } elseif ($now->between($startDate, $endDate)) {
                                            $statusClass = 'badge-success';
                                            $statusText = 'Active';
                                        } else {
                                            $statusClass = 'badge-warning';
                                            $statusText = 'Matured';
                                        }
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($investment->term_start)->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.investments.show-investment', $investment->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.investments.edit-investment', $investment->id) }}" 
                                           class="btn btn-sm btn-outline-success" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                title="Delete" onclick="confirmDelete({{ $investment->id }})">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($investments->hasPages())
                <div class="mt-3">
                    {{ $investments->links() }}
                </div>
                @endif
                @else
                <div class="text-center py-4">
                    <i class="mdi mdi-chart-line" style="font-size: 48px; color: #ccc;"></i>
                    <h6 class="mt-3 text-muted">No Investments Yet</h6>
                    <p class="text-muted mb-0">This investor hasn't made any investments yet.</p>
                    <a href="{{ route('admin.investments.create-investment', $investor->id) }}" class="btn btn-primary mt-3">
                        <i class="mdi mdi-plus"></i> Create First Investment
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deactivate Investor</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate <strong>{{ $investor->full_name }}</strong>?</p>
                <p class="text-muted">This action will:</p>
                <ul class="text-muted">
                    <li>Prevent new investments from being created</li>
                    <li>Keep existing investments active</li>
                    <li>Mark the investor as inactive</li>
                </ul>
                <div class="form-group">
                    <label for="deactivation_reason">Reason for deactivation:</label>
                    <textarea class="form-control" id="deactivation_reason" name="reason" rows="3" placeholder="Optional reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.investments.deactivate-investor', $investor->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="reason" id="hidden_reason">
                    <button type="submit" class="btn btn-warning">Deactivate</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Investment Modal -->
<div class="modal fade" id="deleteInvestmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Investment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this investment?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteInvestmentForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Investment</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Handle deactivation reason
$('#deactivateModal').on('show.bs.modal', function () {
    $('#deactivation_reason').val('');
});

$('#deactivateModal form').on('submit', function() {
    $('#hidden_reason').val($('#deactivation_reason').val());
});

// Handle investment deletion
function confirmDelete(investmentId) {
    $('#deleteInvestmentForm').attr('action', '{{ route("admin.investments.destroy", ":id") }}'.replace(':id', investmentId));
    $('#deleteInvestmentModal').modal('show');
}
</script>
@endpush
@endsection