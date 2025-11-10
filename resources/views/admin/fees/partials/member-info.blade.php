<div class="member-info">
    <div class="d-flex align-items-center mb-3">
        @if($member->photo)
            <img src="{{ asset('storage/' . $member->photo) }}" alt="Member Photo" class="rounded-circle me-3" width="60" height="60">
        @else
            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                <i class="mdi mdi-account text-white font-size-24"></i>
            </div>
        @endif
        <div>
            <h6 class="mb-1">{{ $member->fname }} {{ $member->lname }}</h6>
            <small class="text-muted">{{ $member->code }}</small>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm">
            <tr>
                <td><strong>Branch:</strong></td>
                <td>{{ $member->branch->name ?? 'Not assigned' }}</td>
            </tr>
            <tr>
                <td><strong>Member Type:</strong></td>
                <td>{{ $member->memberType->name ?? 'Not set' }}</td>
            </tr>
            <tr>
                <td><strong>Contact:</strong></td>
                <td>{{ $member->contact }}</td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    @if($member->verified)
                        <span class="badge bg-success">Verified</span>
                    @else
                        <span class="badge bg-warning">Pending</span>
                    @endif
                </td>
            </tr>
            @if($member->group)
            <tr>
                <td><strong>Group:</strong></td>
                <td>{{ $member->group->name }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Joined:</strong></td>
                <td>{{ $member->datecreated ? $member->datecreated->format('M d, Y') : 'N/A' }}</td>
            </tr>
        </table>
    </div>
    
    <div class="row">
        <div class="col-6">
            <div class="card bg-light border-0">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Total Loans</small>
                    <h6 class="mb-0">{{ $member->loans->count() }}</h6>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-light border-0">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Total Fees</small>
                    <h6 class="mb-0">{{ $member->fees->count() }}</h6>
                </div>
            </div>
        </div>
    </div>
</div>