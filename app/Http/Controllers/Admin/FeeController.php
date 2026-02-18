<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\FeeType;
use App\Models\Member;
use App\Models\Loan;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FeeController extends Controller
{
    /**
     * Display a listing of fees
     */
    public function index(Request $request)
    {
        $query = Fee::with(['member', 'loan', 'feeType', 'addedBy']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('pay_ref', 'like', "%{$search}%")
                  ->orWhereHas('member', function($memberQuery) use ($search) {
                      $memberQuery->where('fname', 'like', "%{$search}%")
                                  ->orWhere('lname', 'like', "%{$search}%")
                                  ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by fee type
        if ($request->has('fee_type') && $request->fee_type) {
            $query->where('fees_type_id', $request->fee_type);
        }

        // Filter by payment status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by payment type
        if ($request->has('payment_type') && $request->payment_type !== '') {
            $query->where('payment_type', $request->payment_type);
        }

        $fees = $query->orderBy('datecreated', 'desc')->paginate(20);

        $feeTypes = FeeType::active()->get();

        // Calculate statistics
        $stats = [
            'total_fees' => Fee::count(),
            'paid_fees' => Fee::where('status', 1)->count(),
            'pending_fees' => Fee::where('status', 0)->count(),
            'total_amount' => Fee::where('status', 1)->sum('amount')
        ];

        return view('admin.fees.index', compact('fees', 'feeTypes', 'stats'));
    }

    /**
     * Show the form for creating a new fee payment
     */
    public function create(Request $request)
    {
        $members = Member::verified()->notDeleted()->get();
        $feeTypes = FeeType::active()->get();
        $loans = Loan::where('status', '!=', 3)->with(['member', 'product'])->get(); // Not closed loans

        // Pre-select member if passed
        $selectedMember = null;
        if ($request->has('member_id')) {
            $selectedMember = Member::find($request->member_id);
        }

        // Pre-select loan if passed
        $selectedLoan = null;
        if ($request->has('loan_id')) {
            $selectedLoan = Loan::with('member')->find($request->loan_id);
            $selectedMember = $selectedLoan->member;
        }

        return view('admin.fees.create', compact('members', 'feeTypes', 'loans', 'selectedMember', 'selectedLoan'));
    }

    /**
     * Store a newly created fee payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'loan_id' => 'nullable|exists:loans,id',
            'fees_type_id' => 'required|exists:fees_types,id',
            'payment_type' => 'required|integer|in:1,2,3', // 1=Cash, 2=Mobile Money, 3=Bank Transfer
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:100',
            'pay_ref' => 'nullable|string|max:100',
        ]);

        // Get member and fee type info
        $member = Member::find($validated['member_id']);
        $feeType = FeeType::find($validated['fees_type_id']);

        // Get member and fee type info
        $member = Member::find($validated['member_id']);
        $feeType = FeeType::find($validated['fees_type_id']);

        // Set default amount for registration fees
        if ($feeType->name === 'Registration fees' && $validated['amount'] <= 0) {
            $validated['amount'] = 25000; // Default registration fee amount
        }

        try {
            DB::beginTransaction();

            // Check if this is a mandatory fee that's already been paid
            if ($feeType->required_disbursement == 0) {
                // Mandatory fee - check if already paid
                $existingFee = Fee::where('member_id', $validated['member_id'])
                                ->where('fees_type_id', $validated['fees_type_id'])
                                ->where('status', 1)
                                ->first();

                if ($existingFee) {
                    return redirect()->back()
                                    ->withInput()
                                    ->with('error', "Mandatory fee '{$feeType->name}' has already been paid by this member.");
                }
            } else {
                // Upfront charge - requires loan_id
                if (empty($validated['loan_id'])) {
                    return redirect()->back()
                                    ->withInput()
                                    ->with('error', "Loan is required for upfront charge '{$feeType->name}'.");
                }

                // Check if already paid for this loan
                $existingFee = Fee::where('loan_id', $validated['loan_id'])
                                ->where('fees_type_id', $validated['fees_type_id'])
                                ->where('status', 1)
                                ->first();

                if ($existingFee) {
                    return redirect()->back()
                                    ->withInput()
                                    ->with('error', "Upfront charge '{$feeType->name}' has already been paid for this loan.");
                }
            }

            $validated['added_by'] = auth()->id();
            
            // Handle payment processing based on payment type
            if ($validated['payment_type'] == 2) { // Mobile Money
                // For mobile money, initiate USSD payment
                $validated['status'] = 0; // Pending payment
                $validated['payment_status'] = 'Pending';
                $validated['payment_description'] = 'Mobile Money payment initiated';
                
                $fee = Fee::create($validated);
                
                // Send USSD prompt to member's phone
                $this->initiateMobileMoneyPayment($member, $fee);
                
                DB::commit();
                
                return redirect()->route('admin.fees.show', $fee)
                                ->with('success', 'Mobile Money payment initiated. USSD prompt sent to member\'s phone: ' . $member->mobile_no);
                                
            } else { // Cash or Bank Transfer
                $validated['status'] = 1; // Paid
                $validated['payment_status'] = 'Paid';
                $validated['payment_description'] = 'Manual payment recorded';
                
                $fee = Fee::create($validated);
                
                // Post to General Ledger
                try {
                    $accountingService = new \App\Services\AccountingService();
                    $journal = $accountingService->postFeeCollectionEntry($fee, $validated['fee_type']);
                    if ($journal) {
                        \Log::info('Fee GL entry posted', [
                            'fee_id' => $fee->id,
                            'journal_number' => $journal->journal_number
                        ]);
                    }
                } catch (\Exception $glError) {
                    \Log::error('Fee GL posting failed', [
                        'fee_id' => $fee->id,
                        'error' => $glError->getMessage()
                    ]);
                    // Continue - don't fail fee recording if GL posting fails
                }
                
                DB::commit();
                
                return redirect()->route('admin.fees.show', $fee)
                                ->with('success', 'Fee payment recorded successfully.');
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error recording fee payment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified fee
     */
    public function show(Fee $fee)
    {
        $fee->load(['member', 'loan.product', 'feeType', 'addedBy']);

        return view('admin.fees.show', compact('fee'));
    }

    /**
     * Show the form for editing the specified fee
     */
    public function edit(Fee $fee)
    {
        // Only allow editing of unpaid fees
        if ($fee->status == 1) {
            return redirect()->route('admin.fees.show', $fee)
                            ->with('error', 'Cannot edit paid fees.');
        }

        $members = Member::verified()->notDeleted()->get();
        $feeTypes = FeeType::active()->get();
        $loans = Loan::where('status', '!=', 3)->with(['member', 'product'])->get();

        return view('admin.fees.edit', compact('fee', 'members', 'feeTypes', 'loans'));
    }

    /**
     * Update the specified fee
     */
    public function update(Request $request, Fee $fee)
    {
        // Only allow editing of unpaid fees
        if ($fee->status == 1) {
            return redirect()->route('admin.fees.show', $fee)
                            ->with('error', 'Cannot edit paid fees.');
        }

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'loan_id' => 'nullable|exists:loans,id',
            'fees_type_id' => 'required|exists:fees_types,id',
            'payment_type' => 'required|integer|in:1,2,3',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:100',
            'pay_ref' => 'nullable|string|max:100',
        ]);

        $fee->update($validated);

        return redirect()->route('admin.fees.show', $fee)
                        ->with('success', 'Fee updated successfully.');
    }

    /**
     * Mark fee as paid
     */
    public function markAsPaid(Request $request, Fee $fee)
    {
        $validated = $request->validate([
            'payment_type' => 'required|integer|in:1,2,3',
            'pay_ref' => 'nullable|string|max:100',
            'payment_description' => 'nullable|string|max:500',
        ]);

        $fee->update([
            'payment_type' => $validated['payment_type'],
            'pay_ref' => $validated['pay_ref'],
            'payment_description' => $validated['payment_description'] ?? 'Manual payment confirmation',
            'payment_status' => 'Paid',
            'status' => 1,
        ]);

        // Post to General Ledger
        try {
            $accountingService = new \App\Services\AccountingService();
            $journal = $accountingService->postFeeCollectionEntry($fee, $fee->fee_type);
            if ($journal) {
                \Log::info('Fee GL entry posted', [
                    'fee_id' => $fee->id,
                    'journal_number' => $journal->journal_number
                ]);
            }
        } catch (\Exception $glError) {
            \Log::error('Fee GL posting failed', [
                'fee_id' => $fee->id,
                'error' => $glError->getMessage()
            ]);
            // Continue - don't fail fee marking if GL posting fails
        }

        return redirect()->back()
                        ->with('success', 'Fee marked as paid successfully.');
    }

    /**
     * Remove the specified fee
     */
    public function destroy(Fee $fee)
    {
        // Only allow deletion of unpaid fees
        if ($fee->status == 1) {
            return redirect()->back()
                            ->with('error', 'Cannot delete paid fees.');
        }

        $fee->delete();

        return redirect()->route('admin.fees.index')
                        ->with('success', 'Fee deleted successfully.');
    }

    /**
     * Get member's fee status
     */
    public function getMemberFeeStatus(Member $member)
    {
        // Get mandatory fees status
        $mandatoryFeeTypes = FeeType::active()->where('required_disbursement', 0)->get();
        $mandatoryFees = [];

        foreach ($mandatoryFeeTypes as $feeType) {
            $paidFee = Fee::where('member_id', $member->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1)
                         ->first();

            $mandatoryFees[] = [
                'fee_type' => $feeType->name,
                'required' => true,
                'paid' => $paidFee ? true : false,
                'amount' => $paidFee ? $paidFee->amount : 0,
                'payment_date' => $paidFee ? $paidFee->created_at->format('Y-m-d H:i:s') : null,
            ];
        }

        // Get recent fee payments
        $recentPayments = Fee::where('member_id', $member->id)
                            ->with(['feeType', 'loan'])
                            ->orderByLegacyTimestamp()
                            ->take(10)
                            ->get()
                            ->map(function($fee) {
                                return [
                                    'fee_type' => $fee->feeType->name,
                                    'amount' => $fee->amount,
                                    'status' => $fee->status == 1 ? 'Paid' : 'Pending',
                                    'loan_code' => $fee->loan ? $fee->loan->code : null,
                                    'payment_date' => $fee->created_at->format('Y-m-d H:i:s'),
                                ];
                            });

        return response()->json([
            'success' => true,
            'member' => [
                'name' => $member->fname . ' ' . $member->lname,
                'code' => $member->code,
            ],
            'mandatory_fees' => $mandatoryFees,
            'recent_payments' => $recentPayments,
            'eligible_for_loan' => collect($mandatoryFees)->where('paid', false)->isEmpty(),
        ]);
    }

    /**
     * Get loan's upfront charges status
     */
    public function getLoanChargeStatus(Loan $loan)
    {
        // Get upfront fee types
        $upfrontFeeTypes = FeeType::active()->where('required_disbursement', 1)->get();
        $upfrontCharges = [];

        foreach ($upfrontFeeTypes as $feeType) {
            $paidFee = Fee::where('loan_id', $loan->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1)
                         ->first();

            $upfrontCharges[] = [
                'fee_type' => $feeType->name,
                'required' => true,
                'paid' => $paidFee ? true : false,
                'amount' => $paidFee ? $paidFee->amount : 0,
                'payment_date' => $paidFee ? $paidFee->created_at->format('Y-m-d H:i:s') : null,
            ];
        }

        return response()->json([
            'success' => true,
            'loan' => [
                'code' => $loan->code,
                'principal' => $loan->principal,
                'member' => $loan->member->fname . ' ' . $loan->member->lname,
            ],
            'upfront_charges' => $upfrontCharges,
            'eligible_for_disbursement' => collect($upfrontCharges)->where('paid', false)->isEmpty(),
        ]);
    }

    /**
     * Process mobile money fee payment
     */
    public function processMobileMoneyPayment(Request $request, Fee $fee)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:15',
            'network' => 'required|string|in:MTN,AIRTEL',
        ]);

        // This would integrate with FlexiPay for fee collection
        // For now, we'll create a pending payment record

        $fee->update([
            'payment_type' => 2, // Mobile Money
            'payment_status' => 'Pending',
            'payment_description' => 'Mobile money payment initiated for ' . $validated['network'],
            'pay_ref' => 'MM-' . time(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mobile money payment initiated. Customer will receive payment prompt on ' . $validated['phone_number'],
            'payment_reference' => $fee->pay_ref,
        ]);
    }

    /**
     * Get fee types for AJAX
     */
    public function getFeeTypes()
    {
        $feeTypes = FeeType::active()->get(['id', 'name', 'required_disbursement']);

        return response()->json([
            'success' => true,
            'fee_types' => $feeTypes->map(function($feeType) {
                return [
                    'id' => $feeType->id,
                    'name' => $feeType->name,
                    'type' => $feeType->required_disbursement == 0 ? 'Mandatory' : 'Upfront Charge',
                    'requires_loan' => $feeType->required_disbursement == 1,
                ];
            }),
        ]);
    }

    /**
     * Generate fee payment receipt
     */
    public function generateReceipt(Fee $fee)
    {
        // Implementation for generating receipt
        return response()->json(['success' => true, 'receipt_url' => '#']);
    }

    /**
     * Get fee type information
     */
    public function getFeeTypeInfo(FeeType $feeType)
    {
        $html = view('admin.fees.partials.fee-type-info', compact('feeType'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Generate receipt for fee payment
     */
    public function receipt(Fee $fee)
    {
        $fee->load(['member', 'loan', 'feeType', 'addedBy']);

        return view('admin.fees.receipt', compact('fee'));
    }

    /**
     * Get receipt data for modal display
     */
    public function getReceiptModal(Fee $fee)
    {
        $fee->load(['member', 'loan', 'feeType', 'addedBy']);

        return response()->json([
            'success' => true,
            'html' => view('admin.fees.partials.receipt-modal-content', compact('fee'))->render()
        ]);
    }

    /**
     * Initiate mobile money payment
     */
    private function initiateMobileMoneyPayment($member, $fee)
    {
        // This is a placeholder for mobile money integration
        // In a real implementation, you would integrate with mobile money APIs like:
        // - MTN Mobile Money API
        // - Airtel Money API
        // - Other telecom provider APIs
        
        $phoneNumber = $member->mobile_no;
        $amount = $fee->amount;
        $feeTypeName = $fee->feeType->name;
        
        // Log the mobile money payment initiation
        \Log::info("Mobile Money Payment Initiated", [
            'member_id' => $member->id,
            'member_name' => $member->fname . ' ' . $member->lname,
            'phone_number' => $phoneNumber,
            'fee_id' => $fee->id,
            'fee_type' => $feeTypeName,
            'amount' => $amount,
            'payment_reference' => $fee->pay_ref ?? 'PMT-' . time(),
        ]);

        // Update fee with payment reference if not set
        if (!$fee->pay_ref) {
            $fee->update(['pay_ref' => 'PMT-' . $fee->id . '-' . time()]);
        }

        // In production, this would trigger actual USSD prompt
        // For now, we'll just return true to indicate successful initiation
        return true;
    }

    /**
     * Store fee payment with mobile money collection
     */
    public function storeMobileMoneyPayment(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'fees_type_id' => 'required|exists:fees_types,id',
            'loan_id' => 'nullable|exists:personal_loans,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:100',
            'member_phone' => 'required|string',
            'member_name' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $member = Member::findOrFail($validated['member_id']);
            $feeType = FeeType::findOrFail($validated['fees_type_id']);

            // Create fee record with pending status
            $fee = Fee::create([
                'member_id' => $validated['member_id'],
                'loan_id' => $validated['loan_id'] ?? null,
                'fees_type_id' => $validated['fees_type_id'],
                'payment_type' => 1, // Mobile Money
                'payment_phone' => $validated['member_phone'], // Save actual phone used
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'added_by' => auth()->id(),
                'status' => 0, // Pending
                'payment_status' => 'Pending',
                'payment_description' => 'Awaiting mobile money payment',
                'datecreated' => now()
            ]);

            // Initialize Mobile Money Service
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

            // Collect money from member's phone (Stanbic will generate short request ID)
            $result = $mobileMoneyService->collectMoney(
                $validated['member_name'],
                $validated['member_phone'],
                $validated['amount'],
                "Fee Payment: {$feeType->name}"
            );

            // Check if payment initiation was successful
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment gateway error');
            }

            // Use Stanbic-generated reference (14 chars: EbP##########)
            // This is the same format used for all payment types
            $transactionRef = $result['reference'] ?? null;
            
            if (!$transactionRef) {
                throw new \Exception('Payment initiated but no transaction reference received');
            }
            
            $fee->update([
                'payment_raw' => json_encode($result),
                'payment_description' => $result['message'] ?? 'Mobile money request sent',
                'pay_ref' => $transactionRef // Save the FlexiPay transaction reference
            ]);

            DB::commit();

            // Return success with transaction reference for polling
            return response()->json([
                'success' => true,
                'message' => 'Payment request sent to member\'s phone',
                'transaction_reference' => $transactionRef,
                'fee_id' => $fee->id,
                'status_code' => $result['status_code'] ?? 'PENDING'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Mobile Money Fee Payment Error", [
                'member_id' => $validated['member_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check mobile money payment status
     */
    public function checkMobileMoneyStatus($transactionRef)
    {
        try {
            \Log::info("=== CHECKING MOBILE MONEY STATUS ===", [
                'transaction_ref' => $transactionRef
            ]);
            
            // Find fee by transaction reference (fixed: removed JSON_EXTRACT to avoid invalid JSON errors)
            $fee = Fee::where('pay_ref', $transactionRef)
                     ->orWhere('pay_ref', 'like', "%{$transactionRef}%")
                     ->first();

            if (!$fee) {
                \Log::warning("Fee not found for transaction reference", [
                    'transaction_ref' => $transactionRef
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'Transaction not found'
                ]);
            }

            \Log::info("Fee found", [
                'fee_id' => $fee->id,
                'current_status' => $fee->status,
                'pay_ref' => $fee->pay_ref
            ]);

            // Check current status
            if ($fee->status == 1) {
                \Log::info("Fee already marked as paid");
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully'
                ]);
            }

            // Call Mobile Money Service to check status
            \Log::info("Calling FlexiPay to check status");
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);
            
            // Detect network from payment phone for Stanbic status check
            $network = null;
            if ($fee->payment_phone) {
                $network = $mobileMoneyService->detectNetwork($fee->payment_phone);
                
                \Log::info("Network detected from payment phone", [
                    'phone' => $fee->payment_phone,
                    'network' => $network
                ]);
            }
            
            $statusResult = $mobileMoneyService->checkTransactionStatus($transactionRef, $network);
            
            \Log::info("FlexiPay status result", [
                'status_result' => $statusResult
            ]);

            // Update fee based on status
            if ($statusResult['status'] === 'completed') {
                $fee->update([
                    'status' => 1, // Paid
                    'payment_status' => 'Paid',
                    'payment_description' => 'Payment completed via mobile money',
                    'payment_raw' => json_encode($statusResult)
                ]);

                // Post to General Ledger
                try {
                    $accountingService = new \App\Services\AccountingService();
                    $journal = $accountingService->postFeeCollectionEntry($fee, $fee->fee_type);
                    if ($journal) {
                        \Log::info('Fee GL entry posted for mobile money payment', [
                            'fee_id' => $fee->id,
                            'journal_number' => $journal->journal_number
                        ]);
                    }
                } catch (\Exception $glError) {
                    \Log::error('Fee GL posting failed for mobile money payment', [
                        'fee_id' => $fee->id,
                        'error' => $glError->getMessage()
                    ]);
                    // Continue - don't fail payment confirmation if GL posting fails
                }

                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully'
                ]);
            } elseif ($statusResult['status'] === 'failed') {
                // Check if payment is recent (within 2 minutes) - FlexiPay retries 3 times
                $createdAt = \Carbon\Carbon::parse($fee->datecreated);
                $ageInMinutes = $createdAt->diffInMinutes(now());
                
                if ($ageInMinutes < 2) {
                    // Payment is recent - don't mark as failed yet, FlexiPay is still retrying
                    \Log::info("Payment marked as pending - still within retry window", [
                        'fee_id' => $fee->id,
                        'age_minutes' => $ageInMinutes,
                        'transaction_ref' => $transactionRef
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'status' => 'pending',
                        'message' => 'Payment being processed - FlexiPay will retry if user cancelled'
                    ]);
                }
                
                // Payment is old enough - mark as failed
                $fee->update([
                    'status' => 2, // Failed
                    'payment_status' => 'Failed',
                    'payment_description' => $statusResult['message'] ?? 'Payment failed',
                    'payment_raw' => json_encode($statusResult)
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'failed',
                    'message' => $statusResult['message'] ?? 'Payment failed'
                ]);
            }

            // Still pending
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment pending - waiting for member authorization'
            ]);

        } catch (\Exception $e) {
            \Log::error("Mobile Money Status Check Error", [
                'transaction_ref' => $transactionRef,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Status check failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry a failed mobile money payment
     */
    public function retryMobileMoneyPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'fee_id' => 'required|exists:fees,id',
                'member_phone' => 'required|string',
                'member_name' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string'
            ]);

            // Find the fee
            $fee = Fee::findOrFail($validated['fee_id']);

            // Verify the fee is failed and is a mobile money payment
            if ($fee->payment_type != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only mobile money payments can be retried'
                ], 400);
            }

            if ($fee->status == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been completed'
                ], 400);
            }

            // Store original amount if this is first retry and amount changed
            if (empty($fee->original_amount) && $fee->amount != $validated['amount']) {
                $originalAmount = $fee->amount;
            } else {
                $originalAmount = $fee->original_amount;
            }

            // CRITICAL: Invalidate old transaction reference before retry to prevent conflicts
            // This ensures the old failed/pending transaction doesn't block the new one
            $oldPayRef = $fee->pay_ref;
            
            // Reset fee to pending status with cleared reference
            $fee->update([
                'status' => 0, // Pending
                'payment_status' => 'Pending - Retry Initiated',
                'payment_description' => 'Retry payment (Old ref: ' . ($oldPayRef ?? 'none') . ') - ' . now()->format('Y-m-d H:i:s'),
                'original_amount' => $originalAmount,
                'amount' => $validated['amount'], // Update to new amount if changed
                'payment_phone' => $validated['member_phone'], // Store actual phone used
                'pay_ref' => null, // Clear old reference before generating new one
                'payment_raw' => null // Clear old payment data
            ]);

            // Call Mobile Money Service
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);
            
            $result = $mobileMoneyService->collectMoney(
                $validated['member_name'],
                $validated['member_phone'],
                $validated['amount'],
                $validated['description']
            );

            // Check if payment initiation was successful
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment gateway error');
            }

            // Use Stanbic-generated reference (14 chars: EbP##########)
            // This is the same format used for all payment types
            $transactionRef = $result['reference'] ?? null;
            
            if (!$transactionRef) {
                throw new \Exception('Payment initiated but no transaction reference received');
            }
            
            // Update fee with new transaction reference
            $fee->update([
                'pay_ref' => $transactionRef,
                'payment_raw' => json_encode($result),
                'payment_description' => 'Retry payment initiated - ' . now()->format('Y-m-d H:i:s')
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment retry initiated successfully',
                    'transaction_reference' => $result['reference'],
                    'fee_id' => $fee->id
                ]);
            } else {
                // Update fee to failed if initial request fails
                $fee->update([
                    'status' => 2, // Failed
                    'payment_status' => 'Failed',
                    'payment_description' => 'Retry failed: ' . ($result['message'] ?? 'Unknown error')
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to initiate payment retry'
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error("Mobile Money Retry Error", [
                'fee_id' => $request->fee_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry payment: ' . $e->getMessage()
            ], 500);
        }
    }
}