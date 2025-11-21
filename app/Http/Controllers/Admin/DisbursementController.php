<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Branch;
use App\Models\Investment;
use App\Models\RawPayment;
use App\Models\DisbursementTransaction;
use App\Models\Fee;
use App\Models\FeeType;
use App\Models\ProductCharge;
use App\Models\LoanCharge;
use App\Models\User;
use App\Models\Product;
use App\Models\Member;
use App\Services\MobileMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DisbursementController extends Controller
{
    protected $mobileMoneyService;

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }
    /**
     * Display a listing of disbursements
     */
    public function index(Request $request)
    {
        // Eager load both personal and group loan relationships
        $query = Disbursement::with([
            'personalLoan.member', 
            'personalLoan.product', 
            'personalLoan.branch',
            'groupLoan.group',
            'groupLoan.product',
            'groupLoan.branch',
            'addedBy'
        ]);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('loan', function($loanQuery) use ($search) {
                      $loanQuery->where('code', 'like', "%{$search}%")
                               ->orWhereHas('member', function($memberQuery) use ($search) {
                                   $memberQuery->where('fname', 'like', "%{$search}%")
                                             ->orWhere('lname', 'like', "%{$search}%")
                                             ->orWhere('code', 'like', "%{$search}%");
                               });
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->whereHas('loan', function($loanQuery) use ($request) {
                $loanQuery->where('branch_id', $request->branch_id);
            });
        }

        // Filter by payment type
        if ($request->has('payment_type') && $request->payment_type !== '') {
            $query->where('payment_type', $request->payment_type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $disbursements = $query->orderBy('created_at', 'desc')->paginate(20);

        $branches = Branch::active()->get();
        $investments = Investment::where('status', 1)->get();

        // Calculate stats from all disbursements (not filtered query)
        $stats = [
            'total_disbursements' => Disbursement::count(),
            'total_amount' => Disbursement::sum('amount'),
            'today_disbursements' => Disbursement::whereDate('created_at', today())->count(),
            'pending_disbursements' => Disbursement::where('status', 0)->count(),
            'successful_disbursements' => Disbursement::where('status', 2)->count(), // Status 2 = Disbursed
            'failed_disbursements' => Disbursement::where('status', 3)->count(), // Status 3 = Rejected
        ];

        return view('admin.disbursements.index', compact('disbursements', 'branches', 'investments', 'stats'));
    }

    /**
     * Display pending disbursements waiting for approval
     */
    public function pending(Request $request)
    {
        // Personal loans query - Only show loans ready for disbursement
        $personalLoansQuery = PersonalLoan::where('status', 1) // Approved loans
                    ->whereDoesntHave('disbursements', function($q) {
                        $q->where('status', 2); // No successful disbursement yet (status 2 = Disbursed)
                    });

        // Group loans query
        $groupLoansQuery = GroupLoan::where('status', 1) // Approved loans
                    ->whereDoesntHave('disbursements', function($q) {
                        $q->where('status', 2); // No successful disbursement yet (status 2 = Disbursed)
                    });

        // Apply filters to both queries
        if ($request->filled('search')) {
            $search = $request->get('search');
            
            $personalLoansQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($q) use ($search) {
                      $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhere('contact', 'like', "%{$search}%");
                  });
            });

            $groupLoansQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('group', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('branch')) {
            $personalLoansQuery->where('branch_id', $request->get('branch'));
            $groupLoansQuery->where('branch_id', $request->get('branch'));
        }

        if ($request->filled('product')) {
            $personalLoansQuery->where('product_type', $request->get('product'));
            $groupLoansQuery->where('product_type', $request->get('product'));
        }

        // Get both types separately to preserve relationships
        $personalLoans = $personalLoansQuery->with(['member', 'branch', 'product', 'product.charges'])->get()->map(function($loan) {
            $loan->loan_type = 'personal';
            return $loan;
        });

        $groupLoans = $groupLoansQuery->with(['group', 'branch', 'product', 'product.charges'])->get()->map(function($loan) {
            $loan->loan_type = 'group';
            return $loan;
        });

        // Merge and sort
        $allLoans = $personalLoans->merge($groupLoans)->sortByDesc('datecreated');

        // Filter loans - Only include those ready for disbursement (STRICT VALIDATION)
        $readyLoans = $allLoans->filter(function($loan) {
            // If charge_type = 1, charges are deducted from disbursement - always ready
            if ($loan->charge_type == 1) {
                return true;
            }
            
            // If charge_type = 2, ALL upfront charges MUST be paid before disbursement
            if ($loan->charge_type == 2) {
                if (!$loan->product) {
                    return false; // No product = can't verify fees
                }
                
                $memberId = $loan->loan_type === 'personal' ? $loan->member_id : null;
                
                // Get product charges (mandatory upfront fees)
                $productCharges = $loan->product->charges()->where('isactive', 1)->get();
                
                // Check if ALL charges are paid
                foreach ($productCharges as $charge) {
                    $paidFee = \App\Models\Fee::where('loan_id', $loan->id)
                                  ->where('fees_type_id', $charge->id)
                                  ->where('status', 1)
                                  ->first();
                    
                    // For registration fees, also check member-level payment
                    $isRegFee = stripos($charge->name, 'registration') !== false;
                    if ($isRegFee && !$paidFee && $memberId) {
                        $paidFee = \App\Models\Fee::where('member_id', $memberId)
                                      ->where('fees_type_id', $charge->id)
                                      ->where('status', 1)
                                      ->first();
                    }
                    
                    // If ANY mandatory fee is unpaid, loan cannot be disbursed
                    if (!$paidFee) {
                        return false;
                    }
                }
                
                return true; // All mandatory fees are paid - ready for disbursement
            }
            
            return true; // Default: include the loan
        });

        // Paginate the filtered results
        $perPage = 20;
        $currentPage = request()->get('page', 1);
        $pagedData = $readyLoans->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $readyLoans->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get filter options
        $branches = Branch::active()->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();

        // Calculate stats for both personal and group loans
        $personalPendingLoans = PersonalLoan::where('status', 1)
                           ->whereDoesntHave('disbursements', function($q) {
                               $q->where('status', 2); // Status 2 = Disbursed (successful)
                           });
        
        $groupPendingLoans = GroupLoan::where('status', 1)
                           ->whereDoesntHave('disbursements', function($q) {
                               $q->where('status', 2); // Status 2 = Disbursed (successful)
                           });
        
        $stats = [
            'total_pending' => $personalPendingLoans->count() + $groupPendingLoans->count(),
            'total_amount' => $personalPendingLoans->sum('principal') + $groupPendingLoans->sum('principal'),
            'pending_today' => $personalPendingLoans->whereDate('datecreated', today())->count() +
                              $groupPendingLoans->whereDate('datecreated', today())->count(),
        ];

        return view('admin.loans.disbursements.pending', compact('loans', 'branches', 'products', 'stats'));
    }

    /**
     * Show the approve disbursement form
     */
    public function showApprove($id)
    {
        // Try to find the loan in PersonalLoan first, then GroupLoan
        $loan = PersonalLoan::with(['member', 'branch', 'product'])
                   ->where('status', 1) // Approved
                   ->whereDoesntHave('disbursements', function($q) {
                       $q->where('status', 2); // No successful disbursement (status 2 = Disbursed)
                   })
                   ->find($id);
        
        $loanType = 'personal';
        
        if (!$loan) {
            $loan = GroupLoan::with(['group', 'branch', 'product'])
                       ->where('status', 1) // Approved
                       ->whereDoesntHave('disbursements', function($q) {
                           $q->where('status', 2); // No successful disbursement (status 2 = Disbursed)
                       })
                       ->find($id);
            $loanType = 'group';
        }
        
        if (!$loan) {
            abort(404, 'Loan not found');
        }

        // Calculate net disbursement amount
        $chargeCalculation = $this->calculateAllChargesAndDeductions($loan);
        $loan->disbursement_amount = $chargeCalculation['disbursable_amount'];
        
        // Set borrower name based on loan type
        if ($loanType === 'personal') {
            $loan->borrower_name = $loan->member->fname . ' ' . $loan->member->lname;
            $loan->phone_number = $loan->member->contact;
        } else {
            $loan->borrower_name = $loan->group->name ?? 'Group Loan';
            $loan->phone_number = $loan->group->contact ?? '';
        }
        
        $loan->branch_name = $loan->branch->name ?? null;
        $loan->product_name = $loan->product->name ?? null;
        $loan->loan_code = $loan->code;
        $loan->principal_amount = $loan->principal;
        $loan->interest_rate = $loan->interest ?? 0;
        $loan->loan_term = $loan->period;
        $loan->period_type = $this->getPeriodTypeName($loan->period_type ?? 1);
        $loan->processing_fee = $chargeCalculation['total_deductions'] ?? 0;
        $loan->loan_type = $loanType;
        $loan->created_at = $loan->datecreated ?? now();
        
        // Get system users (staff members) for assignment - status is 'active' not 1
        $staff_members = User::where('status', 'active')
                           ->orderBy('name')
                           ->get();
        
        // Get investment accounts
        $investment_accounts = DB::table('investment')
                                ->orderBy('name')
                                ->get();
        
        // Set current logged-in user as default
        $loan->assigned_to = auth()->id();

        return view('admin.loans.disbursements.approve', compact('loan', 'staff_members', 'investment_accounts'));
    }

    /**
     * Process loan disbursement approval (new UI method)
     */
    public function approve(Request $request, $id)
    {
        // Only Super Administrator can disburse loans
        if (!auth()->user()->hasRole('Super Administrator') && !auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Access denied. Only Super Administrator can disburse loans.');
        }

        $validator = Validator::make($request->all(), [
            'disbursement_date' => 'required|date|before_or_equal:today',
            'payment_type' => 'required|in:mobile_money,bank_transfer,cash,cheque',
            'account_number' => 'required|string|max:50',
            'network' => 'required_if:payment_type,mobile_money|in:MTN,AIRTEL',
            'investment_id' => 'required|exists:investment,id',
            'assigned_to' => 'nullable|exists:users,id',
            'comments' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Try to find loan in PersonalLoan first, then GroupLoan
        $loan = PersonalLoan::with(['member', 'branch', 'product'])
                   ->where('status', 1)
                   ->find($id);
        
        $loanType = 1; // Personal loan
        
        if (!$loan) {
            $loan = GroupLoan::with(['group', 'branch', 'product'])
                       ->where('status', 1)
                       ->find($id);
            $loanType = 2; // Group loan
        }
        
        if (!$loan) {
            return redirect()->route('admin.loans.disbursements.pending')
                ->with('error', 'Loan not found or not approved for disbursement.');
        }

        // Check if loan already has a SUCCESSFUL disbursement (status = 2)
        // Allow retry if previous disbursement failed (status = 0 or 1)
        $existingDisbursement = DB::table('disbursements')
            ->where('loan_id', $loan->id)
            ->where('loan_type', $loanType)
            ->where('status', 2) // Only block if successfully disbursed
            ->first();

        if ($existingDisbursement) {
            return redirect()->route('admin.loans.disbursements.pending')
                ->with('error', 'This loan has already been disbursed successfully. Cannot disburse again.');
        }

        DB::beginTransaction();

        try {
            // Only validate mandatory fees if charge_type = 2 (upfront payment) AND it's a personal loan
            // If charge_type = 1, charges are deducted from disbursement, so no need to check
            if ($loan->charge_type == 2 && $loanType == 1) {
                // 1. Check one-time mandatory fees (registration, etc.)
                $mandatoryValidation = $this->validateMandatoryFees($loan->member);
                if (!$mandatoryValidation['valid']) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Mandatory fees required: ' . $mandatoryValidation['message'])
                        ->withInput();
                }
                
                // 2. Check that ALL upfront product charges have been paid
                $upfrontChargesValidation = $this->validateUpfrontChargesPaid($loan);
                if (!$upfrontChargesValidation['valid']) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Upfront charges not paid: ' . $upfrontChargesValidation['message'])
                        ->withInput();
                }
            }

            $chargeCalculation = $this->calculateAllChargesAndDeductions($loan);
            if (!$chargeCalculation['valid']) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', $chargeCalculation['message'])
                    ->withInput();
            }

            $disbursementAmount = $chargeCalculation['disbursable_amount'];

            // Update loan assignment
            if ($request->filled('assigned_to')) {
                $loan->assigned_to = $request->assigned_to;
                $loan->save();
            }

            // Convert payment_type to legacy format
            $paymentTypeMapping = [
                'mobile_money' => 1,
                'cheque' => 2,
                'bank_transfer' => 2,
                'cash' => 2
            ];

            // Convert network to payment_medium
            $paymentMedium = null;
            if ($request->payment_type === 'mobile_money') {
                $paymentMedium = $request->network === 'AIRTEL' ? 1 : 2; // 1=Airtel, 2=MTN
            }

            // Get account name based on loan type
            $accountName = $loanType == 1 
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Group Loan');
            
            $disbursementData = [
                'loan_id' => $loan->id,
                'loan_type' => $loanType,
                'amount' => $disbursementAmount,
                'comments' => $request->filled('comments') ? substr($request->comments, 0, 100) : null,
                'payment_type' => $paymentTypeMapping[$request->payment_type],
                'account_name' => $accountName,
                'account_number' => $request->account_number,
                'inv_id' => $request->investment_id,
                'medium' => $paymentMedium,
                'status' => 0, // Pending
                'added_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Create disbursement record
            $disbursement = Disbursement::create($disbursementData);

            // Record all charges and auto-deducted fees
            $this->recordAllCharges($loan, $chargeCalculation);

            // Handle mobile money disbursement
            if ($request->payment_type === 'mobile_money') {
                $mobileMoneyResult = $this->processNewMobileMoneyDisbursement(
                    $disbursement,
                    $request->account_number,
                    $request->network
                );

                // Check if it's a timeout error
                $isTimeout = isset($mobileMoneyResult['message']) && 
                            (strpos($mobileMoneyResult['message'], 'cURL error 28') !== false ||
                             strpos($mobileMoneyResult['message'], 'timed out') !== false ||
                             strpos($mobileMoneyResult['message'], 'Operation timed out') !== false);

                if ($mobileMoneyResult['success']) {
                    // If mobile money API call succeeded, complete the disbursement immediately
                    // This updates loan status and generates schedules
                    $disbursement->update(['status' => 1]); // Processing
                    $this->completeDisbursement($disbursement); // Updates to status 2 and completes
                } elseif ($isTimeout) {
                    // Timeout doesn't mean failure - money likely sent successfully
                    // Complete the disbursement but mark as "pending confirmation"
                    $disbursement->update([
                        'status' => 1, // Processing (will be confirmed by webhook/cron)
                        'notes' => ($disbursement->notes ? $disbursement->notes . "\n" : '') . 
                                  'Payment initiated but API timed out. Awaiting confirmation.'
                    ]);
                    $this->completeDisbursement($disbursement); // Complete anyway - money was likely sent
                    
                    DB::commit();
                    
                    Log::warning('Disbursement completed despite timeout', [
                        'loan_id' => $loan->id,
                        'disbursement_id' => $disbursement->id,
                        'message' => $mobileMoneyResult['message']
                    ]);
                    
                    return redirect()->route('admin.loans.active')
                        ->with('warning', 'Loan disbursed successfully. Mobile money payment initiated but confirmation pending due to slow network response. Check disbursement transactions for confirmation.');
                } else {
                    // Actual failure - rollback
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Mobile money disbursement failed: ' . $mobileMoneyResult['message'])
                        ->withInput();
                }
            } else {
                // For non-mobile money, mark as completed immediately and process
                $result = $this->processChequeDisburstement($disbursement, $request->account_number);
                if ($result['success']) {
                    $disbursement->update(['status' => 1]);
                    $this->completeDisbursement($disbursement);
                }
            }

            DB::commit();

            Log::info('Loan disbursement approved via new UI', [
                'loan_id' => $loan->id,
                'loan_code' => $loan->code,
                'amount' => $disbursementAmount,
                'payment_type' => $request->payment_type,
                'approved_by' => auth()->id(),
            ]);

            $message = "Loan {$loan->code} disbursement approved successfully.";
            if ($request->payment_type === 'mobile_money') {
                $message .= " Mobile money transfer initiated.";
            }

            return redirect()->route('admin.loans.active')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Loan disbursement approval failed', [
                'loan_id' => $loan->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Disbursement approval failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process mobile money disbursement using the new MobileMoneyService
     */
    protected function processNewMobileMoneyDisbursement($disbursement, $phoneNumber, $network)
    {
        // Get member name from disbursement account_name field
        $memberName = $disbursement->account_name;
        
        // Get loan code - fetch the actual loan
        $loan = $disbursement->loan_type == 1 
            ? PersonalLoan::find($disbursement->loan_id)
            : GroupLoan::find($disbursement->loan_id);
        
        // Generate unique transaction reference BEFORE API call
        $txnRef = 'TXN-' . $disbursement->loan_id . '-' . time();
        
        // STEP 1: Create transaction record BEFORE making API call
        // This ensures we always have a record even if API times out
        $transaction = DisbursementTransaction::create([
            'loan_id' => $disbursement->loan_id,
            'phone' => $phoneNumber,
            'amount' => $disbursement->amount,
            'status' => '00', // Pending
            'txnref' => $txnRef,
            'message' => 'Initiating disbursement...',
            'datecreated' => now(),
            'dump' => json_encode(['status' => 'initiating']),
            'raw' => '',
            'request' => json_encode([
                'phone' => $phoneNumber,
                'network' => $network,
                'amount' => $disbursement->amount,
                'member_name' => $memberName,
            ]),
        ]);
        
        Log::info('Disbursement transaction record created', [
            'transaction_id' => $transaction->id,
            'loan_id' => $loan->id,
            'loan_code' => $loan->code,
            'amount' => $disbursement->amount
        ]);
        
        try {
            // STEP 2: Make API call to send money
            $result = $this->mobileMoneyService->sendMoney(
                $memberName,
                $phoneNumber,
                $disbursement->amount,
                "Loan disbursement for {$loan->code}"
            );
            
            // STEP 3: Update transaction with actual API response
            $transaction->update([
                'status' => $result['status_code'] ?? $result['status'] ?? '00',
                'txnref' => $result['reference'] ?? $txnRef,
                'message' => $result['message'] ?? 'Disbursement processed',
                'dump' => json_encode($result),
                'raw' => $result['raw_response'] ?? json_encode($result),
            ]);
            
            // STEP 4: Create raw payment record for tracking
            try {
                RawPayment::create([
                    'trans_id' => $result['reference'] ?? $txnRef,
                    'amount' => $disbursement->amount,
                    'phone' => $phoneNumber,
                    'status' => $result['status_code'] ?? $result['status'] ?? '00',
                    'type' => 'disbursement',
                    'message' => $result['message'] ?? 'Disbursement initiated',
                    'direction' => 'outgoing',
                    'added_by' => auth()->id(),
                    'date_created' => now(),
                ]);
            } catch (\Exception $e) {
                // Don't fail if raw payment creation fails
                Log::warning('Could not create raw payment record', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Consider it successful if status is 00 (success) or 01 (processing)
            // or if the message indicates money is being processed
            $isSuccessful = $result['success'] || 
                           in_array($result['status_code'] ?? $result['status'] ?? '', ['00', '01']) ||
                           stripos($result['message'] ?? '', 'processing') !== false ||
                           stripos($result['message'] ?? '', 'received') !== false;
            
            if ($isSuccessful) {
                Log::info('Mobile money disbursement successful/processing', [
                    'loan_id' => $loan->id,
                    'transaction_id' => $transaction->id,
                    'status' => $result['status_code'] ?? $result['status'] ?? '00',
                    'message' => $result['message'] ?? ''
                ]);
                
                return [
                    'success' => true,
                    'message' => $result['message'] ?? 'Mobile money disbursement initiated successfully',
                    'transaction_id' => $result['reference'] ?? $txnRef,
                    'transaction_record_id' => $transaction->id,
                ];
            } else {
                Log::warning('Mobile money disbursement failed', [
                    'loan_id' => $loan->id,
                    'transaction_id' => $transaction->id,
                    'status' => $result['status_code'] ?? $result['status'] ?? '',
                    'message' => $result['message'] ?? ''
                ]);
                
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Mobile money transfer failed',
                    'transaction_record_id' => $transaction->id,
                ];
            }
            
        } catch (\Exception $e) {
            // STEP 5: Update transaction with error details
            $transaction->update([
                'status' => 'ERROR',
                'message' => 'API Error: ' . $e->getMessage(),
                'dump' => json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]),
            ]);
            
            Log::error('Mobile money disbursement exception', [
                'disbursement_id' => $disbursement->id,
                'transaction_id' => $transaction->id,
                'loan_id' => $loan->id,
                'phone' => $phoneNumber,
                'network' => $network,
                'error' => $e->getMessage()
            ]);
            
            // Check if it's a timeout error - money might have been sent
            $isTimeout = strpos($e->getMessage(), 'timed out') !== false ||
                        strpos($e->getMessage(), 'cURL error 28') !== false;
            
            if ($isTimeout) {
                // For timeout, assume money was sent and treat as successful
                return [
                    'success' => true,
                    'message' => 'Payment initiated but confirmation timed out. Transaction saved for verification.',
                    'transaction_record_id' => $transaction->id,
                    'is_timeout' => true,
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Mobile money service error: ' . $e->getMessage(),
                'transaction_record_id' => $transaction->id,
            ];
        }
    }

    /**
     * Helper method to get period type name
     */
    protected function getPeriodTypeName($periodType)
    {
        $types = [
            1 => 'weeks',
            2 => 'months',
            3 => 'days'
        ];
        
        return $types[$periodType] ?? 'periods';
    }

    /**
     * Show the form for creating a new disbursement
     */
    public function create(Request $request)
    {
        // Get approved loans that haven't been disbursed yet (only for approved members)
        $loans = Loan::with(['member', 'product', 'branch'])
                    ->where('status', 1) // Approved but not disbursed
                    ->whereDoesntHave('disbursements', function($query) {
                        $query->where('status', 1); // No successful disbursement
                    })
                    ->whereHas('member', function($query) {
                        $query->where('status', 'approved'); // Only approved members
                    })
                    ->get();

        $investments = Investment::where('status', 1)->get();
        $users = \App\Models\User::where('status', 'active')->get(); // Staff for assignment - status is 'active' not 1

        // Pre-select loan if passed
        $selectedLoan = null;
        if ($request->has('loan_id')) {
            $selectedLoan = Loan::with(['member', 'product'])->find($request->loan_id);
        }

        return view('admin.disbursements.create', compact('loans', 'investments', 'users', 'selectedLoan'));
    }

    /**
     * Store a newly created disbursement
     */
    public function store(Request $request)
    {
        // Only Super Administrator can disburse loans
        if (!auth()->user()->hasRole('Super Administrator') && !auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Access denied. Only Super Administrator can disburse loans.');
        }

        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'disbursement_date' => 'required|date',
            'payment_type' => 'required|integer|in:1,2', // 1=Mobile Money, 2=Cheque
            'payment_medium' => 'required_if:payment_type,1|integer|in:1,2', // 1=Airtel, 2=MTN
            'account_number' => 'required|string|max:20', // Phone for MM, Account for cheque
            'investment_id' => 'required|exists:investments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::with(['member', 'product'])->find($validated['loan_id']);

            // Verify loan is approved and not already disbursed
            if ($loan->status !== 1) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'Loan must be approved before disbursement.');
            }

            // CRITICAL: Validate that the member is approved before disbursement
            if (!$loan->member->isApproved()) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'Disbursement denied: Member ' . $loan->member->fname . ' ' . $loan->member->lname . 
                                       ' is not approved. Only approved members can receive loan disbursements. Current status: ' . 
                                       $loan->member->status_display);
            }

            // Check if already has successful disbursement
            $existingDisbursement = Disbursement::where('loan_id', $loan->id)
                                               ->whereIn('status', [1, 2]) // Approved or Disbursed
                                               ->first();
            
            if ($existingDisbursement) {
                // Check if there's a pending/successful transaction
                $existingTxn = DisbursementTransaction::where('disbursement_id', $existingDisbursement->id)
                                                      ->first();
                
                $statusMessage = 'Loan has already been disbursed.';
                
                if ($existingTxn) {
                    $txnStatus = match($existingTxn->status) {
                        '00' => 'Transaction is PENDING - waiting for customer to approve on their phone',
                        '01' => 'Transaction SUCCESSFUL - money already sent',
                        '02', '57' => 'Previous transaction FAILED - contact admin to retry',
                        default => 'Transaction status: ' . $existingTxn->status
                    };
                    
                    $statusMessage .= ' ' . $txnStatus . ' (Ref: ' . $existingTxn->txn_reference . ')';
                }
                
                return redirect()->back()
                                ->withInput()
                                ->with('error', $statusMessage);
            }

            // CRITICAL: Validate all mandatory fees are paid (CANNOT BE AUTO-DEDUCTED)
            $mandatoryFeeValidation = $this->validateMandatoryFees($loan->member);
            if (!$mandatoryFeeValidation['valid']) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'MANDATORY FEES REQUIRED: ' . $mandatoryFeeValidation['message'] . 
                                       ' These fees cannot be auto-deducted and must be paid before any loan disbursement.');
            }

            // CRITICAL: Calculate ALL charges (upfront + product charges) and disbursable amount
            $chargeCalculation = $this->calculateAllChargesAndDeductions($loan);
            if (!$chargeCalculation['valid']) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', $chargeCalculation['message']);
            }

            // Check investment account balance (for disbursable amount, not original principal)
            $investment = Investment::find($validated['investment_id']);
            if ($investment->amount < $chargeCalculation['disbursable_amount']) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'Insufficient funds in investment account. Required: ' . 
                                       number_format($chargeCalculation['disbursable_amount'], 2) . 
                                       ', Available: ' . number_format($investment->amount, 2));
            }

            // Generate disbursement code
            $branch = Branch::find($loan->branch_id);
            $disbursementCount = Disbursement::count();
            $code = 'DISB-' . $branch->code . '-' . date('Ymd') . '-' . str_pad($disbursementCount + 1, 4, '0', STR_PAD_LEFT);

            // Create disbursement record
            $disbursement = Disbursement::create([
                'loan_id' => $loan->id,
                'code' => $code,
                'amount' => $chargeCalculation['disbursable_amount'], // Amount after ALL deductions
                'disbursement_date' => $validated['disbursement_date'],
                'payment_type' => $validated['payment_type'],
                'payment_medium' => $validated['payment_medium'] ?? null,
                'account_number' => $validated['account_number'],
                'investment_id' => $validated['investment_id'],
                'assigned_to' => $validated['assigned_to'],
                'notes' => $validated['notes'] . "\n\n" . $chargeCalculation['detailed_breakdown'],
                'status' => 0, // Pending
                'added_by' => auth()->id(),
            ]);

            // Record all charges and auto-deducted fees
            $this->recordAllCharges($loan, $chargeCalculation);

            // Process disbursement based on payment type
            if ($validated['payment_type'] == 1) {
                // Mobile Money disbursement via FlexiPay
                $result = $this->processMobileMoneyDisbursement($disbursement, $validated['account_number'], $validated['payment_medium']);
            } else {
                // Cheque disbursement
                $result = $this->processChequeDisburstement($disbursement, $validated['account_number']);
            }

            if ($result['success']) {
                // Complete disbursement if immediately successful
                if (isset($result['immediate_success']) && $result['immediate_success']) {
                    $this->completeDisbursement($disbursement); // This updates status to 2 internally
                }

                DB::commit();

                return redirect()->route('admin.disbursements.show', $disbursement)
                                ->with('success', $result['message']);
            } else {
                DB::rollback();
                
                return redirect()->back()
                                ->withInput()
                                ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Disbursement creation failed: ' . $e->getMessage());
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified disbursement
     */
    public function show(Disbursement $disbursement)
    {
        $disbursement->load([
            'loan.member',
            'loan.product',
            'loan.branch',
            'investment',
            'addedBy',
            'assignedTo',
            'transaction',
            'rawPayment'
        ]);

        return view('admin.disbursements.show', compact('disbursement'));
    }

    /**
     * Process mobile money disbursement via Stanbic FlexiPay
     */
    private function processMobileMoneyDisbursement(Disbursement $disbursement, $phoneNumber, $paymentMedium)
    {
        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
            
            // Determine network
            $network = null;
            if ($paymentMedium == 1) {
                $network = 'AIRTEL';
            } elseif ($paymentMedium == 2) {
                $network = 'MTN';
            }

            // Get member name for disbursement
            $memberName = $disbursement->loan && $disbursement->loan->member ? 
                         $disbursement->loan->member->fname . ' ' . $disbursement->loan->member->lname : 
                         'Beneficiary';

            // Check if this disbursement already has a pending/successful transaction
            $existingTxn = DisbursementTransaction::where('disbursement_id', $disbursement->id)
                                                  ->whereIn('status', ['00', '01']) // Pending or Successful
                                                  ->first();
            
            if ($existingTxn) {
                Log::warning('Disbursement already has pending/successful transaction', [
                    'disbursement_id' => $disbursement->id,
                    'existing_txn_id' => $existingTxn->id,
                    'existing_status' => $existingTxn->status
                ]);
                
                $statusMessage = match($existingTxn->status) {
                    '00' => 'TRANSACTION PENDING: A disbursement request is already sent and waiting for customer approval on their phone. Ref: ' . $existingTxn->txn_reference,
                    '01' => 'TRANSACTION SUCCESSFUL: Money has already been sent to ' . $existingTxn->phone . '. Ref: ' . $existingTxn->txn_reference,
                    default => 'Transaction already in progress. Ref: ' . $existingTxn->txn_reference
                };
                
                return [
                    'success' => false,
                    'message' => $statusMessage,
                    'transaction_id' => $existingTxn->txn_reference,
                    'status' => $existingTxn->status
                ];
            }

            // Generate unique request ID before API call to enable idempotent retries
            $requestId = 'EbP' . time() . substr(microtime(false), 2, 6) . mt_rand(100, 999);
            
            Log::info('Processing disbursement via MobileMoneyService', [
                'disbursement_id' => $disbursement->id,
                'phone' => $normalizedPhone,
                'network' => $network,
                'amount' => $disbursement->amount,
                'beneficiary' => $memberName,
                'request_id' => $requestId
            ]);

            // Create transaction record BEFORE API call to track request and prevent duplicates
            $disbursementTxn = DisbursementTransaction::create([
                'disbursement_id' => $disbursement->id,
                'loan_id' => $disbursement->loan_id,
                'txn_reference' => $requestId,
                'network' => $network,
                'phone' => $normalizedPhone,
                'amount' => $disbursement->amount,
                'status' => '00', // Pending
                'message' => 'Initiated',
            ]);

            // Use MobileMoneyService for disbursement (will use Stanbic FlexiPay)
            $result = $this->mobileMoneyService->disburse(
                $normalizedPhone,
                $disbursement->amount,
                $network,
                $memberName,
                $requestId  // Pass request ID for idempotent retries
            );

            Log::info('MobileMoneyService disbursement result', [
                'result' => $result
            ]);

            if ($result['success']) {
                // Create raw payment record
                RawPayment::create([
                    'txn_id' => $result['reference'] ?? $requestId,
                    'amount' => $disbursement->amount,
                    'phone_number' => $normalizedPhone,
                    'status' => $result['status_code'] ?? '00',
                    'type' => 'disbursement',
                    'loan_id' => $disbursement->loan_id,
                    'disbursement_id' => $disbursement->id,
                ]);

                // Update disbursement transaction record with response
                $disbursementTxn->update([
                    'status' => $result['status_code'] ?? '00',
                    'message' => $result['message'] ?? 'Success',
                    'response_data' => json_encode($result),
                ]);

                // Check if immediately successful
                $immediateSuccess = isset($result['status_code']) && $result['status_code'] === '00';

                return [
                    'success' => true,
                    'message' => $immediateSuccess ? 
                        'Disbursement initiated successfully via ' . ($result['network'] ?? $network) . '. Transaction processing.' : 
                        'Disbursement initiated via ' . ($result['network'] ?? $network) . '. Transaction pending confirmation.',
                    'immediate_success' => $immediateSuccess,
                    'transaction_id' => $result['reference'] ?? null,
                    'provider' => $result['provider'] ?? 'stanbic'
                ];

            } else {
                // Update transaction record with failure
                if (isset($disbursementTxn)) {
                    $disbursementTxn->update([
                        'status' => '02', // Failed
                        'message' => $result['message'] ?? 'Failed',
                    ]);
                }
                
                Log::error('Mobile money disbursement failed', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'result' => $result
                ]);
                
                return [
                    'success' => false,
                    'message' => '❌ TRANSACTION FAILED: ' . ($result['message'] ?? 'Failed to initiate mobile money disbursement. Please try again.')
                ];
            }

        } catch (\Exception $e) {
            // Update transaction record with error if exists
            if (isset($disbursementTxn)) {
                $disbursementTxn->update([
                    'status' => '02', // Failed
                    'message' => 'Exception: ' . substr($e->getMessage(), 0, 70),
                ]);
            }
            
            Log::error('Mobile money disbursement error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => '❌ TRANSACTION ERROR: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process cheque disbursement
     */
    private function processChequeDisburstement(Disbursement $disbursement, $accountNumber)
    {
        try {
            // Create disbursement transaction record for cheque
            DisbursementTransaction::create([
                'disbursement_id' => $disbursement->id,
                'txn_reference' => 'CHQ-' . $disbursement->code,
                'network' => 'CHEQUE',
                'account_number' => $accountNumber,
                'status' => '01', // Cheques are considered successful immediately
            ]);

            return [
                'success' => true,
                'message' => 'Cheque disbursement recorded successfully.',
                'immediate_success' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Cheque disbursement error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error processing cheque disbursement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete disbursement and update loan status
     */
    private function completeDisbursement(Disbursement $disbursement)
    {
        try {
            // Update disbursement status to disbursed
            $disbursement->update(['status' => 2]);

            // Get the actual loan model based on loan_type
            $loan = $disbursement->loan_type == 1 
                ? PersonalLoan::find($disbursement->loan_id)
                : GroupLoan::find($disbursement->loan_id);

            if (!$loan) {
                throw new \Exception('Loan not found for disbursement ' . $disbursement->id);
            }

            // Update loan status to active/disbursed (status 2)
            DB::table($disbursement->loan_type == 1 ? 'personal_loans' : 'group_loans')
                ->where('id', $loan->id)
                ->update(['status' => 2]);

            // Generate or recalculate loan repayment schedules
            $this->generateRepaymentSchedules($loan, $disbursement);

            // Deduct from investment account if exists
            if ($disbursement->inv_id) {
                DB::table('investment')
                    ->where('id', $disbursement->inv_id)
                    ->decrement('amount', $disbursement->amount);
            }

            Log::info('Disbursement completed successfully', [
                'disbursement_id' => $disbursement->id,
                'loan_id' => $loan->id,
                'loan_type' => $disbursement->loan_type,
                'amount' => $disbursement->amount
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing disbursement', [
                'disbursement_id' => $disbursement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate repayment schedules for disbursed loan
     */
    private function generateRepaymentSchedules($loan, $disbursement)
    {
        // Check if schedules already exist
        $existingSchedules = DB::table('loan_schedules')
            ->where('loan_id', $loan->id)
            ->count();

        if ($existingSchedules > 0) {
            // Schedules exist, just recalculate dates
            $this->recalculateLoanSchedule($loan, $disbursement->disbursement_date);
            return;
        }

        // Generate new schedules using BIMSADMIN DECLINING BALANCE interest calculation
        $principal = floatval($loan->principal);
        $interestRate = floatval($loan->interest) / 100; // Convert to decimal (e.g., 0.7% = 0.007)
        $period = intval($loan->period);
        $periodType = $loan->period_type ?? 3; // Default to daily
        
        $disbursementDate = \Carbon\Carbon::parse($disbursement->disbursement_date);
        
        // Calculate principal per installment (flat/equal installments)
        $principalPerInstallment = $principal / $period;
        $balance = $principal;
        
        // BIMSADMIN DECLINING BALANCE INTEREST LOGIC (CORRECT):
        // Interest is calculated on the REMAINING principal balance AFTER each payment
        // Example: 10,000 loan, 2 periods, 0.7% interest
        // Period 1: Balance = 10,000 → Interest = 10,000 × 0.007 = 70
        // Period 2: Balance = 5,000 → Interest = 5,000 × 0.007 = 35
        // Total Interest: 105
        
        $remainingPrincipal = $principal; // Tracks remaining principal balance for interest calculation
        
        // Calculate all installments first to get total
        $installments = [];
        $tempRemainingPrincipal = $remainingPrincipal;
        $tempBalance = $principal;
        $totalScheduledPayment = 0;
        
        for ($i = 1; $i <= $period; $i++) {
            // Interest calculated on REMAINING principal balance (before this installment's principal payment)
            $interestAmount = $tempRemainingPrincipal * $interestRate;
            
            $principalAmount = $principalPerInstallment;
            $installmentPayment = $principalAmount + $interestAmount;
            
            // Update balance after principal payment
            $tempBalance -= $principalAmount;
            if ($tempBalance < 0) {
                $principalAmount += $tempBalance;
                $tempBalance = 0;
            }
            
            $installments[] = [
                'principal' => $principalAmount,
                'interest' => $interestAmount,
                'payment' => $installmentPayment,
                'balance' => $tempBalance
            ];
            
            $totalScheduledPayment += $installmentPayment;
            
            // Reduce remaining principal for next period's interest calculation
            $tempRemainingPrincipal -= $principalAmount;
            if ($tempRemainingPrincipal < 0) $tempRemainingPrincipal = 0;
        }
        
        // No rounding difference needed with declining balance method
        // Interest naturally decreases as principal is paid off
        
        // Now insert all schedules
        $balance = $principal;
        for ($i = 0; $i < count($installments); $i++) {
            $installment = $installments[$i];
            $balance -= $installment['principal'];
            if ($balance < 0) $balance = 0;
            
            $paymentDate = $this->calculatePaymentDate($disbursementDate, $i + 1, $periodType);

            DB::table('loan_schedules')->insert([
                'loan_id' => $loan->id,
                'payment_date' => $paymentDate->format('d-m-Y'),
                'payment' => round($installment['payment'], 2),
                'interest' => round($installment['interest'], 2),
                'principal' => round($installment['principal'], 2),
                'balance' => round($balance, 2),
                'paid' => 0.00,
                'status' => 0,
                'pending_count' => 0,
                'date_created' => now(),
            ]);
        }

        Log::info('Generated loan schedules', [
            'loan_id' => $loan->id,
            'periods' => $period,
            'schedules_created' => $period
        ]);
    }

    /**
     * Recalculate loan schedule dates from disbursement date
     */
    private function recalculateLoanSchedule($loan, $disbursementDate)
    {
        $schedules = LoanSchedule::where('loan_id', $loan->id)
                                ->orderBy('payment_date')
                                ->get();

        $startDate = \Carbon\Carbon::parse($disbursementDate);
        
        foreach ($schedules as $index => $schedule) {
            $newPaymentDate = $this->calculatePaymentDate($startDate, $index + 1, $loan->period_type);
            
            $schedule->update(['payment_date' => $newPaymentDate]);
        }
    }

    /**
     * Calculate payment date based on period type
     */
    private function calculatePaymentDate($startDate, $periodNumber, $periodType)
    {
        switch ($periodType) {
            case 1: // Weekly - every Friday
                $nextFriday = $startDate->copy()->next(\Carbon\Carbon::FRIDAY);
                return $nextFriday->addWeeks($periodNumber - 1);
                
            case 2: // Monthly
                return $startDate->copy()->addMonths($periodNumber);
                
            case 3: // Daily - each day after disbursement, skip Sundays
                $date = $startDate->copy();
                $daysAdded = 0;
                
                // Add days for each period, skipping Sundays
                while ($daysAdded < $periodNumber) {
                    $date->addDay();
                    
                    // Skip Sundays
                    if (!$date->isSunday()) {
                        $daysAdded++;
                    }
                }
                
                return $date;
                
            default:
                return $startDate->copy()->addDays($periodNumber);
        }
    }

    /**
     * Normalize phone number to international format
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove leading zeros
        $phone = ltrim($phone, '0');
        
        // Add country code if missing (256 for Uganda)
        if (strlen($phone) == 9) {
            $phone = '256' . $phone;
        }
        
        return $phone;
    }

    /**
     * Detect mobile network based on phone number
     */
    private function detectNetwork($normalizedPhone)
    {
        // MTN prefixes
        if (preg_match('/^256(77|78|76)/', $normalizedPhone)) {
            return 'MTN';
        }
        
        // Airtel prefixes
        if (preg_match('/^256(70|75|74)/', $normalizedPhone)) {
            return 'AIRTEL';
        }
        
        return null;
    }

    /**
     * Send disbursement notification to member
     */
    private function sendDisbursementNotification($member, $disbursement)
    {
        // TODO: Implement SMS notification
        // This would use your SMS service to notify the member
        Log::info('Disbursement notification sent', [
            'member_id' => $member->id,
            'phone' => $member->contact,
            'amount' => $disbursement->amount
        ]);
    }

    /**
     * Check disbursement status (for pending mobile money transactions)
     */
    public function checkStatus(Disbursement $disbursement)
    {
        if ($disbursement->status !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Disbursement is not in pending status.'
            ]);
        }

        $transaction = $disbursement->transaction;
        if (!$transaction || !$transaction->txn_reference) {
            return response()->json([
                'success' => false,
                'message' => 'No transaction reference found.'
            ]);
        }

        try {
            // Call FlexiPay status check API
            $response = Http::timeout(30)->post(config('services.flexipay.base_url') . 'checkFromMMStatusProd.php', [
                'transactionId' => $transaction->txn_reference
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update transaction status
                $transaction->update([
                    'status' => $responseData['status'] ?? '00',
                    'response_data' => json_encode($responseData)
                ]);

                // Update raw payment status
                if ($disbursement->rawPayment) {
                    $disbursement->rawPayment->update([
                        'status' => $responseData['status'] ?? '00'
                    ]);
                }

                // If successful, complete the disbursement
                if (isset($responseData['status']) && $responseData['status'] === '01') {
                    $disbursement->update(['status' => 1]);
                    $this->completeDisbursement($disbursement);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Disbursement completed successfully.',
                        'status' => 'completed'
                    ]);
                } elseif (isset($responseData['status']) && $responseData['status'] === '02') {
                    // Failed
                    $disbursement->update(['status' => 2]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Disbursement failed.',
                        'status' => 'failed'
                    ]);
                } else {
                    // Still pending
                    return response()->json([
                        'success' => true,
                        'message' => 'Disbursement still pending.',
                        'status' => 'pending'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error checking disbursement status.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error checking disbursement status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking disbursement status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel a pending disbursement
     */
    public function cancel(Disbursement $disbursement)
    {
        if ($disbursement->status == 2) {
            return redirect()->back()
                            ->with('error', 'Cannot cancel a disbursement that has already been disbursed.');
        }

        if ($disbursement->status == 3) {
            return redirect()->back()
                            ->with('info', 'Disbursement is already cancelled/failed.');
        }

        try {
            DB::beginTransaction();

            $disbursement->update([
                'status' => 3, // Failed/Cancelled (not 2!)
                'notes' => ($disbursement->notes ?? '') . "\n\nCancelled by: " . auth()->user()->name . " on " . now()->format('Y-m-d H:i:s')
            ]);

            // Update transaction status if exists
            if ($disbursement->transaction) {
                $disbursement->transaction->update(['status' => '57']); // Failed status
            }

            // Update raw payment status if exists
            if ($disbursement->rawPayment) {
                $disbursement->rawPayment->update(['pay_status' => '57']); // Failed status
            }

            DB::commit();

            return redirect()->back()
                            ->with('success', 'Disbursement cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error cancelling disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Get loan details for AJAX with complete charge breakdown
     */
    public function getLoanDetails(Loan $loan)
    {
        try {
            $loan->load(['member', 'product']);

            // 1. Validate mandatory fees
            $mandatoryValidation = $this->validateMandatoryFees($loan->member);
            if (!$mandatoryValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mandatory Fee Validation Failed: ' . $mandatoryValidation['message']
                ]);
            }

            // 2. Calculate all charges and deductions
            $chargeCalculation = $this->calculateAllChargesAndDeductions($loan);
            if (!$chargeCalculation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Charge Calculation Failed: ' . $chargeCalculation['message']
                ]);
            }

            return response()->json([
                'success' => true,
                'loan' => [
                    'id' => $loan->id,
                    'code' => $loan->code,
                    'member_name' => $loan->member->fname . ' ' . $loan->member->lname,
                    'member_phone' => $loan->member->contact,
                    'product_name' => $loan->product->name,
                    'principal' => $loan->principal,
                    'interest' => $loan->interest,
                    'period' => $loan->period,
                    'period_type' => $loan->period_type,
                    'disbursable_amount' => $chargeCalculation['disbursable_amount'],
                    'total_deductions' => $chargeCalculation['total_deductions'],
                    'charge_breakdown' => $chargeCalculation['charge_breakdown'],
                    'detailed_breakdown' => $chargeCalculation['detailed_breakdown']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading loan details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validate that all mandatory fees are paid before disbursement
     * MANDATORY FEES CANNOT BE AUTO-DEDUCTED - MUST BE PAID SEPARATELY
     */
    private function validateMandatoryFees(Member $member)
    {
        // ONLY REGISTRATION FEE IS MANDATORY - paid once per member lifetime
        // All other fees (License, Affiliation, test fees, etc.) are optional
        
        // Check if member has any previous loans (first loan vs subsequent loans)
        $previousLoansCount = \App\Models\PersonalLoan::where('member_id', $member->id)
                                ->where('status', '>=', 1) // Approved or higher
                                ->count();
        
        $isFirstLoan = $previousLoansCount <= 1; // Current loan is the first

        // For subsequent loans, skip registration fee check (already paid once)
        if (!$isFirstLoan) {
            \Log::info("Skipping registration fee check for subsequent loan", [
                'member_id' => $member->id,
                'previous_loans' => $previousLoansCount
            ]);
            return ['valid' => true];
        }

        // For first loan, check if registration fee has been paid
        $registrationFee = FeeType::active()
                                  ->where('required_disbursement', 0)
                                  ->where(function($query) {
                                      $query->where('name', 'like', '%registration%')
                                            ->orWhere('name', 'like', '%Registration%');
                                  })
                                  ->first();

        if (!$registrationFee) {
            \Log::warning("Registration fee type not found in system", [
                'member_id' => $member->id
            ]);
            return ['valid' => true]; // No registration fee defined, allow disbursement
        }

        // Check if member has paid registration fee
        $paidRegistrationFee = Fee::where('member_id', $member->id)
                                  ->where('fees_type_id', $registrationFee->id)
                                  ->where('status', 1) // Paid
                                  ->first();

        if (!$paidRegistrationFee) {
            return [
                'valid' => false,
                'message' => 'Registration fee must be paid before first loan disbursement.'
            ];
        }

        \Log::info("Registration fee validated successfully", [
            'member_id' => $member->id,
            'fee_id' => $registrationFee->id,
            'amount_paid' => $paidRegistrationFee->amount
        ]);

        return ['valid' => true];
    }

    /**
     * Validate that all upfront product charges have been paid for this specific loan
     * Only applies when charge_type = 2 (upfront payment)
     */
    private function validateUpfrontChargesPaid($loan)
    {
        $product = $loan->product;
        if (!$product) {
            return ['valid' => false, 'message' => 'Product not found for this loan'];
        }

        // Get all active product charges
        $productCharges = \App\Models\ProductCharge::where('product_id', $product->id)
                                                   ->where('isactive', 1)
                                                   ->get();

        if ($productCharges->isEmpty()) {
            return ['valid' => true]; // No charges to validate
        }

        $unpaidCharges = [];

        foreach ($productCharges as $charge) {
            // Calculate expected charge amount
            $chargeAmount = 0;
            switch ($charge->type) {
                case 1: // Fixed
                    $chargeAmount = floatval($charge->value ?? 0);
                    break;
                case 2: // Percentage
                    $chargeAmount = ($loan->principal * floatval($charge->value ?? 0)) / 100;
                    break;
                case 3: // Per Day
                    $chargeAmount = floatval($charge->value ?? 0) * intval($loan->period ?? 0);
                    break;
                case 4: // Per Month
                    $months = ceil(intval($loan->period ?? 0) / 30);
                    $chargeAmount = floatval($charge->value ?? 0) * $months;
                    break;
            }

            // Check if this charge has been paid for this specific loan
            $paidFee = Fee::where('loan_id', $loan->id)
                         ->where('fees_type_id', $charge->id)
                         ->where('status', 1) // Paid
                         ->first();

            if (!$paidFee) {
                $unpaidCharges[] = $charge->name . ' (UGX ' . number_format($chargeAmount, 0) . ')';
                
                \Log::warning("Upfront charge not paid for loan", [
                    'loan_id' => $loan->id,
                    'charge_name' => $charge->name,
                    'charge_id' => $charge->id,
                    'expected_amount' => $chargeAmount
                ]);
            } else {
                \Log::info("Upfront charge verified as paid", [
                    'loan_id' => $loan->id,
                    'charge_name' => $charge->name,
                    'paid_amount' => $paidFee->amount,
                    'fee_id' => $paidFee->id
                ]);
            }
        }

        if (!empty($unpaidCharges)) {
            return [
                'valid' => false,
                'message' => implode(', ', $unpaidCharges) . 
                           '. All upfront charges must be paid before disbursement. Please go back to the loan page and pay these charges.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Calculate ALL charges (upfront + product) and determine final disbursable amount
     * Upfront charges can be auto-deducted if not already paid
     */
    private function calculateAllChargesAndDeductions($loan)
    {
        $product = $loan->product;
        $principal = floatval($loan->principal ?? 0);
        $totalDeductions = 0;
        $chargeBreakdown = [];
        $feeRecords = [];
        $loanChargeRecords = [];

        // Check charge_type: 1 = Deduct from disbursement, 2 = Pay upfront
        $chargeType = $loan->charge_type ?? 2; // Default to 2 if not set

        // 1. GET PRODUCT CHARGES - Only deduct if charge_type = 1
        $productCharges = ProductCharge::where('product_id', $product->id)
                                      ->active()
                                      ->get();

        foreach ($productCharges as $charge) {
            $chargeAmount = 0;

            switch ($charge->type) {
                case 1: // Fixed Amount
                    $chargeAmount = floatval($charge->value ?? 0);
                    break;
                    
                case 2: // Percentage
                    $chargeAmount = ($principal * floatval($charge->value ?? 0)) / 100;
                    break;
                    
                case 3: // Per Day
                    $chargeAmount = floatval($charge->value ?? 0) * intval($loan->period ?? 0);
                    break;
                    
                case 4: // Per Month
                    $months = ceil(intval($loan->period ?? 0) / 30);
                    $chargeAmount = floatval($charge->value ?? 0) * $months;
                    break;
                    
                default:
                    $chargeAmount = floatval($charge->value ?? 0);
            }

            if ($chargeType == 1) {
                // Deduct from disbursement
                $totalDeductions += $chargeAmount;
                $chargeBreakdown[] = $charge->name . ': UGX ' . number_format($chargeAmount, 2) . 
                                   ' (' . $charge->type_name . ' - Deducted from disbursement)';
                
                $loanChargeRecords[] = [
                    'charge_name' => $charge->name,
                    'charge_type' => $charge->type,
                    'charge_value' => $charge->value,
                    'actual_value' => $chargeAmount,
                    'deducted' => true
                ];
            } else {
                // charge_type = 2: Already paid upfront, no deduction
                $chargeBreakdown[] = $charge->name . ': UGX ' . number_format($chargeAmount, 2) . 
                                   ' (Paid upfront - Not deducted)';
                
                $loanChargeRecords[] = [
                    'charge_name' => $charge->name,
                    'charge_type' => $charge->type,
                    'charge_value' => $charge->value,
                    'actual_value' => $chargeAmount,
                    'deducted' => false
                ];
            }
        }

        $disbursableAmount = $principal - $totalDeductions;

        // Ensure disbursable amount is positive
        if ($disbursableAmount <= 0) {
            return [
                'valid' => false,
                'message' => 'Total charges (UGX ' . number_format($totalDeductions, 2) . 
                           ') exceed loan principal (UGX ' . number_format($principal, 2) . 
                           '). Please review loan amount or charges.'
            ];
        }

        return [
            'valid' => true,
            'disbursable_amount' => $disbursableAmount,
            'total_deductions' => $totalDeductions,
            'auto_deducted_fees' => $feeRecords,
            'loan_charges' => $loanChargeRecords,
            'charge_breakdown' => $chargeBreakdown,
            'detailed_breakdown' => "DISBURSEMENT BREAKDOWN:\n" .
                                  "═══════════════════════\n" .
                                  "Principal Amount: UGX " . number_format($principal, 2) . "\n\n" .
                                  "DEDUCTIONS:\n" .
                                  implode("\n", $chargeBreakdown) . "\n\n" .
                                  "Total Deductions: UGX " . number_format($totalDeductions, 2) . "\n" .
                                  "═══════════════════════\n" .
                                  "DISBURSABLE AMOUNT: UGX " . number_format($disbursableAmount, 2) . "\n" .
                                  "═══════════════════════"
        ];
    }

    /**
     * Record all charges and auto-deducted fees
     */
    private function recordAllCharges($loan, array $chargeCalculation)
    {
        // Record product charges and create Fee records if deducted
        foreach ($chargeCalculation['loan_charges'] as $chargeData) {
            LoanCharge::create([
                'loan_id' => $loan->id,
                'charge_name' => $chargeData['charge_name'],
                'charge_type' => $chargeData['charge_type'],
                'charge_value' => $chargeData['charge_value'],
                'actual_value' => $chargeData['actual_value'],
                'added_by' => auth()->id(),
            ]);

            // If this charge was deducted (charge_type = 1), create Fee record
            if (isset($chargeData['deducted']) && $chargeData['deducted']) {
                // Find the ProductCharge to get the fees_type_id
                $productCharge = ProductCharge::where('product_id', $loan->product_id)
                                             ->where('name', $chargeData['charge_name'])
                                             ->first();
                
                if ($productCharge) {
                    Fee::create([
                        'member_id' => $loan->member_id,
                        'loan_id' => $loan->id,
                        'fees_type_id' => $productCharge->id, // Using product_charge.id as fees_type_id
                        'payment_type' => 4, // Auto-deducted
                        'amount' => $chargeData['actual_value'],
                        'description' => 'Deducted from disbursement amount',
                        'added_by' => auth()->id(),
                        'payment_status' => 'Paid - Deducted from Disbursement',
                        'payment_description' => 'Automatically deducted during loan disbursement on ' . now()->format('Y-m-d H:i:s'),
                        'status' => 1, // Paid (via deduction)
                    ]);
                }
            }
        }
    }

    /**
     * Validate upfront charges are paid before disbursement
     */
    private function validateUpfrontCharges($loan)
    {
        // Get fee types that require disbursement (upfront charges)
        $upfrontFeeTypes = FeeType::active()
                                 ->where('required_disbursement', 1)
                                 ->get();

        $unpaidUpfrontFees = [];

        foreach ($upfrontFeeTypes as $feeType) {
            // Check if this loan has paid upfront fees
            $paidFee = Fee::where('loan_id', $loan->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1) // Paid
                         ->first();

            if (!$paidFee) {
                $unpaidUpfrontFees[] = $feeType->name;
            }
        }

        if (!empty($unpaidUpfrontFees)) {
            return [
                'valid' => false,
                'message' => 'Loan has unpaid upfront charges: ' . implode(', ', $unpaidUpfrontFees) . 
                           '. All upfront charges must be paid before disbursement.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get fee information for a loan (AJAX endpoint)
     */
    public function getLoanFeeInfo(Loan $loan)
    {
        // Validate mandatory fees
        $mandatoryValidation = $this->validateMandatoryFees($loan->member);
        
        // Validate upfront charges
        $upfrontValidation = $this->validateUpfrontCharges($loan);
        
        // Calculate product charges
        $chargeCalculation = $this->calculateProductCharges($loan);

        // Get fee breakdown
        $mandatoryFees = $this->getMandatoryFeesBreakdown($loan->member);
        $upfrontFees = $this->getUpfrontFeesBreakdown($loan);

        return response()->json([
            'success' => true,
            'loan' => [
                'principal' => $loan->principal,
                'code' => $loan->code,
            ],
            'mandatory_fees' => [
                'valid' => $mandatoryValidation['valid'],
                'message' => $mandatoryValidation['message'] ?? '',
                'breakdown' => $mandatoryFees
            ],
            'upfront_charges' => [
                'valid' => $upfrontValidation['valid'],
                'message' => $upfrontValidation['message'] ?? '',
                'breakdown' => $upfrontFees
            ],
            'product_charges' => [
                'valid' => $chargeCalculation['valid'],
                'message' => $chargeCalculation['message'] ?? '',
                'total_charges' => $chargeCalculation['total_charges'] ?? 0,
                'disbursable_amount' => $chargeCalculation['disbursable_amount'] ?? 0,
                'breakdown' => $chargeCalculation['breakdown'] ?? ''
            ],
            'can_disburse' => $mandatoryValidation['valid'] && 
                             $upfrontValidation['valid'] && 
                             $chargeCalculation['valid']
        ]);
    }

    /**
     * Get mandatory fees breakdown for a member
     */
    private function getMandatoryFeesBreakdown(Member $member)
    {
        $mandatoryFeeTypes = FeeType::active()
                                   ->where('required_disbursement', 0)
                                   ->get();

        $breakdown = [];

        foreach ($mandatoryFeeTypes as $feeType) {
            $paidFee = Fee::where('member_id', $member->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1)
                         ->first();

            $breakdown[] = [
                'name' => $feeType->name,
                'required' => true,
                'paid' => $paidFee ? true : false,
                'amount' => $paidFee ? $paidFee->amount : 0,
                'payment_date' => $paidFee ? $paidFee->created_at->format('Y-m-d') : null
            ];
        }

        return $breakdown;
    }

    /**
     * Get upfront fees breakdown for a loan
     */
    private function getUpfrontFeesBreakdown($loan)
    {
        $upfrontFeeTypes = FeeType::active()
                                 ->where('required_disbursement', 1)
                                 ->get();

        $breakdown = [];

        foreach ($upfrontFeeTypes as $feeType) {
            $paidFee = Fee::where('loan_id', $loan->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1)
                         ->first();

            $breakdown[] = [
                'name' => $feeType->name,
                'required' => true,
                'paid' => $paidFee ? true : false,
                'amount' => $paidFee ? $paidFee->amount : 0,
                'payment_date' => $paidFee ? $paidFee->created_at->format('Y-m-d') : null
            ];
        }

        return $breakdown;
    }

    /**
     * Bulk status check for pending disbursements (CRON job endpoint)
     */
    public function bulkStatusCheck()
    {
        $pendingDisbursements = Disbursement::where('status', 0)
                                          ->whereHas('transaction')
                                          ->with('transaction', 'rawPayment')
                                          ->get();

        $processed = 0;
        $completed = 0;
        $failed = 0;

        foreach ($pendingDisbursements as $disbursement) {
            try {
                $result = $this->checkStatus($disbursement);
                $resultData = $result->getData(true);
                
                if ($resultData['success'] && isset($resultData['status'])) {
                    $processed++;
                    
                    if ($resultData['status'] === 'completed') {
                        $completed++;
                    } elseif ($resultData['status'] === 'failed') {
                        $failed++;
                    }
                }
                
                // Prevent API overload
                sleep(1);
                
            } catch (\Exception $e) {
                Log::error('Bulk status check error for disbursement ' . $disbursement->id . ': ' . $e->getMessage());
            }
        }

        Log::info('Bulk disbursement status check completed', [
            'processed' => $processed,
            'completed' => $completed,
            'failed' => $failed
        ]);

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'completed' => $completed,
            'failed' => $failed
        ]);
    }

    /**
     * Export pending disbursements to Excel/CSV
     */
    public function export(Request $request)
    {
        // Get the same data as the pending method but without pagination
        $commonColumns = [
            'id', 'code', 'product_type', 'principal', 'status', 'verified',
            'added_by', 'datecreated', 'branch_id'
        ];

        // Personal loans query
        $personalLoansQuery = PersonalLoan::where('status', 1) // Approved loans
                    ->whereDoesntHave('disbursements', function($q) {
                        $q->where('status', 1); // No successful disbursement yet
                    })
                    ->with(['member', 'branch', 'product'])
                    ->select(array_merge($commonColumns, [
                        'member_id',
                        DB::raw("'personal' as loan_type"),
                        DB::raw("NULL as group_id")
                    ]));

        // Group loans query
        $groupLoansQuery = GroupLoan::where('status', 1) // Approved loans
                    ->whereDoesntHave('disbursements', function($q) {
                        $q->where('status', 1); // No successful disbursement yet
                    })
                    ->with(['group', 'branch', 'product'])
                    ->select(array_merge($commonColumns, [
                        'group_id',
                        DB::raw("'group' as loan_type"),
                        DB::raw("NULL as member_id")
                    ]));

        // Apply filters (same as pending method)
        if ($request->filled('search')) {
            $search = $request->get('search');
            
            $personalLoansQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($q) use ($search) {
                      $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhere('contact', 'like', "%{$search}%");
                  });
            });

            $groupLoansQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('group', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('branch')) {
            $personalLoansQuery->where('branch_id', $request->get('branch'));
            $groupLoansQuery->where('branch_id', $request->get('branch'));
        }

        if ($request->filled('product')) {
            $personalLoansQuery->where('product_type', $request->get('product'));
            $groupLoansQuery->where('product_type', $request->get('product'));
        }

        // Get all loans
        $loans = $personalLoansQuery->union($groupLoansQuery)
                                   ->orderBy('datecreated', 'desc')
                                   ->get();

        // Prepare data for export
        $exportData = [];
        foreach ($loans as $loan) {
            $exportData[] = [
                'Loan Code' => $loan->code,
                'Type' => ucfirst($loan->loan_type),
                'Borrower' => $loan->loan_type === 'personal' ? 
                    ($loan->member->fname ?? 'N/A') . ' ' . ($loan->member->lname ?? '') :
                    ($loan->group->name ?? 'N/A'),
                'Product' => $loan->product->name ?? 'N/A',
                'Principal Amount' => $loan->principal,
                'Branch' => $loan->branch->name ?? 'N/A',
                'Date Created' => \Carbon\Carbon::parse($loan->datecreated)->format('Y-m-d H:i:s'),
                'Status' => 'Approved - Pending Disbursement'
            ];
        }

        $filename = 'pending_disbursements_' . date('Y_m_d_H_i_s') . '.csv';
        
        // Create CSV response
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($exportData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for proper Excel UTF-8 handling
            fwrite($file, "\xEF\xBB\xBF");
            
            // Write header
            if (!empty($exportData)) {
                fputcsv($file, array_keys($exportData[0]));
                
                // Write data
                foreach ($exportData as $row) {
                    fputcsv($file, $row);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mark disbursement as complete
     */
    public function complete(Disbursement $disbursement)
    {
        // Only Super Administrator can complete disbursements
        if (!auth()->user()->hasRole('Super Administrator') && !auth()->user()->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'Access denied. Only Super Administrator can complete disbursements.');
        }

        try {
            if ($disbursement->status == 2) {
                return back()->with('info', 'Disbursement is already marked as disbursed.');
            }

            if ($disbursement->status == 3) {
                return back()->with('error', 'Cannot complete a failed disbursement.');
            }

            DB::beginTransaction();

            // Complete the disbursement (updates status to 2, updates loan, creates schedules, etc.)
            $this->completeDisbursement($disbursement);

            DB::commit();

            return back()->with('success', 'Disbursement marked as complete successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error completing disbursement: ' . $e->getMessage());
            return back()->with('error', 'Error completing disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Retry a failed disbursement
     */
    public function retry(Disbursement $disbursement)
    {
        try {
            if ($disbursement->status != 3) {
                return back()->with('error', 'Only failed disbursements can be retried.');
            }

            DB::beginTransaction();

            // Reset status to pending (0)
            $disbursement->update([
                'status' => 0,
                'notes' => ($disbursement->notes ?? '') . "\n\nRetried by: " . auth()->user()->name . " on " . now()->format('Y-m-d H:i:s')
            ]);

            // If mobile money, initiate new transaction
            if ($disbursement->payment_type == 1) {
                $result = $this->processMobileMoneyDisbursement(
                    $disbursement,
                    $disbursement->account_number,
                    $disbursement->payment_medium
                );

                if ($result['success']) {
                    // Complete disbursement if immediately successful
                    if (isset($result['immediate_success']) && $result['immediate_success']) {
                        $this->completeDisbursement($disbursement); // This updates status to 2 internally
                    }

                    DB::commit();
                    return back()->with('success', $result['message']);
                } else {
                    DB::rollback();
                    return back()->with('error', $result['message']);
                }
            } else {
                // For non-mobile money, just reset status
                DB::commit();
                return back()->with('success', 'Disbursement reset to pending. Please process manually.');
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error retrying disbursement: ' . $e->getMessage());
            return back()->with('error', 'Error retrying disbursement: ' . $e->getMessage());
        }
    }
}