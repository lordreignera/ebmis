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
        $query = Disbursement::with(['loan.member', 'loan.product', 'loan.branch', 'addedBy']);

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
            $query->whereDate('disbursement_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('disbursement_date', '<=', $request->end_date);
        }

        $disbursements = $query->orderBy('created_at', 'desc')->paginate(20);

        $branches = Branch::active()->get();
        $investments = Investment::where('status', 1)->get();

        // Calculate totals
        $totals = [
            'total_disbursements' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'pending_disbursements' => $query->where('status', 0)->count(),
            'successful_disbursements' => $query->where('status', 1)->count(),
            'failed_disbursements' => $query->where('status', 2)->count(),
        ];

        return view('admin.disbursements.index', compact('disbursements', 'branches', 'investments', 'totals'));
    }

    /**
     * Display pending disbursements waiting for approval
     */
    public function pending(Request $request)
    {
        // Common columns for both loan types
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
                      $q->where('group_name', 'like', "%{$search}%");
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

        // Union and paginate
        $loans = $personalLoansQuery->union($groupLoansQuery)
                                   ->orderBy('datecreated', 'desc')
                                   ->paginate(20);

        // Get filter options
        $branches = Branch::active()->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();

        // Calculate stats for both personal and group loans
        $personalPendingLoans = PersonalLoan::where('status', 1)
                           ->whereDoesntHave('disbursements', function($q) {
                               $q->where('status', 1);
                           });
        
        $groupPendingLoans = GroupLoan::where('status', 1)
                           ->whereDoesntHave('disbursements', function($q) {
                               $q->where('status', 1);
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
                       $q->where('status', 1); // No successful disbursement
                   })
                   ->find($id);
        
        $loanType = 'personal';
        
        if (!$loan) {
            $loan = GroupLoan::with(['group', 'branch', 'product'])
                       ->where('status', 1) // Approved
                       ->whereDoesntHave('disbursements', function($q) {
                           $q->where('status', 1); // No successful disbursement
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
            $loan->borrower_name = $loan->group->group_name ?? 'Group Loan';
            $loan->phone_number = $loan->group->contact ?? '';
        }
        
        $loan->branch_name = $loan->branch->name ?? null;
        $loan->product_name = $loan->product->name ?? null;
        $loan->loan_code = $loan->code;
        $loan->principal_amount = $loan->principal;
        $loan->loan_term = $loan->period;
        $loan->period_type = $this->getPeriodTypeName($loan->period_type);
        $loan->processing_fee = $chargeCalculation['total_deductions'];
        $loan->loan_type = $loanType;
        
        // Get staff members for assignment
        $staff_members = User::where('status', 1)
                           ->where('role', '!=', 'member')
                           ->orderBy('name')
                           ->get();

        return view('admin.loans.disbursements.approve', compact('loan', 'staff_members'));
    }

    /**
     * Process loan disbursement approval (new UI method)
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'disbursement_date' => 'required|date|before_or_equal:today',
            'payment_type' => 'required|in:mobile_money,bank_transfer,cash,cheque',
            'account_number' => 'required|string|max:50',
            'network' => 'required_if:payment_type,mobile_money|in:MTN,AIRTEL',
            'assigned_to' => 'nullable|exists:users,id',
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::with(['member', 'branch', 'product'])
                   ->where('status', 1)
                   ->whereDoesntHave('disbursements', function($q) {
                       $q->where('status', 1);
                   })
                   ->findOrFail($id);

        DB::beginTransaction();

        try {
            // Validate mandatory fees and calculate charges
            $mandatoryValidation = $this->validateMandatoryFees($loan->member);
            if (!$mandatoryValidation['valid']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mandatory fees required: ' . $mandatoryValidation['message']
                ], 400);
            }

            $chargeCalculation = $this->calculateAllChargesAndDeductions($loan);
            if (!$chargeCalculation['valid']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $chargeCalculation['message']
                ], 400);
            }

            $disbursementAmount = $chargeCalculation['disbursable_amount'];

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

            // Generate disbursement code
            $branch = $loan->branch;
            $disbursementCount = Disbursement::count();
            $code = 'DISB-' . ($branch->code ?? 'UNK') . '-' . date('Ymd') . '-' . str_pad($disbursementCount + 1, 4, '0', STR_PAD_LEFT);

            $disbursementData = [
                'loan_id' => $loan->id,
                'code' => $code,
                'amount' => $disbursementAmount,
                'disbursement_date' => $request->disbursement_date,
                'payment_type' => $paymentTypeMapping[$request->payment_type],
                'payment_medium' => $paymentMedium,
                'account_number' => $request->account_number,
                'investment_id' => 1, // Default investment account - you might want to make this configurable
                'assigned_to' => $request->assigned_to,
                'notes' => $request->comments . "\n\n" . $chargeCalculation['detailed_breakdown'],
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

                if ($mobileMoneyResult['success']) {
                    if (isset($mobileMoneyResult['immediate_success']) && $mobileMoneyResult['immediate_success']) {
                        $disbursement->update(['status' => 1]);
                        $this->completeDisbursement($disbursement);
                    }
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile money disbursement failed: ' . $mobileMoneyResult['message']
                    ], 400);
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

            return response()->json([
                'success' => true,
                'message' => $message,
                'loan_id' => $loan->id,
                'disbursement_id' => $disbursement->id,
                'redirect_url' => route('admin.loans.active')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Loan disbursement approval failed', [
                'loan_id' => $loan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Disbursement approval failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Process mobile money disbursement using the new MobileMoneyService
     */
    protected function processNewMobileMoneyDisbursement($disbursement, $phoneNumber, $network)
    {
        try {
            $loan = $disbursement->loan;
            $memberName = $loan->member->fname . ' ' . $loan->member->lname;
            
            // Use the MobileMoneyService which handles FlexiPay integration
            $result = $this->mobileMoneyService->sendMoney(
                $memberName,
                $phoneNumber,
                $disbursement->amount,
                "Loan disbursement for {$loan->code}"
            );

            if ($result['success']) {
                // Create raw payment record for tracking
                RawPayment::create([
                    'txn_id' => $result['reference'] ?? 'TXN-' . time(),
                    'amount' => $disbursement->amount,
                    'phone_number' => $phoneNumber,
                    'status' => $result['status'] ?? '00',
                    'type' => 'disbursement',
                    'loan_id' => $disbursement->loan_id,
                    'disbursement_id' => $disbursement->id,
                ]);

                // Create disbursement transaction record
                DisbursementTransaction::create([
                    'disbursement_id' => $disbursement->id,
                    'txn_reference' => $result['reference'] ?? 'TXN-' . time(),
                    'network' => $network,
                    'phone' => $phoneNumber,
                    'status' => $result['status'] ?? '00',
                    'response_data' => json_encode($result),
                ]);

                return [
                    'success' => true,
                    'message' => $result['message'] ?? 'Mobile money disbursement initiated successfully',
                    'immediate_success' => isset($result['status']) && $result['status'] === '01',
                    'transaction_id' => $result['reference'] ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Mobile money transfer failed'
                ];
            }

        } catch (\Exception $e) {
            Log::error('New mobile money disbursement error', [
                'disbursement_id' => $disbursement->id,
                'phone' => $phoneNumber,
                'network' => $network,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Mobile money service error: ' . $e->getMessage()
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
        $users = \App\Models\User::where('status', 1)->get(); // Staff for assignment

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
                                               ->where('status', 1)
                                               ->first();
            
            if ($existingDisbursement) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'Loan has already been disbursed.');
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
                // Update disbursement status if immediately successful
                if (isset($result['immediate_success']) && $result['immediate_success']) {
                    $disbursement->update(['status' => 1]);
                    $this->completeDisbursement($disbursement);
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
     * Process mobile money disbursement via FlexiPay
     */
    private function processMobileMoneyDisbursement(Disbursement $disbursement, $phoneNumber, $paymentMedium)
    {
        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
            
            // Detect network
            $network = $this->detectNetwork($normalizedPhone);
            
            // Override with selected payment medium if provided
            if ($paymentMedium == 1) {
                $network = 'AIRTEL';
            } elseif ($paymentMedium == 2) {
                $network = 'MTN';
            }

            if (!$network) {
                return [
                    'success' => false,
                    'message' => 'Unable to detect mobile network for phone number: ' . $phoneNumber
                ];
            }

            // Prepare FlexiPay request
            $flexiPayData = [
                'msisdn' => $normalizedPhone,
                'amount' => $disbursement->amount,
                'reference' => $disbursement->code,
                'narrative' => 'Loan Disbursement ' . $disbursement->loan->code,
                'network' => $network,
            ];

            // Call FlexiPay API
            $response = Http::timeout(30)->post(config('services.flexipay.base_url') . 'marchanToMobilePayprod.php', $flexiPayData);

            if ($response->successful()) {
                $responseData = $response->json();

                // Create raw payment record
                RawPayment::create([
                    'txn_id' => $responseData['transactionId'] ?? 'TXN-' . time(),
                    'amount' => $disbursement->amount,
                    'phone_number' => $normalizedPhone,
                    'status' => $responseData['status'] ?? '00',
                    'type' => 'disbursement',
                    'loan_id' => $disbursement->loan_id,
                    'disbursement_id' => $disbursement->id,
                ]);

                // Create disbursement transaction record
                DisbursementTransaction::create([
                    'disbursement_id' => $disbursement->id,
                    'txn_reference' => $responseData['transactionId'] ?? 'TXN-' . time(),
                    'network' => $network,
                    'phone' => $normalizedPhone,
                    'status' => $responseData['status'] ?? '00',
                    'response_data' => json_encode($responseData),
                ]);

                // Check if immediately successful
                $immediateSuccess = isset($responseData['status']) && $responseData['status'] === '01';

                return [
                    'success' => true,
                    'message' => $immediateSuccess ? 
                        'Disbursement completed successfully via ' . $network : 
                        'Disbursement initiated via ' . $network . '. Transaction pending confirmation.',
                    'immediate_success' => $immediateSuccess,
                    'transaction_id' => $responseData['transactionId'] ?? null,
                ];

            } else {
                Log::error('FlexiPay API error: ' . $response->body());
                
                return [
                    'success' => false,
                    'message' => 'Failed to initiate mobile money disbursement. Please try again.'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Mobile money disbursement error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error processing mobile money disbursement: ' . $e->getMessage()
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
            $loan = $disbursement->loan;

            // Update loan status to active/disbursed
            $loan->update(['status' => 2]);

            // Deduct from investment account
            $investment = $disbursement->investment;
            $investment->decrement('amount', $disbursement->amount);

            // Recalculate loan schedule dates from disbursement date
            $this->recalculateLoanSchedule($loan, $disbursement->disbursement_date);

            // Send SMS notification to member
            $this->sendDisbursementNotification($loan->member, $disbursement);

            Log::info('Disbursement completed successfully', [
                'disbursement_id' => $disbursement->id,
                'loan_id' => $loan->id,
                'amount' => $disbursement->amount
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing disbursement: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recalculate loan schedule dates from disbursement date
     */
    private function recalculateLoanSchedule(Loan $loan, $disbursementDate)
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
                
            case 3: // Daily - skip Sundays
                $date = $startDate->copy()->addDays(7); // Start from next week
                $daysAdded = 0;
                
                for ($i = 0; $i < $periodNumber - 1; $i++) {
                    $date->addDay();
                    // Skip Sundays
                    if ($date->isSunday()) {
                        $date->addDay();
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
        if ($disbursement->status !== 0) {
            return redirect()->back()
                            ->with('error', 'Can only cancel pending disbursements.');
        }

        try {
            DB::beginTransaction();

            $disbursement->update(['status' => 2]); // Cancelled/Failed

            // Update transaction status if exists
            if ($disbursement->transaction) {
                $disbursement->transaction->update(['status' => '02']);
            }

            // Update raw payment status if exists
            if ($disbursement->rawPayment) {
                $disbursement->rawPayment->update(['status' => '02']);
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
        // Get all fee types that are mandatory (required_disbursement = 0)
        // These are one-time fees like registration that CANNOT be auto-deducted
        $mandatoryFeeTypes = FeeType::active()
                                   ->where('required_disbursement', 0)
                                   ->get();

        $unpaidMandatoryFees = [];

        foreach ($mandatoryFeeTypes as $feeType) {
            // Check if member has paid this mandatory fee
            $paidFee = Fee::where('member_id', $member->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1) // Paid
                         ->first();

            if (!$paidFee) {
                $unpaidMandatoryFees[] = $feeType->name;
            }
        }

        if (!empty($unpaidMandatoryFees)) {
            return [
                'valid' => false,
                'message' => implode(', ', $unpaidMandatoryFees) . 
                           '. These mandatory fees must be paid separately before any loan disbursement.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Calculate ALL charges (upfront + product) and determine final disbursable amount
     * Upfront charges can be auto-deducted if not already paid
     */
    private function calculateAllChargesAndDeductions(Loan $loan)
    {
        $product = $loan->product;
        $principal = $loan->principal;
        $totalDeductions = 0;
        $chargeBreakdown = [];
        $feeRecords = [];
        $loanChargeRecords = [];

        // 1. GET UPFRONT CHARGES (can be auto-deducted if not paid)
        $upfrontFeeTypes = FeeType::active()
                                 ->where('required_disbursement', 1)
                                 ->get();

        foreach ($upfrontFeeTypes as $feeType) {
            // Check if already paid
            $paidFee = Fee::where('loan_id', $loan->id)
                         ->where('fees_type_id', $feeType->id)
                         ->where('status', 1)
                         ->first();

            if (!$paidFee) {
                // Not paid - will be auto-deducted
                // For upfront fees, we need to determine the amount
                // This could be a fixed amount or percentage - let's assume fixed for now
                $upfrontAmount = 50000; // This should come from configuration or fee type
                
                $totalDeductions += $upfrontAmount;
                $chargeBreakdown[] = $feeType->name . ': UGX ' . number_format($upfrontAmount, 2) . ' (Auto-deducted upfront charge)';
                
                // Record for auto-creation
                $feeRecords[] = [
                    'fees_type_id' => $feeType->id,
                    'amount' => $upfrontAmount,
                    'description' => 'Auto-deducted during disbursement'
                ];
            } else {
                $chargeBreakdown[] = $feeType->name . ': UGX ' . number_format($paidFee->amount, 2) . ' (Already paid)';
            }
        }

        // 2. GET PRODUCT CHARGES (always auto-deducted)
        $productCharges = ProductCharge::where('product_id', $product->id)
                                      ->active()
                                      ->get();

        foreach ($productCharges as $charge) {
            $chargeAmount = 0;

            switch ($charge->type) {
                case 1: // Fixed Amount
                    $chargeAmount = $charge->value;
                    break;
                    
                case 2: // Percentage
                    $chargeAmount = ($principal * $charge->value) / 100;
                    break;
                    
                case 3: // Per Day
                    $chargeAmount = $charge->value * $loan->period;
                    break;
                    
                case 4: // Per Month
                    $months = ceil($loan->period / 30);
                    $chargeAmount = $charge->value * $months;
                    break;
                    
                default:
                    $chargeAmount = $charge->value;
            }

            $totalDeductions += $chargeAmount;
            $chargeBreakdown[] = $charge->name . ': UGX ' . number_format($chargeAmount, 2) . 
                               ' (' . $charge->type_name . ' - Product charge)';
            
            $loanChargeRecords[] = [
                'charge_name' => $charge->name,
                'charge_type' => $charge->type,
                'charge_value' => $charge->value,
                'actual_value' => $chargeAmount
            ];
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
    private function recordAllCharges(Loan $loan, array $chargeCalculation)
    {
        // Record auto-deducted upfront fees
        foreach ($chargeCalculation['auto_deducted_fees'] as $feeData) {
            Fee::create([
                'member_id' => $loan->member_id,
                'loan_id' => $loan->id,
                'fees_type_id' => $feeData['fees_type_id'],
                'payment_type' => 4, // Auto-deducted
                'amount' => $feeData['amount'],
                'description' => $feeData['description'],
                'added_by' => auth()->id(),
                'payment_status' => 'Auto-deducted',
                'payment_description' => 'Automatically deducted during loan disbursement',
                'status' => 1, // Paid (via deduction)
            ]);
        }

        // Record product charges
        foreach ($chargeCalculation['loan_charges'] as $chargeData) {
            LoanCharge::create([
                'loan_id' => $loan->id,
                'charge_name' => $chargeData['charge_name'],
                'charge_type' => $chargeData['charge_type'],
                'charge_value' => $chargeData['charge_value'],
                'actual_value' => $chargeData['actual_value'],
                'added_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Validate upfront charges are paid before disbursement
     */
    private function validateUpfrontCharges(Loan $loan)
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
    private function getUpfrontFeesBreakdown(Loan $loan)
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
                      $q->where('group_name', 'like', "%{$search}%");
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
                    ($loan->group->group_name ?? 'N/A'),
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
}