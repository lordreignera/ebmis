<!-- E-Signature Form Content -->
<h5 class="mb-3"><i class="mdi mdi-draw me-2"></i>Electronic Loan Agreement Signature</h5>
<p class="text-muted">Complete the loan agreement details and add electronic signatures without printing.</p>

@if($loan->agreement_finalized_at)
<div class="alert alert-success">
    <i class="mdi mdi-check-circle me-1"></i>
    <strong>Agreement Finalized!</strong> This loan agreement was electronically signed and finalized on {{ \Carbon\Carbon::parse($loan->agreement_finalized_at)->format('M d, Y \a\t h:i A') }}.
    @if($loan->signed_agreement_path)
    <br><a href="{{ \App\Services\FileStorageService::getFileUrl($loan->signed_agreement_path) }}" target="_blank" class="btn btn-sm btn-success mt-2">
        <i class="mdi mdi-file-pdf-box me-1"></i> View Signed Agreement
    </a>
    <button type="button" class="btn btn-sm btn-warning mt-2" onclick="regenerateAgreement()">
        <i class="mdi mdi-refresh me-1"></i> Regenerate Agreement (Reflects Current Data)
    </button>
    @endif
</div>
@endif

<form id="eSignatureForm" method="POST" action="{{ route('admin.loans.save-esignature', $loan->id) }}" enctype="multipart/form-data" {{ $loan->agreement_finalized_at ? 'onsubmit="return false;"' : '' }}>
    @csrf
    <input type="hidden" name="loan_type" value="{{ $loanType }}">
    @php
        $cashAccountNumber = $loan->cash_account_number;
        $cashAccountName = $loan->cash_account_name;
        $cashSecurityAmount = null;

        if ($loanType === 'personal' && $loan->member) {
            $cashAccountNumber = $loan->member->cash_security_account_number ?: $loan->cash_account_number;
            $cashAccountName = trim(($loan->member->fname ?? '') . ' ' . ($loan->member->mname ?? '') . ' ' . ($loan->member->lname ?? ''));

            $cashSecurityAmount = \App\Models\CashSecurity::where('member_id', $loan->member->id)
                ->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('returned')->orWhere('returned', 0);
                })
                ->sum('amount');
        }
    @endphp

    <!-- Step 1: Loan Purpose & Collateral -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="mdi mdi-numeric-1-circle me-1"></i> Loan Purpose & Collateral Details</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="loan_purpose" class="form-label">Loan Purpose <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="loan_purpose" name="loan_purpose" rows="2" 
                        placeholder="e.g., Investing in retail business, agricultural inputs, school fees payment..." 
                        {{ $loan->agreement_finalized_at ? 'readonly' : 'required' }}>{{ $loan->loan_purpose }}</textarea>
                    <small class="text-muted">Describe what the loan will be used for</small>
                </div>
            </div>

            <h6 class="mt-3 mb-3">Cash Security Account</h6>
            <input type="hidden" name="cash_account_number" value="{{ $cashAccountNumber }}">
            <input type="hidden" name="cash_account_name" value="{{ $cashAccountName }}">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cash_account_number" class="form-label">Account Number</label>
                    <input type="text" class="form-control" id="cash_account_number" 
                        value="{{ $cashAccountNumber }}" 
                        placeholder="Cash security account number"
                        readonly>
                    <small class="text-muted">Auto-fetched from member profile cash security account</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cash_account_name" class="form-label">Account Name</label>
                    <input type="text" class="form-control" id="cash_account_name" 
                        value="{{ $cashAccountName }}" 
                        placeholder="Name on the account"
                        readonly>
                    <small class="text-muted">Auto-fetched from borrower details</small>
                </div>
                @if($loanType === 'personal')
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current Cash Security Amount</label>
                    <input type="text" class="form-control" value="UGX {{ number_format($cashSecurityAmount ?? 0, 2) }}" readonly>
                    <small class="text-muted">Sum of paid, not-yet-returned cash security for this borrower</small>
                </div>
                @endif
            </div>

            <h6 class="mt-3 mb-3">Additional Collateral Security (Not captured in system)</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="immovable_assets" class="form-label">Immovable Assets</label>
                    <textarea class="form-control" id="immovable_assets" name="immovable_assets" rows="2" 
                        placeholder="e.g., Land titles, property deeds..." 
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>{{ $loan->immovable_assets }}</textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="moveable_assets" class="form-label">Moveable Assets</label>
                    <textarea class="form-control" id="moveable_assets" name="moveable_assets" rows="2" 
                        placeholder="e.g., Motorcycle, vehicles, furniture..." 
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>{{ $loan->moveable_assets }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="intellectual_property" class="form-label">Intellectual Property</label>
                    <input type="text" class="form-control" id="intellectual_property" name="intellectual_property" 
                        placeholder="Patents, trademarks..." value="{{ $loan->intellectual_property }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="stocks_collateral" class="form-label">Stocks</label>
                    <input type="text" class="form-control" id="stocks_collateral" name="stocks_collateral" 
                        placeholder="Business stocks, inventory..." value="{{ $loan->stocks_collateral }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="livestock_collateral" class="form-label">Livestock</label>
                    <input type="text" class="form-control" id="livestock_collateral" name="livestock_collateral" 
                        placeholder="Cattle, goats, poultry..." value="{{ $loan->livestock_collateral }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
            </div>
        </div>
    </div>

    @if($loanType === 'group')
    <!-- Group-Specific Fields -->
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="mdi mdi-account-group me-1"></i> Group Details</h6>
        </div>
        <div class="card-body">
            <h6 class="mb-3">Group Banker Information</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="group_banker_name" class="form-label">Group Banker Name</label>
                    <input type="text" class="form-control" id="group_banker_name" name="group_banker_name" 
                        placeholder="Full name of elected group banker" value="{{ $loan->group_banker_name }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="group_banker_nin" class="form-label">Group Banker NIN</label>
                    <input type="text" class="form-control" id="group_banker_nin" name="group_banker_nin" 
                        placeholder="CM..." value="{{ $loan->group_banker_nin }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="group_banker_occupation" class="form-label">Occupation</label>
                    <input type="text" class="form-control" id="group_banker_occupation" name="group_banker_occupation" 
                        placeholder="e.g., Farmer, Teacher..." value="{{ $loan->group_banker_occupation }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="group_banker_residence" class="form-label">Residence</label>
                    <input type="text" class="form-control" id="group_banker_residence" name="group_banker_residence" 
                        placeholder="Village, Parish, Subcounty" value="{{ $loan->group_banker_residence }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
            </div>

            <h6 class="mt-3 mb-3">Group Representative</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="group_representative_name" class="form-label">Representative Name</label>
                    <input type="text" class="form-control" id="group_representative_name" name="group_representative_name" 
                        placeholder="Group representative name" value="{{ $loan->group_representative_name }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="group_representative_phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="group_representative_phone" name="group_representative_phone" 
                        placeholder="0700000000" value="{{ $loan->group_representative_phone }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Step 2: Witness Information -->
    <div class="card mb-3">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0"><i class="mdi mdi-numeric-2-circle me-1"></i> Witness Information</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="witness_name" class="form-label">Witness Name</label>
                    <input type="text" class="form-control" id="witness_name" name="witness_name" 
                        placeholder="Full name of witness" value="{{ $loan->witness_name }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="witness_nin" class="form-label">Witness NIN</label>
                    <input type="text" class="form-control" id="witness_nin" name="witness_nin" 
                        placeholder="CM..." value="{{ $loan->witness_nin }}"
                        {{ $loan->agreement_finalized_at ? 'readonly' : '' }}>
                </div>
            </div>

            <h6 class="mt-3 mb-3">Witness Signature</h6>
            @if($loan->witness_signature)
            <div class="alert alert-success">
                <i class="mdi mdi-check-circle me-1"></i> Witness has signed this agreement
                <br><small>Signed on: {{ \Carbon\Carbon::parse($loan->witness_signature_date)->format('M d, Y \a\t h:i A') }}</small>
            </div>
            <div class="text-center mb-3">
                @if($loan->witness_signature_type === 'uploaded')
                <img src="{{ \App\Services\FileStorageService::getFileUrl($loan->witness_signature) }}" alt="Witness Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;">
                @else
                <img src="{{ $loan->witness_signature }}" alt="Witness Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;">
                @endif
            </div>
            @else
            <p class="text-muted">The witness can sign the agreement using one of the following methods:</p>
            
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#witness-draw" role="tab">
                        <i class="mdi mdi-draw me-1"></i> Draw Signature
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#witness-upload" role="tab">
                        <i class="mdi mdi-upload me-1"></i> Upload Signature
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="witness-draw" role="tabpanel">
                    <div class="signature-pad-container mb-3">
                        <canvas id="witnessSignaturePad" class="signature-pad"></canvas>
                        <input type="hidden" name="witness_signature_data" id="witnessSignatureData">
                        <input type="hidden" name="witness_signature_type" value="drawn">
                    </div>
                    <button type="button" class="btn btn-sm btn-warning" onclick="clearWitnessSignature()">
                        <i class="mdi mdi-eraser me-1"></i> Clear
                    </button>
                </div>
                <div class="tab-pane" id="witness-upload" role="tabpanel">
                    <input type="file" class="form-control" name="witness_signature_file" accept="image/*">
                    <small class="text-muted">Upload a scanned signature image (PNG, JPG)</small>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Step 3: Borrower Signature -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="mdi mdi-numeric-3-circle me-1"></i> Borrower Signature</h6>
        </div>
        <div class="card-body">
            @if($loan->borrower_signature)
            <div class="alert alert-success">
                <i class="mdi mdi-check-circle me-1"></i> Borrower has signed this agreement
                <br><small>Signed on: {{ \Carbon\Carbon::parse($loan->borrower_signature_date)->format('M d, Y \a\t h:i A') }}</small>
            </div>
            <div class="text-center mb-3">
                @if($loan->borrower_signature_type === 'uploaded')
                <img src="{{ \App\Services\FileStorageService::getFileUrl($loan->borrower_signature) }}" alt="Borrower Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;">
                @else
                <img src="{{ $loan->borrower_signature }}" alt="Borrower Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;">
                @endif
            </div>
            @else
            <p class="text-muted">The borrower can sign the agreement using one of the following methods:</p>
            
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#borrower-draw" role="tab">
                        <i class="mdi mdi-draw me-1"></i> Draw Signature
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#borrower-upload" role="tab">
                        <i class="mdi mdi-upload me-1"></i> Upload Signature
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="borrower-draw" role="tabpanel">
                    <div class="signature-pad-container mb-3">
                        <canvas id="borrowerSignaturePad" class="signature-pad"></canvas>
                        <input type="hidden" name="borrower_signature_data" id="borrowerSignatureData">
                        <input type="hidden" name="borrower_signature_type" value="drawn">
                    </div>
                    <button type="button" class="btn btn-sm btn-warning" onclick="clearBorrowerSignature()">
                        <i class="mdi mdi-eraser me-1"></i> Clear
                    </button>
                </div>
                <div class="tab-pane" id="borrower-upload" role="tabpanel">
                    <input type="file" class="form-control" name="borrower_signature_file" accept="image/*">
                    <small class="text-muted">Upload a scanned signature image (PNG, JPG)</small>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Step 4: Lender Signature -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="mdi mdi-numeric-4-circle me-1"></i> Lender Signature</h6>
        </div>
        <div class="card-body">
            @if($loan->lender_signature)
            <div class="alert alert-info">
                <i class="mdi mdi-check-circle me-1"></i> Lender representative has signed this agreement
                <br><small>Signed on: {{ \Carbon\Carbon::parse($loan->lender_signature_date)->format('M d, Y \a\t h:i A') }}</small>
                <br><small>Title: {{ $loan->lender_title ?? 'Branch Manager' }}</small>
            </div>
            <div class="text-center mb-3">
                @if($loan->lender_signature_type === 'uploaded')
                <img src="{{ \App\Services\FileStorageService::getFileUrl($loan->lender_signature) }}" alt="Lender Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;">
                @else
                <img src="{{ $loan->lender_signature }}" alt="Lender Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;">
                @endif
            </div>
            @else
            <div class="mb-3">
                <label for="lender_title" class="form-label">Lender Title</label>
                <input type="text" class="form-control" id="lender_title" name="lender_title" 
                    value="Branch Manager" placeholder="e.g., Branch Manager, Loan Officer">
            </div>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#lender-draw" role="tab">
                        <i class="mdi mdi-draw me-1"></i> Draw Signature
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#lender-upload" role="tab">
                        <i class="mdi mdi-upload me-1"></i> Upload Signature
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="lender-draw" role="tabpanel">
                    <div class="signature-pad-container mb-3">
                        <canvas id="lenderSignaturePad" class="signature-pad"></canvas>
                        <input type="hidden" name="lender_signature_data" id="lenderSignatureData">
                        <input type="hidden" name="lender_signature_type" value="drawn">
                    </div>
                    <button type="button" class="btn btn-sm btn-warning" onclick="clearLenderSignature()">
                        <i class="mdi mdi-eraser me-1"></i> Clear
                    </button>
                </div>
                <div class="tab-pane" id="lender-upload" role="tabpanel">
                    <input type="file" class="form-control" name="lender_signature_file" accept="image/*">
                    <small class="text-muted">Upload a scanned signature image (PNG, JPG)</small>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Submit Button -->
    @if(!$loan->agreement_finalized_at)
    <div class="text-end">
        <button type="button" class="btn btn-secondary me-2" onclick="saveDraft()">
            <i class="mdi mdi-content-save me-1"></i> Save Draft
        </button>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="mdi mdi-check-circle me-1"></i> Finalize & Generate Signed Agreement
        </button>
    </div>
    @endif
</form>

<style>
.signature-pad-container {
    border: 2px solid #ddd;
    border-radius: 8px;
    background: #f8f9fa;
    padding: 10px;
    display: block;
    width: 100%;
}

.signature-pad {
    display: block;
    width: 100%;
    height: 200px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
    cursor: crosshair;
    touch-action: none;
}
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
let borrowerSignaturePad, lenderSignaturePad, witnessSignaturePad;

function resizeCanvas(canvas, signaturePad) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    
    const ctx = canvas.getContext("2d");
    ctx.scale(ratio, ratio);
    
    // Clear the signature pad after resize
    if (signaturePad) {
        signaturePad.clear();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for tabs to render
    setTimeout(function() {
        // Initialize Borrower Signature Pad
        const borrowerCanvas = document.getElementById('borrowerSignaturePad');
        if (borrowerCanvas) {
            resizeCanvas(borrowerCanvas);
            
            borrowerSignaturePad = new SignaturePad(borrowerCanvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 1,
                maxWidth: 2.5,
                velocityFilterWeight: 0.7
            });
        }

        // Initialize Lender Signature Pad
        const lenderCanvas = document.getElementById('lenderSignaturePad');
        if (lenderCanvas) {
            resizeCanvas(lenderCanvas);
            
            lenderSignaturePad = new SignaturePad(lenderCanvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 1,
                maxWidth: 2.5,
                velocityFilterWeight: 0.7
            });
        }

        // Initialize Witness Signature Pad
        const witnessCanvas = document.getElementById('witnessSignaturePad');
        if (witnessCanvas) {
            resizeCanvas(witnessCanvas);
            
            witnessSignaturePad = new SignaturePad(witnessCanvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 1,
                maxWidth: 2.5,
                velocityFilterWeight: 0.7
            });
        }
    }, 300);

    // Reinitialize signature pads when tabs are shown
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(event) {
            if (event.target.getAttribute('href') === '#borrower-draw' && borrowerSignaturePad) {
                setTimeout(function() {
                    resizeCanvas(document.getElementById('borrowerSignaturePad'), borrowerSignaturePad);
                }, 100);
            }
            if (event.target.getAttribute('href') === '#lender-draw' && lenderSignaturePad) {
                setTimeout(function() {
                    resizeCanvas(document.getElementById('lenderSignaturePad'), lenderSignaturePad);
                }, 100);
            }
            if (event.target.getAttribute('href') === '#witness-draw' && witnessSignaturePad) {
                setTimeout(function() {
                    resizeCanvas(document.getElementById('witnessSignaturePad'), witnessSignaturePad);
                }, 100);
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (borrowerSignaturePad) {
            resizeCanvas(document.getElementById('borrowerSignaturePad'), borrowerSignaturePad);
        }
        if (lenderSignaturePad) {
            resizeCanvas(document.getElementById('lenderSignaturePad'), lenderSignaturePad);
        }
        if (witnessSignaturePad) {
            resizeCanvas(document.getElementById('witnessSignaturePad'), witnessSignaturePad);
        }
    });

    // Form submission
    const form = document.getElementById('eSignatureForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Save signature data before submission
            if (borrowerSignaturePad && !borrowerSignaturePad.isEmpty()) {
                document.getElementById('borrowerSignatureData').value = borrowerSignaturePad.toDataURL();
            }
            if (lenderSignaturePad && !lenderSignaturePad.isEmpty()) {
                document.getElementById('lenderSignatureData').value = lenderSignaturePad.toDataURL();
            }
            if (witnessSignaturePad && !witnessSignaturePad.isEmpty()) {
                document.getElementById('witnessSignatureData').value = witnessSignaturePad.toDataURL();
            }
        });
    }
});

function clearBorrowerSignature() {
    if (borrowerSignaturePad) {
        borrowerSignaturePad.clear();
    }
}

function clearLenderSignature() {
    if (lenderSignaturePad) {
        lenderSignaturePad.clear();
    }
}

function clearWitnessSignature() {
    if (witnessSignaturePad) {
        witnessSignaturePad.clear();
    }
}

function saveDraft() {
    const form = document.getElementById('eSignatureForm');
    const formData = new FormData(form);
    formData.append('save_draft', '1');

    // Save signature data
    if (borrowerSignaturePad && !borrowerSignaturePad.isEmpty()) {
        formData.set('borrower_signature_data', borrowerSignaturePad.toDataURL());
    }
    if (lenderSignaturePad && !lenderSignaturePad.isEmpty()) {
        formData.set('lender_signature_data', lenderSignaturePad.toDataURL());
    }
    if (witnessSignaturePad && !witnessSignaturePad.isEmpty()) {
        formData.set('witness_signature_data', witnessSignaturePad.toDataURL());
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Saved!', 'Draft saved successfully', 'success');
        } else {
            Swal.fire('Error!', data.message || 'Failed to save draft', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', 'An error occurred while saving', 'error');
    });
}

function regenerateAgreement() {
    Swal.fire({
        title: 'Regenerate Agreement?',
        text: 'This will create a new PDF with current guarantor and loan data. The existing agreement will be kept for records.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Regenerate',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Regenerating...',
                text: 'Please wait while we generate the new agreement',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route('admin.loans.regenerate-agreement', $loan->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    loan_type: '{{ $loanType }}'
                })
            })
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    // Try to get error message from response
                    return response.text().then(text => {
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.message || 'Server error');
                        } catch (e) {
                            // If not JSON, throw the status text
                            throw new Error(`Server error: ${response.status} ${response.statusText}`);
                        }
                    });
                }
                // Parse JSON response
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Agreement regenerated successfully',
                        icon: 'success',
                        confirmButtonText: 'View Updated Agreement'
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error!', data.message || 'Failed to regenerate agreement', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', error.message || 'An error occurred while regenerating the agreement', 'error');
            });
        }
    });
}
</script>
@endpush
