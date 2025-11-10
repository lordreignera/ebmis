@extends('layouts.admin')

@section('title', 'Fee Payment Receipt')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <!-- Receipt Header -->
                    <div class="receipt-header text-center py-4 bg-primary text-white">
                        <h3 class="mb-1">EBIMS Payment Receipt</h3>
                        <p class="mb-0">Fee Payment Confirmation</p>
                    </div>

                    <!-- Receipt Body -->
                    <div class="p-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <h6 class="text-muted mb-2">Receipt Details</h6>
                                <p class="mb-1"><strong>Receipt No:</strong> RCP-{{ str_pad($fee->id, 6, '0', STR_PAD_LEFT) }}</p>
                                <p class="mb-1"><strong>Date:</strong> {{ $fee->created_at ? $fee->created_at->format('d F Y') : ($fee->datecreated ? $fee->datecreated->format('d F Y') : 'N/A') }}</p>
                                <p class="mb-0"><strong>Time:</strong> {{ $fee->created_at ? $fee->created_at->format('H:i:s') : ($fee->datecreated ? $fee->datecreated->format('H:i:s') : 'N/A') }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <h6 class="text-muted mb-2">Payment Status</h6>
                                @if($fee->status == 1)
                                    <span class="badge bg-success fs-6 px-3 py-2">PAID</span>
                                @else
                                    <span class="badge bg-warning fs-6 px-3 py-2">PENDING</span>
                                @endif
                            </div>
                        </div>

                        <!-- Member Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted mb-2">Member Information</h6>
                                <div class="border rounded p-3 bg-light">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Name:</strong> {{ $fee->member->fname }} {{ $fee->member->lname }}</p>
                                            <p class="mb-0"><strong>Member Code:</strong> {{ $fee->member->code }}</p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Mobile:</strong> {{ $fee->member->contact ?? 'N/A' }}</p>
                                            <p class="mb-0"><strong>Email:</strong> {{ $fee->member->email ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Payment Details</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td class="fw-medium" width="30%">Fee Type</td>
                                            <td>{{ $fee->feeType->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Amount</td>
                                            <td class="fw-bold text-primary">UGX {{ number_format($fee->amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Payment Method</td>
                                            <td>
                                                @if($fee->payment_type == 1)
                                                    Cash Payment
                                                @elseif($fee->payment_type == 2)
                                                    Mobile Money
                                                @elseif($fee->payment_type == 3)
                                                    Bank Transfer
                                                @endif
                                            </td>
                                        </tr>
                                        @if($fee->pay_ref)
                                        <tr>
                                            <td class="fw-medium">Payment Reference</td>
                                            <td>{{ $fee->pay_ref }}</td>
                                        </tr>
                                        @endif
                                        @if($fee->loan)
                                        <tr>
                                            <td class="fw-medium">Related Loan</td>
                                            <td>Loan #{{ $fee->loan->id }} - UGX {{ number_format($fee->loan->amount) }}</td>
                                        </tr>
                                        @endif
                                        @if($fee->description)
                                        <tr>
                                            <td class="fw-medium">Description</td>
                                            <td>{{ $fee->description }}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Amount Summary -->
                        <div class="row mb-4">
                            <div class="col-md-6 ms-auto">
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr class="border-top">
                                                <td class="fw-bold fs-5">Total Amount Paid:</td>
                                                <td class="text-end fw-bold fs-5 text-primary">UGX {{ number_format($fee->amount) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Receipt Footer -->
                        <div class="border-top pt-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <strong>Processed by:</strong> {{ $fee->addedBy->name ?? 'System' }}<br>
                                        <strong>Date:</strong> {{ $fee->created_at ? $fee->created_at->format('d F Y, H:i:s') : ($fee->datecreated ? $fee->datecreated->format('d F Y, H:i:s') : 'N/A') }}
                                    </small>
                                </div>
                                <div class="col-6 text-end">
                                    <small class="text-muted">
                                        This is a computer-generated receipt.<br>
                                        No signature required.
                                    </small>
                                </div>
                            </div>
                        </div>

                        @if($fee->status == 0)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> This payment is still pending confirmation. 
                            @if($fee->payment_type == 2)
                                The member should complete the mobile money transaction on their phone.
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-3 d-print-none">
                <a href="{{ route('admin.fees.show', $fee) }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Back to Details
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
@media print {
    .d-print-none {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .receipt-header {
        background-color: #0052cc !important;
        -webkit-print-color-adjust: exact;
    }
}

.receipt-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
@endsection