@extends('layouts.admin')

@section('title', 'Cash Security Receipt')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <!-- Receipt Header -->
                    <div class="receipt-header text-center py-4 bg-success text-white">
                        <h3 class="mb-1">EBIMS Cash Security Receipt</h3>
                        <p class="mb-0">Cash Security Payment Confirmation</p>
                    </div>

                    <!-- Receipt Body -->
                    <div class="p-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <h6 class="text-muted mb-2">Receipt Details</h6>
                                <p class="mb-1"><strong>Receipt No:</strong> CS-{{ str_pad($cashSecurity->id, 6, '0', STR_PAD_LEFT) }}</p>
                                <p class="mb-1"><strong>Date:</strong> {{ $cashSecurity->created_at->format('d F Y') }}</p>
                                <p class="mb-0"><strong>Time:</strong> {{ $cashSecurity->created_at->format('H:i:s') }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <h6 class="text-muted mb-2">Payment Status</h6>
                                @if($cashSecurity->status == 1)
                                    <span class="badge bg-success fs-6 px-3 py-2">PAID</span>
                                @elseif($cashSecurity->status == 2)
                                    <span class="badge bg-danger fs-6 px-3 py-2">FAILED</span>
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
                                            <p class="mb-1"><strong>Name:</strong> {{ $cashSecurity->member->fname }} {{ $cashSecurity->member->lname }}</p>
                                            <p class="mb-0"><strong>Member Code:</strong> {{ $cashSecurity->member->code }}</p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Mobile:</strong> {{ $cashSecurity->member->contact ?? 'N/A' }}</p>
                                            <p class="mb-0"><strong>Email:</strong> {{ $cashSecurity->member->email ?? 'N/A' }}</p>
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
                                            <td class="fw-medium" width="30%">Payment Type</td>
                                            <td>Cash Security</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Amount</td>
                                            <td class="fw-bold text-success fs-5">UGX {{ number_format($cashSecurity->amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Payment Method</td>
                                            <td>
                                                @if($cashSecurity->payment_type == 1)
                                                    <i class="mdi mdi-cellphone text-primary"></i> Mobile Money
                                                @elseif($cashSecurity->payment_type == 2)
                                                    <i class="mdi mdi-cash text-success"></i> Cash
                                                @elseif($cashSecurity->payment_type == 3)
                                                    <i class="mdi mdi-bank text-info"></i> Bank Transfer
                                                @endif
                                            </td>
                                        </tr>
                                        @if($cashSecurity->pay_ref)
                                        <tr>
                                            <td class="fw-medium">Payment Reference</td>
                                            <td>{{ $cashSecurity->pay_ref }}</td>
                                        </tr>
                                        @endif
                                        @if($cashSecurity->transaction_reference)
                                        <tr>
                                            <td class="fw-medium">Transaction Reference</td>
                                            <td>{{ $cashSecurity->transaction_reference }}</td>
                                        </tr>
                                        @endif
                                        @if($cashSecurity->loan)
                                        <tr>
                                            <td class="fw-medium">Related Loan</td>
                                            <td>Loan {{ $cashSecurity->loan->code }}</td>
                                        </tr>
                                        @endif
                                        @if($cashSecurity->description)
                                        <tr>
                                            <td class="fw-medium">Description</td>
                                            <td>{{ $cashSecurity->description }}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Additional Information</h6>
                            <div class="alert alert-info mb-0">
                                <i class="mdi mdi-information"></i>
                                <strong>Cash Security Note:</strong> This payment represents a refundable security deposit held by EBIMS. 
                                The cash security will be returned to the member upon completion of agreed terms and conditions.
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="text-muted small mb-2">
                                This is a computer-generated receipt and does not require a physical signature.
                            </p>
                            <p class="text-muted small mb-0">
                                For inquiries, please contact EBIMS Support<br>
                                Generated on: {{ now()->format('d F Y H:i:s') }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="text-center mt-4">
                            <button onclick="window.print()" class="btn btn-primary me-2">
                                <i class="mdi mdi-printer"></i> Print Receipt
                            </button>
                            <a href="{{ route('admin.members.show', $cashSecurity->member) }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i> Back to Member Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .navbar, .sidebar, .breadcrumb {
            display: none !important;
        }
        .card {
            border: none;
            box-shadow: none;
        }
    }
</style>
@endsection
