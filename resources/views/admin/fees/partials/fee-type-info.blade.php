<div class="fee-type-info">
    <div class="d-flex align-items-center mb-3">
        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
            <i class="mdi mdi-credit-card text-white font-size-20"></i>
        </div>
        <div>
            <h6 class="mb-1">{{ $feeType->name }}</h6>
            <small class="text-muted">Fee Type</small>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm">
            <tr>
                <td><strong>Type:</strong></td>
                <td>
                    @if($feeType->required_disbursement)
                        <span class="badge bg-warning">Upfront Charge</span>
                    @else
                        <span class="badge bg-primary">Mandatory Fee</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Account:</strong></td>
                <td>{{ $feeType->account }}</td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    @if($feeType->isactive)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Added:</strong></td>
                <td>{{ $feeType->datecreated ? $feeType->datecreated->format('M d, Y') : 'N/A' }}</td>
            </tr>
        </table>
    </div>
    
    @if($feeType->required_disbursement)
        <div class="alert alert-info">
            <small><i class="mdi mdi-information"></i> This fee is collected during loan disbursement.</small>
        </div>
    @else
        <div class="alert alert-warning">
            <small><i class="mdi mdi-alert"></i> This is a mandatory fee that must be paid.</small>
        </div>
    @endif
    
    <div class="row">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body p-2 text-center">
                    <small class="text-muted">Total Payments</small>
                    <h6 class="mb-0">{{ $feeType->fees->count() }}</h6>
                </div>
            </div>
        </div>
    </div>
</div>