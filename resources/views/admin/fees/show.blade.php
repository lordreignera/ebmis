@extends('layouts.admin')

@section('title', 'Fee Payment Details')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Fee Payment Details</h4>
                
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.fees.index') }}">Fee Payments</a></li>
                        <li class="breadcrumb-item active">Payment Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Payment Information</h4>
                    <div>
                        @if($fee->status == 0)
                            <span class="badge bg-warning">Pending</span>
                        @else
                            <span class="badge bg-success">Paid</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Member</label>
                                <div class="fw-medium">{{ $fee->member->code }} - {{ $fee->member->fname }} {{ $fee->member->lname }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Fee Type</label>
                                <div class="fw-medium">{{ $fee->feeType->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Amount</label>
                                <div class="fw-medium text-primary fs-5">UGX {{ number_format($fee->amount) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Payment Type</label>
                                <div class="fw-medium">
                                    @if($fee->payment_type == 1)
                                        <i class="fas fa-money-bill-wave text-success"></i> Cash
                                    @elseif($fee->payment_type == 2)
                                        <i class="fas fa-mobile-alt text-info"></i> Mobile Money
                                    @elseif($fee->payment_type == 3)
                                        <i class="fas fa-university text-primary"></i> Bank Transfer
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($fee->loan)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Related Loan</label>
                                <div class="fw-medium">
                                    <a href="{{ route('admin.loans.show', $fee->loan) }}" class="text-decoration-none">
                                        Loan #{{ $fee->loan->id }} - UGX {{ number_format($fee->loan->amount) }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($fee->description)
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label text-muted">Description</label>
                                <div class="fw-medium">{{ $fee->description }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Payment Status</label>
                                <div class="fw-medium">{{ $fee->payment_status ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Date Created</label>
                                <div class="fw-medium">{{ $fee->created_at ? $fee->created_at->format('d M Y, H:i') : ($fee->datecreated ? $fee->datecreated->format('d M Y, H:i') : 'N/A') }}</div>
                            </div>
                        </div>
                        @if($fee->payment_description)
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label text-muted">Payment Description</label>
                                <div class="fw-medium">{{ $fee->payment_description }}</div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($fee->status == 0 && $fee->payment_type == 2)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Mobile Money Payment Initiated:</strong> USSD prompt has been sent to member's phone ({{ $fee->member->mobile_no }}). Payment is pending confirmation.
                    </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.fees.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Fees
                        </a>
                        <div>
                            @if($fee->status == 0)
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                                    <i class="fas fa-check"></i> Mark as Paid
                                </button>
                            @endif
                            <button class="btn btn-primary receipt-btn" data-fee-id="{{ $fee->id }}">
                                <i class="fas fa-receipt"></i> View Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Member Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Member Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="avatar-lg bg-primary bg-soft rounded">
                                <div class="avatar-title rounded bg-primary bg-soft text-primary">
                                    {{ strtoupper(substr($fee->member->fname, 0, 1) . substr($fee->member->lname, 0, 1)) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $fee->member->fname }} {{ $fee->member->lname }}</h5>
                            <p class="text-muted mb-0">{{ $fee->member->code }}</p>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted">Mobile</small>
                            <div class="fw-medium">{{ $fee->member->mobile_no }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Email</small>
                            <div class="fw-medium">{{ $fee->member->email ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Status</small>
                            <div class="fw-medium">
                                @if($fee->member->status == 1)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-warning">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Member Since</small>
                            <div class="fw-medium">{{ $fee->member->created_at ? $fee->member->created_at->format('M Y') : ($fee->member->datecreated ? $fee->member->datecreated->format('M Y') : 'N/A') }}</div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('admin.members.show', $fee->member) }}" class="btn btn-sm btn-outline-primary">
                            View Full Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    @php
                        // Use the model's legacy timestamp ordering
                        $recentFees = $fee->member->fees()->with('feeType')->orderByLegacyTimestamp()->limit(5)->get();
                    @endphp
                    
                    @if($recentFees->count() > 0)
                        @foreach($recentFees as $recentFee)
                        <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'border-bottom pb-2 mb-2' : '' }}">
                            <div>
                                <div class="fw-medium">{{ $recentFee->feeType->name }}</div>
                                <small class="text-muted">{{ $recentFee->created_at ? $recentFee->created_at->format('d M Y') : ($recentFee->datecreated ? $recentFee->datecreated->format('d M Y') : 'N/A') }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-medium">UGX {{ number_format($recentFee->amount) }}</div>
                                @if($recentFee->status == 1)
                                    <span class="badge bg-success-subtle text-success">Paid</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Pending</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No recent payments found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
@if($fee->status == 0)
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.fees.mark-paid', $fee) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="markPaidModalLabel">Mark Payment as Paid</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="payment_type_confirm" class="form-label">Payment Type</label>
                        <select name="payment_type" id="payment_type_confirm" class="form-select" required>
                            <option value="1" {{ $fee->payment_type == 1 ? 'selected' : '' }}>Cash</option>
                            <option value="2" {{ $fee->payment_type == 2 ? 'selected' : '' }}>Mobile Money</option>
                            <option value="3" {{ $fee->payment_type == 3 ? 'selected' : '' }}>Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_description_confirm" class="form-label">Payment Description</label>
                        <textarea name="payment_description" id="payment_description_confirm" class="form-control" rows="3" 
                                  placeholder="Enter payment confirmation details">{{ $fee->payment_description }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: white; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="receiptModalLabel" style="color: #000;">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptModalBody" style="background-color: white;">
                <!-- Receipt content will be loaded here -->
            </div>
            <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="mdi mdi-printer"></i> Print
                </button>
                <button type="button" class="btn btn-success" onclick="downloadReceipt()">
                    <i class="mdi mdi-download"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Receipt modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const receiptButtons = document.querySelectorAll('.receipt-btn');
    
    receiptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const feeId = this.getAttribute('data-fee-id');
            showReceiptModal(feeId);
        });
    });
});

function showReceiptModal(feeId) {
    // Show loading state
    const modalBody = document.getElementById('receiptModalBody');
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    modal.show();
    
    // Fetch receipt content
    fetch(`/admin/fees/${feeId}/receipt-modal`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load receipt</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading receipt</div>';
            console.error('Error:', error);
        });
}

function printReceipt() {
    window.print();
}

function downloadReceipt() {
    // Create a new window for printing/downloading
    const receiptContent = document.querySelector('.receipt-modal-content').innerHTML;
    const newWindow = window.open('', '', 'width=800,height=600');
    newWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            ${receiptContent}
        </body>
        </html>
    `);
    newWindow.document.close();
    newWindow.focus();
    newWindow.print();
    newWindow.close();
}
</script>
@endpush
@endif
@endsection