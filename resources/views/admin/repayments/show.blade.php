@extends('layouts.admin')

@section('title', 'Repayment Details')

@push('css')
<style>
    .repayment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }
    
    .detail-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0;
    }
    
    .detail-value {
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 0;
        text-align: right;
    }
    
    .status-badge {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .payment-method-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
    
    .action-buttons {
        gap: 0.5rem;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        padding-bottom: 1.5rem;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }
    
    .timeline-item:last-child::before {
        display: none;
    }
    
    .timeline-dot {
        position: absolute;
        left: 0;
        top: 0.5rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: white;
        border: 2px solid #48bb78;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="card repayment-header mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="h3 mb-1 text-white">Repayment Details</h1>
                    <p class="text-white-50 mb-0">Transaction ID: {{ $repayment->txn_id ?? 'N/A' }}</p>
                </div>
                <div class="d-flex action-buttons">
                    <a href="{{ route('admin.repayments.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    @if($repayment->status == 0 && $repayment->created_at->diffInHours(now()) <= 24)
                        <a href="{{ route('admin.repayments.edit', $repayment) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                    @endif
                    <button onclick="printReceipt()" class="btn btn-light btn-sm">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    @if($repayment->status == 0 && $repayment->created_at->diffInHours(now()) <= 24)
                        <button onclick="deleteRepayment()" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    @endif
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        @php
                            $methodData = match($repayment->type) {
                                1 => ['icon' => 'fas fa-money-bill', 'bg' => 'bg-success', 'name' => 'Cash'],
                                2 => ['icon' => 'fas fa-mobile-alt', 'bg' => 'bg-warning', 'name' => 'Mobile Money'],
                                3 => ['icon' => 'fas fa-university', 'bg' => 'bg-info', 'name' => 'Bank Transfer'],
                                default => ['icon' => 'fas fa-question', 'bg' => 'bg-secondary', 'name' => 'Unknown']
                            };
                        @endphp
                        <div class="payment-method-icon {{ $methodData['bg'] }}">
                            <i class="{{ $methodData['icon'] }} text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-white mb-0">UGX {{ number_format($repayment->amount) }}</h4>
                            <p class="text-white-50 mb-0">via {{ $methodData['name'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    @if($repayment->status == 1)
                        <span class="status-badge bg-success text-white">
                            <i class="fas fa-check-circle me-1"></i>Confirmed
                        </span>
                    @else
                        <span class="status-badge bg-warning text-dark">
                            <i class="fas fa-clock me-1"></i>Pending
                        </span>
                    @endif
                    <div class="text-white-50 mt-2">
                        {{ $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('M j, Y g:i A') : 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Payment Information -->
        <div class="col-lg-6 mb-4">
            <div class="card detail-card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="detail-item">
                        <div class="detail-label">Amount Paid</div>
                        <div class="detail-value text-success">UGX {{ number_format($repayment->amount) }}</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Payment Method</div>
                        <div class="detail-value">{{ $methodData['name'] }}</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Transaction Reference</div>
                        <div class="detail-value">{{ $repayment->txn_id ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Payment Date</div>
                        <div class="detail-value">
                            {{ $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('M j, Y') : 'N/A' }}
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            @if($repayment->status == 1)
                                <span class="badge bg-success">Confirmed</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </div>
                    </div>
                    
                    @if($repayment->details)
                    <div class="detail-item">
                        <div class="detail-label">Description</div>
                        <div class="detail-value">{{ $repayment->details }}</div>
                    </div>
                    @endif
                    
                    @if($repayment->pay_message)
                    <div class="detail-item">
                        <div class="detail-label">Payment Message</div>
                        <div class="detail-value">{{ $repayment->pay_message }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Loan Information -->
        <div class="col-lg-6 mb-4">
            <div class="card detail-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-contract me-2"></i>
                        Loan Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="detail-item">
                        <div class="detail-label">Loan Code</div>
                        <div class="detail-value">
                            <a href="{{ route('admin.loans.show', $repayment->loan_id) }}" class="text-decoration-none">
                                {{ $repayment->loan->code ?? 'N/A' }}
                            </a>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Member</div>
                        <div class="detail-value">
                            @if($repayment->loan && $repayment->loan->member)
                                <a href="{{ route('admin.members.show', $repayment->loan->member->id) }}" class="text-decoration-none">
                                    {{ $repayment->loan->member->fname }} {{ $repayment->loan->member->lname }}
                                </a>
                                <br><small class="text-muted">{{ $repayment->loan->member->code }}</small>
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                    
                    @if($repayment->loan)
                    <div class="detail-item">
                        <div class="detail-label">Principal Amount</div>
                        <div class="detail-value">UGX {{ number_format($repayment->loan->principal) }}</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Total Paid</div>
                        <div class="detail-value text-success">UGX {{ number_format($repayment->loan->paid) }}</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Outstanding Balance</div>
                        <div class="detail-value text-warning">UGX {{ number_format($repayment->loan->outstanding_balance) }}</div>
                    </div>
                    @endif
                    
                    @if($repayment->schedule_id)
                    <div class="detail-item">
                        <div class="detail-label">Schedule ID</div>
                        <div class="detail-value">#{{ $repayment->schedule_id }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Information -->
    <div class="row">
        <div class="col-12">
            <div class="card detail-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Processing Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Added By</div>
                                <div class="detail-value">{{ $repayment->addedBy->name ?? 'System' }}</div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Platform</div>
                                <div class="detail-value">{{ $repayment->platform ?? 'Web' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Created Date</div>
                                <div class="detail-value">
                                    {{ $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('M j, Y g:i A') : 'N/A' }}
                                </div>
                            </div>
                            
                            @if($repayment->pay_status)
                            <div class="detail-item">
                                <div class="detail-label">Payment Status</div>
                                <div class="detail-value">{{ $repayment->pay_status }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($repayment->raw_message)
                    <div class="mt-3">
                        <h6 class="text-muted">Raw Message:</h6>
                        <div class="bg-light p-3 rounded">
                            <code>{{ $repayment->raw_message }}</code>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this repayment record?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This action cannot be undone and will affect the loan balance.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('admin.repayments.destroy', $repayment) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Repayment</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function printReceipt() {
    window.open('{{ route('admin.repayments.receipt', $repayment) }}', '_blank', 'width=800,height=600');
}

function deleteRepayment() {
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
@endpush
@endsection