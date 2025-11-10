<div class="receipt-modal-content" style="background-color: white; padding: 15px;">
    
    <!-- Receipt Header -->
    <div class="text-center mb-3 p-2 bg-primary text-white rounded">
        <h5 class="mb-1">EBIMS Payment Receipt</h5>
        <small>Fee Payment Confirmation</small>
    </div>

    <!-- Receipt Content -->
    <div class="receipt-details" style="background-color: white;">
        
        <!-- Receipt & Status Row -->
        <div class="row mb-3">
            <div class="col-6">
                <div class="border rounded p-2" style="background-color: #f8f9fa;">
                    <h6 class="text-primary mb-2">Receipt Details</h6>
                    <p class="mb-1" style="color: #000;"><strong>Receipt No:</strong> <span class="text-success">RCP-{{ str_pad($fee->id, 6, '0', STR_PAD_LEFT) }}</span></p>
                    <p class="mb-1" style="color: #000;"><strong>Date:</strong> {{ $fee->created_at ? $fee->created_at->format('d M Y') : ($fee->datecreated ? $fee->datecreated->format('d M Y') : 'N/A') }}</p>
                    <p class="mb-0" style="color: #000;"><strong>Time:</strong> {{ $fee->created_at ? $fee->created_at->format('H:i') : ($fee->datecreated ? $fee->datecreated->format('H:i') : 'N/A') }}</p>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2 text-center" style="background-color: #f8f9fa;">
                    <h6 class="text-primary mb-2">Payment Status</h6>
                    @if($fee->status == 1)
                        <span class="badge bg-success">PAID</span>
                    @else
                        <span class="badge bg-warning">PENDING</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Member Information -->
        <div class="mb-3">
            <div class="border border-primary rounded" style="background-color: white;">
                <div class="bg-primary text-white p-2 rounded-top">
                    <h6 class="mb-0" style="color: white;">Member Information</h6>
                </div>
                <div class="p-2" style="background-color: white;">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1" style="color: #000;"><strong>Name:</strong> {{ $fee->member->fname }} {{ $fee->member->lname }}</p>
                            <p class="mb-0" style="color: #000;"><strong>Member Code:</strong> <span class="text-primary">{{ $fee->member->code }}</span></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-1" style="color: #000;"><strong>Mobile:</strong> {{ $fee->member->contact ?? 'N/A' }}</p>
                            <p class="mb-0" style="color: #000;"><strong>Email:</strong> {{ $fee->member->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="mb-3">
            <div class="border border-success rounded" style="background-color: white;">
                <div class="bg-success text-white p-2 rounded-top">
                    <h6 class="mb-0" style="color: white;">Payment Details</h6>
                </div>
                <div class="table-responsive" style="background-color: white;">
                    <table class="table table-sm mb-0" style="background-color: white;">
                        <tbody>
                            <tr style="background-color: white;">
                                <td class="fw-bold" style="width: 40%; color: #000;">Fee Type</td>
                                <td style="color: #000;">{{ $fee->feeType->name ?? 'N/A' }}</td>
                            </tr>
                            <tr class="table-warning">
                                <td class="fw-bold" style="color: #000;">Amount Paid</td>
                                <td class="fw-bold text-success">UGX {{ number_format($fee->amount, 2) }}</td>
                            </tr>
                            <tr style="background-color: white;">
                                <td class="fw-bold" style="color: #000;">Payment Method</td>
                                <td>
                                    @if($fee->payment_type == 1)
                                        <span class="badge bg-info">Cash</span>
                                    @elseif($fee->payment_type == 2)
                                        <span class="badge bg-warning">Mobile Money</span>
                                    @elseif($fee->payment_type == 3)
                                        <span class="badge bg-primary">Bank Transfer</span>
                                    @else
                                        <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </td>
                            </tr>
                            @if($fee->loan)
                            <tr>
                                <td class="fw-bold">Related Loan</td>
                                <td>Loan #{{ $fee->loan->id }} - <span class="text-success">UGX {{ number_format($fee->loan->amount) }}</span></td>
                            </tr>
                            @endif
                            @if($fee->description)
                            <tr>
                                <td class="fw-bold">Description</td>
                                <td>{{ $fee->description }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Receipt Footer -->
        <div class="text-center border-top pt-2">
            <small class="text-muted">
                <strong>Processed by:</strong> {{ $fee->addedBy->name ?? 'System' }} | 
                <strong>Date:</strong> {{ $fee->created_at ? $fee->created_at->format('d M Y, H:i') : ($fee->datecreated ? $fee->datecreated->format('d M Y, H:i') : 'N/A') }}
            </small>
            <br>
            <small class="text-muted">
                <em>Computer-generated receipt</em>
            </small>
        </div>
        
    </div>
</div>

<style>
.receipt-modal-content {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: white !important;
    color: #000 !important;
}

.receipt-modal-content * {
    color: #000 !important;
}

.receipt-modal-content .table td {
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    background-color: white !important;
    color: #000 !important;
}

.receipt-modal-content .text-primary {
    color: #007bff !important;
}

.receipt-modal-content .text-success {
    color: #28a745 !important;
}

@media print {
    .receipt-modal-content {
        margin: 0;
        box-shadow: none;
        font-size: 12pt;
        background-color: white !important;
        color: #000 !important;
    }
    
    .modal-header, .modal-footer {
        display: none !important;
    }
    
    body * {
        visibility: hidden;
    }
    
    .receipt-modal-content, .receipt-modal-content * {
        visibility: visible;
        color: #000 !important;
    }
    
    .receipt-modal-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        background-color: white !important;
    }
}
</style>