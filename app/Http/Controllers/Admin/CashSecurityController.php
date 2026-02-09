<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSecurity;
use App\Models\Member;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashSecurityController extends Controller
{
    /**
     * Store a new cash security payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'loan_id' => 'nullable|exists:loans,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|integer|in:1,2,3', // 1=Mobile Money, 2=Cash, 3=Bank Transfer
            'description' => 'nullable|string|max:500',
            'member_phone' => 'nullable|string',
            'member_name' => 'nullable|string',
        ]);

        // Get member info
        $member = Member::findOrFail($validated['member_id']);

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

                // Return success with transaction reference for polling
                return response()->json([
                    'success' => true,
                    'message' => 'Payment request sent to member\'s phone',
                    'transaction_reference' => $transactionRef,
                    'cash_security_id' => $cashSecurity->id,
                    'status_code' => $result['status_code'] ?? 'PENDING'
                ]);
                                
            } else { // Cash or Bank Transfer
                $cashSecurity = CashSecurity::create([
                    'member_id' => $validated['member_id'],
                    'loan_id' => $validated['loan_id'] ?? null,
                    'payment_type' => $validated['payment_type'],
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'added_by' => auth()->id(),
                    'status' => 1, // Paid
                    'payment_status' => 'Paid',
                    'payment_description' => 'Manual payment recorded',
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

            // Process the status result
            if (isset($statusResult['status_code'])) {
                // Check for successful payment codes (00, 01, SUCCESSFUL, SUCCESS)
                if (in_array($statusResult['status_code'], ['00', '01', 'SUCCESSFUL', 'SUCCESS'])) {
                    $cashSecurity->update([
                        'status' => 1,
                        'payment_status' => 'Paid',
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
                } elseif ($statusResult['status_code'] === 'FAILED') {
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
        $cashSecurity->load(['member', 'loan', 'addedBy']);
        
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
        if ($cashSecurity->status == 1) {
            return redirect()->back()
                            ->with('error', 'Cannot delete paid cash security.');
        }

        $cashSecurity->delete();

        return redirect()->back()
                        ->with('success', 'Cash security deleted successfully.');
    }
}
