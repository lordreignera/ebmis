<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LoanApprovalService;
use App\Services\DisbursementService;
use App\Services\RepaymentService;
use App\Services\LoanScheduleService;
use App\Services\FeeManagementService;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Disbursement;
use App\Models\Repayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanManagementController extends Controller
{
    private LoanApprovalService $approvalService;
    private DisbursementService $disbursementService;
    private RepaymentService $repaymentService;
    private LoanScheduleService $scheduleService;
    private FeeManagementService $feeService;
    
    public function __construct(
        LoanApprovalService $approvalService,
        DisbursementService $disbursementService,
        RepaymentService $repaymentService,
        LoanScheduleService $scheduleService,
        FeeManagementService $feeService
    ) {
        $this->approvalService = $approvalService;
        $this->disbursementService = $disbursementService;
        $this->repaymentService = $repaymentService;
        $this->scheduleService = $scheduleService;
        $this->feeService = $feeService;
    }
    
    /**
     * Show loan approval page
     */
    public function showLoanApproval($id, $type = 'personal')
    {
        try {
            $loan = $this->getLoanByTypeAndId($type, $id);
            if (!$loan) {
                return redirect()->back()->with('error', 'Loan not found');
            }
            
            $approvalSummary = $this->approvalService->generateApprovalSummary($loan);
            
            return view('admin.loans.approve', [
                'loan' => $loan,
                'approval_summary' => $approvalSummary,
                'loan_type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading loan approval page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading loan details');
        }
    }
    
    /**
     * Process loan approval
     */
    public function approveLoan(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|string|in:personal,group',
            'charge_type' => 'required|string|in:1,2',
            'comments' => 'nullable|string|max:500'
        ]);
        
        try {
            $loan = $this->getLoanByTypeAndId($request->loan_type, $request->loan_id);
            if (!$loan) {
                return response()->json([
                    'status' => false,
                    'msg' => 'Loan not found'
                ]);
            }
            
            $approvalData = [
                'charge_type' => $request->charge_type,
                'comments' => $request->comments
            ];
            
            if ($request->loan_type === 'personal') {
                $result = $this->approvalService->approvePersonalLoan($loan, $approvalData);
            } else {
                $result = $this->approvalService->approveGroupLoan($loan, $approvalData);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Loan approval error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'msg' => 'Loan approval failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Reject loan
     */
    public function rejectLoan(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|string|in:personal,group',
            'comments' => 'required|string|max:500'
        ]);
        
        try {
            $loan = $this->getLoanByTypeAndId($request->loan_type, $request->loan_id);
            if (!$loan) {
                return response()->json([
                    'status' => false,
                    'msg' => 'Loan not found'
                ]);
            }
            
            $result = $this->approvalService->rejectLoan($loan, $request->comments);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Loan rejection error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'msg' => 'Loan rejection failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show disbursement page
     */
    public function showDisbursements()
    {
        try {
            $pendingDisbursements = $this->disbursementService->getPendingDisbursements();
            $statistics = $this->disbursementService->getDisbursementStatistics();
            
            return view('admin.loans.disbursements', [
                'pending_disbursements' => $pendingDisbursements,
                'statistics' => $statistics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading disbursements page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading disbursements');
        }
    }
    
    /**
     * Process loan disbursement
     */
    public function processDisbursement(Request $request)
    {
        $request->validate([
            'disbursement_id' => 'required|integer',
            'type' => 'required|string|in:0,1,2', // 0=cash, 1=mobile money, 2=bank
            'account_number' => 'nullable|string',
            'd_date' => 'required|date',
            'inv_id' => 'nullable|integer'
        ]);
        
        // Additional validation for mobile money
        if ($request->type == '1') {
            $request->validate([
                'account_number' => 'required|string|min:10',
                'loan_amt' => 'required|numeric|min:1000'
            ]);
        }
        
        try {
            $disbursementData = [
                'type' => $request->type,
                'account_number' => $request->account_number,
                'd_date' => $request->d_date,
                'inv_id' => $request->inv_id,
                'loan_amt' => $request->loan_amt ?? 0,
                'medium' => $request->medium ?? '2' // Default to MTN
            ];
            
            $result = $this->disbursementService->processLoanDisbursement(
                $request->disbursement_id,
                $disbursementData
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Disbursement processing error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'msg' => 'Disbursement processing failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show repayments page
     */
    public function showRepayments()
    {
        try {
            return view('admin.loans.repayments');
            
        } catch (\Exception $e) {
            Log::error('Error loading repayments page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading repayments');
        }
    }
    
    /**
     * Process loan repayment
     */
    public function processRepayment(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
            'amount' => 'required|numeric|min:100',
            'type' => 'required|string|in:1,2,3' // 1=cash, 2=mobile money, 3=bank
        ]);
        
        // Additional validation for mobile money
        if ($request->type == '2') {
            $request->validate([
                'phone' => 'required|string|min:10',
                'network' => 'nullable|string|in:MTN,AIRTEL'
            ]);
        }
        
        try {
            $paymentData = [
                'schedule_id' => $request->schedule_id,
                'amount' => $request->amount,
                'type' => $request->type,
                'phone' => $request->phone ?? '',
                'network' => $request->network ?? null
            ];
            
            $result = $this->repaymentService->processRepayment($paymentData);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Repayment processing error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Repayment processing failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show loan schedule
     */
    public function showLoanSchedule($id, $type = 'personal')
    {
        try {
            $loan = $this->getLoanByTypeAndId($type, $id);
            if (!$loan) {
                return redirect()->back()->with('error', 'Loan not found');
            }
            
            $amortizationTable = $this->scheduleService->calculateAmortizationTable($loan);
            
            return view('admin.loans.schedule', [
                'loan' => $loan,
                'amortization_table' => $amortizationTable,
                'loan_type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading loan schedule: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading loan schedule');
        }
    }
    
    /**
     * Generate loan schedule
     */
    public function generateSchedule(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|string|in:personal,group'
        ]);
        
        try {
            $loan = $this->getLoanByTypeAndId($request->loan_type, $request->loan_id);
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }
            
            $result = $this->scheduleService->generateAndSaveSchedule($loan);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Schedule generated successfully' : 'Failed to generate schedule'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Schedule generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Schedule generation failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show loan fees
     */
    public function showLoanFees($id, $type = 'personal')
    {
        try {
            $loan = $this->getLoanByTypeAndId($type, $id);
            if (!$loan) {
                return redirect()->back()->with('error', 'Loan not found');
            }
            
            $calculatedFees = $this->feeService->calculateLoanFees($loan);
            $existingFees = $this->feeService->getLoanFees($loan);
            $disbursementCalculation = $this->feeService->calculateDisbursementAmount($loan);
            
            return view('admin.loans.fees', [
                'loan' => $loan,
                'calculated_fees' => $calculatedFees,
                'existing_fees' => $existingFees,
                'disbursement_calculation' => $disbursementCalculation,
                'loan_type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading loan fees: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading loan fees');
        }
    }
    
    /**
     * Process mobile money callback
     */
    public function mobileMoneyCallback(Request $request)
    {
        try {
            Log::info('Mobile money callback received', $request->all());
            
            $result = $this->repaymentService->processPaymentCallback($request->all());
            
            if ($result['success']) {
                return response()->json(['status' => 'success', 'message' => $result['message']]);
            } else {
                return response()->json(['status' => 'error', 'message' => $result['message']], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Mobile money callback error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Callback processing failed'
            ], 500);
        }
    }
    
    /**
     * Test mobile money connection
     */
    public function testMobileMoneyConnection()
    {
        try {
            $result = $this->disbursementService->testMobileMoneyConnection();
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Mobile money connection test error: ' . $e->getMessage());
            
            return response()->json([
                'connection' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get loan by type and ID
     */
    private function getLoanByTypeAndId(string $type, int $id)
    {
        switch ($type) {
            case 'personal':
                return PersonalLoan::find($id);
            case 'group':
                return GroupLoan::find($id);
            case 'unified':
                return Loan::find($id);
            default:
                return null;
        }
    }
}