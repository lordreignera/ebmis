<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSecurity;
use App\Models\GroupLoan;
use App\Models\Member;
use App\Models\PersonalLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashSecurityController extends Controller
{
    public function index(Request $request)
    {
        $securities = $this->cashSecurityQuery($request)
            ->latest('datecreated')
            ->latest('id')
            ->paginate((int) $request->get('per_page', 20))
            ->withQueryString();

        $stats = [
            'total_count' => CashSecurity::count(),
            'held_amount' => CashSecurity::where('status', CashSecurity::STATUS_PAID)->where('returned', 0)->sum('amount'),
            'pending_amount' => CashSecurity::where('status', CashSecurity::STATUS_PENDING)->sum('amount'),
            'returned_amount' => CashSecurity::where('returned', 1)->sum('amount'),
        ];

        return view('admin.cash-securities.index', compact('securities', 'stats'));
    }

    public function create(Request $request)
    {
        $cashSecurity = new CashSecurity([
            'member_id' => $request->integer('member_id') ?: null,
            'loan_id' => $request->integer('loan_id') ?: null,
            'payment_type' => CashSecurity::PAYMENT_MOBILE_MONEY,
        ]);

        return view('admin.cash-securities.create', $this->formOptions($cashSecurity));
    }

    public function edit(CashSecurity $cashSecurity)
    {
        $cashSecurity->load(['member', 'loan']);

        return view('admin.cash-securities.edit', $this->formOptions($cashSecurity));
    }

    public function update(Request $request, CashSecurity $cashSecurity)
    {
        $rules = [
            'loan_id' => ['nullable', 'integer'],
            'loan_type' => ['nullable', 'in:personal,group'],
            'description' => ['nullable', 'string', 'max:500'],
            'member_phone' => ['nullable', 'string', 'max:20'],
        ];

        if ($cashSecurity->can_edit_financials) {
            $rules = array_merge($rules, [
                'member_id' => ['required', 'exists:members,id'],
                'loan_type' => ['nullable', 'in:personal,group'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'payment_type' => ['required', 'integer', 'in:1,2,3'],
            ]);
        }

        $validated = $request->validate($rules);

        if (
            $cashSecurity->can_edit_financials
            && !$request->user()?->isSuperAdmin()
            && (int) $validated['payment_type'] !== CashSecurity::PAYMENT_MOBILE_MONEY
        ) {
            return back()
                ->withInput()
                ->with('error', 'Only the Super Administrator can set cash or bank cash-security records.');
        }

        if ($cashSecurity->can_edit_financials) {
            $member = Member::findOrFail($validated['member_id']);
            $loanValidation = $this->validateLinkedLoan($request, $validated, $member);
            if ($loanValidation) {
                return $loanValidation;
            }

            $cashSecurity->fill([
                'member_id' => $validated['member_id'],
                'loan_id' => $validated['loan_id'] ?? null,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_phone' => $validated['member_phone'] ?? $member->contact,
                'description' => $validated['description'] ?? null,
            ]);
        } else {
            $cashSecurity->loadMissing('member');
            if ($cashSecurity->member) {
                $loanValidation = $this->validateLinkedLoan($request, $validated, $cashSecurity->member);
                if ($loanValidation) {
                    return $loanValidation;
                }
            }

            $cashSecurity->fill([
                'loan_id' => $validated['loan_id'] ?? null,
                'payment_phone' => $validated['member_phone'] ?? $cashSecurity->payment_phone,
                'description' => $validated['description'] ?? null,
            ]);
        }

        $cashSecurity->save();

        return redirect()
            ->route('admin.cash-securities.show', $cashSecurity)
            ->with('success', $cashSecurity->can_edit_financials
                ? 'Cash security updated.'
                : 'Cash security notes updated. Paid or returned financial records cannot have amount, member, or payment method changed.');
    }

    public function export(Request $request)
    {
        $rows = $this->cashSecurityQuery($request)
            ->latest('datecreated')
            ->latest('id')
            ->get();

        $filename = 'cash_securities_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Security ID',
                'Date',
                'Member Code',
                'Member Name',
                'Phone',
                'Cash Security Account',
                'Loan',
                'Amount',
                'Payment Type',
                'Status',
                'Reference',
                'Returned',
                'Added By',
                'Description',
            ]);

            foreach ($rows as $security) {
                fputcsv($handle, [
                    'CS-' . str_pad((string) $security->id, 6, '0', STR_PAD_LEFT),
                    optional($security->datecreated ?? $security->created_at)->format('Y-m-d H:i'),
                    $security->member->code ?? '',
                    $security->member?->full_name ?? '',
                    $security->member->contact ?? $security->payment_phone,
                    $security->member->cash_security_account_number ?? '',
                    $security->loan->code ?? '',
                    number_format((float) $security->amount, 2, '.', ''),
                    $security->payment_type_name,
                    $security->status_label,
                    $security->transaction_reference ?: $security->pay_ref,
                    (int) $security->returned === 1 ? optional($security->returned_at)->format('Y-m-d H:i') : 'No',
                    $security->addedBy->name ?? '',
                    $security->description ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Store a new cash security payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'loan_id' => 'nullable|integer',
            'loan_type' => 'nullable|in:personal,group',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|integer|in:1,2,3', // 1=Mobile Money, 2=Cash, 3=Bank Transfer
            'description' => 'nullable|string|max:500',
            'member_phone' => 'nullable|string',
            'member_name' => 'nullable|string',
        ]);

        $member = Member::findOrFail($validated['member_id']);
        $loanValidation = $this->validateLinkedLoan($request, $validated, $member);
        if ($loanValidation) {
            return $loanValidation;
        }

        if (!$request->user()?->isSuperAdmin() && (int) $validated['payment_type'] !== 1) {
            $message = 'Access denied. Only the Super Administrator can confirm cash or bank cash-security payments. Please use Mobile Money.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 403);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $message);
        }

        try {
            DB::beginTransaction();

            // Handle payment processing based on payment type
            if ($validated['payment_type'] == 1) { // Mobile Money
                // Create cash security record with pending status
                $cashSecurity = CashSecurity::create([
                    'member_id' => $validated['member_id'],
                    'loan_id' => $validated['loan_id'] ?? null,
                    'payment_type' => 1, // Mobile Money
                    'payment_phone' => $validated['member_phone'] ?? $member->contact,
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'added_by' => auth()->id(),
                    'status' => 0, // Pending
                    'payment_status' => 'Pending',
                    'payment_description' => 'Awaiting mobile money payment',
                    'datecreated' => now()
                ]);

                // Initialize Mobile Money Service (same as fee payment)
                $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

                // Collect money from member's phone
                $result = $mobileMoneyService->collectMoney(
                    $validated['member_name'] ?? ($member->fname . ' ' . $member->lname),
                    $validated['member_phone'] ?? $member->contact,
                    $validated['amount'],
                    "Cash Security Payment"
                );

                // Check if payment initiation was successful
                if (!$result['success']) {
                    throw new \Exception($result['message'] ?? 'Payment gateway error');
                }

                // Use Stanbic-generated reference
                $transactionRef = $result['reference'] ?? null;
                
                if (!$transactionRef) {
                    throw new \Exception('Payment initiated but no transaction reference received');
                }
                
                $cashSecurity->update([
                    'payment_raw' => json_encode($result),
                    'payment_description' => $result['message'] ?? 'Mobile money request sent',
                    'pay_ref' => $transactionRef,
                    'transaction_reference' => $transactionRef
                ]);

                DB::commit();

                if (!$request->boolean('manager_form') || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment request sent to member\'s phone',
                        'transaction_reference' => $transactionRef,
                        'cash_security_id' => $cashSecurity->id,
                        'status_code' => $result['status_code'] ?? 'PENDING'
                    ]);
                }

                return redirect()
                    ->route('admin.cash-securities.show', $cashSecurity)
                    ->with('info', 'Mobile money request sent to the member phone. Check status before treating it as paid.');
                                
            } else { // Cash or Bank Transfer
                $cashSecurity = CashSecurity::create([
                    'member_id' => $validated['member_id'],
                    'loan_id' => $validated['loan_id'] ?? null,
                    'payment_type' => $validated['payment_type'],
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'added_by' => auth()->id(),
                    'status' => 1, // Paid
                    'payment_status' => 'Confirmed',
                    'payment_description' => 'Manual payment confirmed',
                    'datecreated' => now()
                ]);
                
                // 🆕 POST TO GENERAL LEDGER for Cash/Bank payments
                try {
                    $accountingService = new \App\Services\AccountingService();
                    $journal = $accountingService->postCashSecurityEntry($cashSecurity);
                    
                    if ($journal) {
                        Log::info('GL entry posted for cash security', [
                            'cash_security_id' => $cashSecurity->id,
                            'journal_number' => $journal->journal_number
                        ]);
                    }
                } catch (\Exception $glError) {
                    Log::error('GL posting failed for cash security', [
                        'cash_security_id' => $cashSecurity->id,
                        'gl_error' => $glError->getMessage()
                    ]);
                }
                
                DB::commit();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Cash security payment recorded successfully.',
                        'cash_security_id' => $cashSecurity->id,
                    ]);
                }
                
                if ($request->boolean('manager_form')) {
                    return redirect()
                        ->route('admin.cash-securities.show', $cashSecurity)
                        ->with('success', 'Cash security payment recorded successfully.');
                }

                return redirect()->back()
                    ->with('success', 'Cash security payment recorded successfully.');
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Cash security payment error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error recording cash security payment: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error recording cash security payment: ' . $e->getMessage());
        }
    }

    /**
     * Check mobile money payment status
     */
    public function checkPaymentStatus($transactionRef)
    {
        try {
            Log::info("=== CHECKING CASH SECURITY STATUS ===", [
                'transaction_ref' => $transactionRef
            ]);
            
            // Find cash security by transaction reference
            $cashSecurity = CashSecurity::where('pay_ref', $transactionRef)
                     ->orWhere('pay_ref', 'like', "%{$transactionRef}%")
                     ->orWhere('transaction_reference', $transactionRef)
                     ->first();

            if (!$cashSecurity) {
                Log::warning("Cash security not found for transaction reference", [
                    'transaction_ref' => $transactionRef
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'Transaction not found'
                ]);
            }

            Log::info("Cash security found", [
                'cash_security_id' => $cashSecurity->id,
                'current_status' => $cashSecurity->status,
                'pay_ref' => $cashSecurity->pay_ref
            ]);

            // Check current status
            if ($cashSecurity->status == 1) {
                Log::info("Cash security already marked as paid");
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully'
                ]);
            }

            // Call Mobile Money Service to check status
            Log::info("Calling MobileMoneyService to check status");
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);
            $statusResult = $mobileMoneyService->checkTransactionStatus($transactionRef);
            
            Log::info("Status result", [
                'status_result' => $statusResult
            ]);

            // Process the normalized gateway status. Code 01 may still mean processing,
            // so only the service's completed state can confirm received collateral money.
            if (isset($statusResult['status'])) {
                if ($statusResult['status'] === 'completed') {
                    $cashSecurity->update([
                        'status' => 1,
                        'payment_status' => 'Completed',
                        'payment_description' => 'Payment confirmed via status check',
                        'payment_raw' => json_encode($statusResult)
                    ]);

                    Log::info("Cash security payment marked as paid", ['cash_security_id' => $cashSecurity->id]);

                    // 🆕 POST TO GENERAL LEDGER
                    try {
                        $accountingService = new \App\Services\AccountingService();
                        $journal = $accountingService->postCashSecurityEntry($cashSecurity);
                        
                        if ($journal) {
                            Log::info('GL entry posted for cash security', [
                                'cash_security_id' => $cashSecurity->id,
                                'journal_number' => $journal->journal_number
                            ]);
                        }
                    } catch (\Exception $glError) {
                        Log::error('GL posting failed for cash security', [
                            'cash_security_id' => $cashSecurity->id,
                            'gl_error' => $glError->getMessage()
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'Payment successful'
                    ]);
                } elseif ($statusResult['status'] === 'failed') {
                    $cashSecurity->update([
                        'status' => 2,
                        'payment_status' => 'Failed',
                        'payment_description' => $statusResult['message'] ?? 'Payment failed',
                        'payment_raw' => json_encode($statusResult)
                    ]);

                    return response()->json([
                        'success' => false,
                        'status' => 'failed',
                        'message' => $statusResult['message'] ?? 'Payment failed'
                    ]);
                }
            }

            // Still pending
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment still processing'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking cash security payment status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error checking payment status'
            ], 500);
        }
    }

    /**
     * Show cash security details
     */
    public function show(CashSecurity $cashSecurity)
    {
        $cashSecurity->load(['member', 'loan', 'addedBy', 'returnedBy']);
        
        return view('admin.cash-securities.show', compact('cashSecurity'));
    }

    /**
     * Generate cash security receipt
     */
    public function receipt(CashSecurity $cashSecurity)
    {
        $cashSecurity->load(['member', 'loan']);
        
        return view('admin.cash-securities.receipt', compact('cashSecurity'));
    }

    /**
     * Return cash security to member via mobile money
     */
    public function returnCashSecurity(Request $request)
    {
        $validated = $request->validate([
            'cash_security_id' => 'required|exists:cash_securities,id',
            'phone' => 'required|string',
            'member_name' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
            'investment_id' => 'required|exists:investment,id',
        ]);

        try {
            DB::beginTransaction();

            // Get the cash security record
            $cashSecurity = CashSecurity::findOrFail($validated['cash_security_id']);

            // Verify it's paid and not already returned
            if ($cashSecurity->status != 1) {
                throw new \Exception('Cannot return unpaid cash security');
            }

            if ($cashSecurity->returned == 1) {
                throw new \Exception('Cash security has already been returned');
            }

            // Validate minimum disbursement amount (Stanbic requirement)
            if ($validated['amount'] < 1000) {
                throw new \Exception('Minimum disbursement amount is 1,000 UGX. Amount below this limit cannot be sent via mobile money.');
            }

            // Get investment account (for reference/allocation only - not a real balance)
            $investment = \App\Models\Investment::findOrFail($validated['investment_id']);
            
            Log::info("Cash security return - investment account selected", [
                'investment_id' => $investment->id,
                'investment_name' => $investment->name,
                'investment_amount' => $investment->amount,
                'return_amount' => $validated['amount']
            ]);

            // Initialize Mobile Money Service
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

            // Validate phone number
            $phoneValidation = $mobileMoneyService->validatePhoneNumber($validated['phone']);
            if (!$phoneValidation['valid']) {
                throw new \Exception('Invalid phone number: ' . $phoneValidation['message']);
            }

            Log::info("Processing cash security return", [
                'cash_security_id' => $cashSecurity->id,
                'member_name' => $validated['member_name'],
                'phone' => $validated['phone'],
                'formatted_phone' => $phoneValidation['formatted_phone'],
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
                'investment_id' => $investment->id,
                'investment_name' => $investment->name
            ]);

            // Generate requestId using Stanbic's format: EbP{last6-timestamp}{3-microsec}{2-random}
            // Example: EbP208899123456 (total: 14 chars)
            $timestamp = substr((string)time(), -6); // Last 6 digits of timestamp
            $microsec = substr(microtime(false), 2, 3); // 3 digits of microseconds
            $random = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT); // 2 random digits
            $requestId = 'EbP' . $timestamp . $microsec . $random;
            
            Log::info("Generated requestId for cash security return", [
                'request_id' => $requestId,
                'length' => strlen($requestId),
                'cash_security_id' => $cashSecurity->id
            ]);

            // Disburse money to member's phone (same as loan disbursement)
            $result = $mobileMoneyService->disburse(
                $phoneValidation['formatted_phone'],
                $validated['amount'],
                null, // network - auto-detect
                $validated['member_name'],
                $requestId  // Use proper EbP format
            );

            Log::info("Cash security disbursement result", [
                'result' => $result
            ]);

            // Check if disbursement was successful
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Disbursement failed');
            }

            // Note: We don't deduct from investment account (same as loan disbursements)
            // The investment account is just for reference/tracking purposes
            
            Log::info("Cash security return processed via investment account", [
                'investment_id' => $investment->id,
                'investment_name' => $investment->name,
                'amount' => $validated['amount']
            ]);

            // Update cash security record
            $cashSecurity->update([
                'returned' => 1,
                'returned_at' => now(),
                'return_transaction_reference' => $result['transaction_reference'] ?? $result['reference'] ?? null,
                'return_payment_raw' => json_encode($result),
                'return_payment_status' => 'Returned',
                'returned_by' => auth()->id(),
                'description' => ($cashSecurity->description ? $cashSecurity->description . ' | ' : '') . 'Return Reason: ' . $validated['reason'] . ' | Source Account: ' . $investment->name
            ]);

            DB::commit();

            Log::info("Cash security returned successfully", [
                'cash_security_id' => $cashSecurity->id,
                'amount' => $validated['amount'],
                'phone' => $phoneValidation['formatted_phone'],
                'transaction_reference' => $result['transaction_reference'] ?? $result['reference'] ?? null,
                'investment_account' => $investment->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cash security disbursed successfully. Money sent to ' . $phoneValidation['formatted_phone'] . ' from ' . $investment->name,
                'transaction_reference' => $result['transaction_reference'] ?? $result['reference'] ?? null
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Cash security return error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error returning cash security: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete cash security (only if pending)
     */
    public function destroy(CashSecurity $cashSecurity)
    {
        if (!$cashSecurity->can_delete) {
            return redirect()->back()
                ->with('error', 'Cannot delete paid or returned cash security.');
        }

        $cashSecurity->delete();

        return redirect()->back()
                        ->with('success', 'Cash security deleted successfully.');
    }

    private function cashSecurityQuery(Request $request)
    {
        $query = CashSecurity::with(['member', 'loan', 'addedBy', 'returnedBy']);

        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('pay_ref', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('code', 'like', "%{$search}%")
                            ->orWhere('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('contact', 'like', "%{$search}%")
                            ->orWhere('cash_security_account_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('loan', fn ($loanQuery) => $loanQuery->where('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'pending' => $query->where('status', CashSecurity::STATUS_PENDING),
                'paid' => $query->where('status', CashSecurity::STATUS_PAID)->where('returned', 0),
                'failed' => $query->where('status', CashSecurity::STATUS_FAILED),
                'returned' => $query->where('returned', 1),
                default => null,
            };
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', (int) $request->payment_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('datecreated', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('datecreated', '<=', $request->date_to);
        }

        return $query;
    }

    private function formOptions(CashSecurity $cashSecurity): array
    {
        return [
            'cashSecurity' => $cashSecurity,
            'members' => Member::notDeleted()
                ->orderBy('fname')
                ->orderBy('lname')
                ->limit(1000)
                ->get(['id', 'code', 'fname', 'mname', 'lname', 'contact', 'cash_security_account_number']),
            'loans' => PersonalLoan::with('member:id,code,fname,mname,lname')
                ->latest('id')
                ->limit(1000)
                ->get(['id', 'member_id', 'code', 'principal', 'status']),
        ];
    }

    private function validateLinkedLoan(Request $request, array $validated, Member $member)
    {
        $loanType = $validated['loan_type'] ?? 'personal';

        if (empty($validated['loan_id'])) {
            return null;
        }

        if ($loanType === 'personal') {
            $loan = PersonalLoan::find($validated['loan_id']);

            if (!$loan) {
                return $this->loanValidationResponse($request, 'Selected personal loan was not found for this cash security.');
            }

            if ((int) $loan->member_id !== (int) $member->id) {
                return $this->loanValidationResponse($request, 'Cash security must be linked to a loan owned by the selected member.');
            }

            return null;
        }

        if (!GroupLoan::find($validated['loan_id'])) {
            return $this->loanValidationResponse($request, 'Selected group loan was not found for this cash security.');
        }

        return null;
    }

    private function loanValidationResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
