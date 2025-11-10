<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Branch;
use App\Models\LoanSchedule;
use App\Models\Product;
use App\Models\User;
use App\Services\MobileMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RepaymentController extends Controller
{
    protected $mobileMoneyService;

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }

    /**
     * Display active loans for repayment management
     */
    public function activeLoans(Request $request)
    {
        $query = Loan::where('status', 2) // Disbursed loans
                    ->with(['member', 'branch', 'product', 'repayments']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($q) use ($search) {
                      $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhere('contact', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('branch')) {
            $query->where('branch_id', $request->get('branch'));
        }

        if ($request->filled('product')) {
            $query->where('product_id', $request->get('product'));
        }

        if ($request->filled('status')) {
            // Filter by repayment status
            $status = $request->get('status');
            if ($status === 'overdue') {
                $query->whereHas('schedules', function($q) {
                    $q->where('status', 0)
                      ->where('payment_date', '<', now());
                });
            } elseif ($status === 'current') {
                $query->whereDoesntHave('schedules', function($q) {
                    $q->where('status', 0)
                      ->where('payment_date', '<', now());
                });
            }
        }

        $loans = $query->get()->map(function($loan) {
            // Calculate loan details
            $loan->borrower_name = $loan->member->fname . ' ' . $loan->member->lname;
            $loan->phone_number = $loan->member->contact;
            $loan->branch_name = $loan->branch->name ?? 'N/A';
            $loan->product_name = $loan->product->name ?? 'N/A';
            $loan->loan_code = $loan->code;
            $loan->principal_amount = $loan->principal;
            
            // Calculate outstanding balance
            $totalPaid = $loan->repayments->where('status', 1)->sum('amount');
            $loan->outstanding_balance = $loan->principal + $loan->interest - $totalPaid;
            
            // Find next due payment
            $nextSchedule = $loan->schedules()
                                ->where('status', 0)
                                ->orderBy('payment_date')
                                ->first();
            
            if ($nextSchedule) {
                $loan->next_due_date = $nextSchedule->payment_date;
                $loan->next_due_amount = $nextSchedule->payment + ($nextSchedule->penalty ?? 0);
                $loan->days_overdue = $nextSchedule->payment_date < now() ? 
                                     now()->diffInDays($nextSchedule->payment_date) : 0;
            } else {
                $loan->next_due_date = null;
                $loan->next_due_amount = 0;
                $loan->days_overdue = 0;
            }
            
            return $loan;
        });

        // Filter loans with outstanding balance
        $loans = $loans->filter(function($loan) {
            return $loan->outstanding_balance > 0;
        });

        // Paginate manually
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = 20;
        $currentItems = $loans->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $loans->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get filter options
        $branches = Branch::active()->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();

        // Calculate stats
        $allActiveLoans = Loan::where('status', 2)->with('repayments', 'schedules')->get();
        $stats = [
            'total_active' => $allActiveLoans->count(),
            'outstanding_amount' => $allActiveLoans->sum(function($loan) {
                $totalPaid = $loan->repayments->where('status', 1)->sum('amount');
                return $loan->principal + $loan->interest - $totalPaid;
            }),
            'overdue_count' => $allActiveLoans->filter(function($loan) {
                $hasOverdue = $loan->schedules()
                                  ->where('status', 0)
                                  ->where('payment_date', '<', now())
                                  ->exists();
                return $hasOverdue;
            })->count(),
            'collections_today' => Repayment::where('status', 1)
                                          ->whereDate('date_created', today())
                                          ->sum('amount'),
        ];

        return view('admin.loans.active', compact('loans', 'branches', 'products', 'stats'));
    }

    /**
     * Display repayment schedules for a specific loan
     */
    public function schedules($loanId)
    {
        $loan = Loan::with(['member', 'branch', 'product', 'schedules', 'repayments'])
                   ->where('status', 2) // Disbursed
                   ->findOrFail($loanId);

        // Calculate loan details
        $loan->borrower_name = $loan->member->fname . ' ' . $loan->member->lname;
        $loan->phone_number = $loan->member->contact;
        $loan->branch_name = $loan->branch->name ?? 'N/A';
        $loan->product_name = $loan->product->name ?? 'N/A';
        $loan->loan_code = $loan->code;
        $loan->principal_amount = $loan->principal;
        
        // Calculate payment status
        $totalPaid = $loan->repayments->where('status', 1)->sum('amount');
        $totalPayable = $loan->principal + $loan->interest;
        $loan->amount_paid = $totalPaid;
        $loan->outstanding_balance = $totalPayable - $totalPaid;
        $loan->total_payable = $totalPayable;
        $loan->payment_percentage = $totalPayable > 0 ? ($totalPaid / $totalPayable) * 100 : 0;
        
        // Calculate days overdue
        $overdueSchedules = $loan->schedules()
                                ->where('status', 0)
                                ->where('payment_date', '<', now())
                                ->get();
        
        $loan->days_overdue = $overdueSchedules->count() > 0 ? 
                             now()->diffInDays($overdueSchedules->first()->payment_date) : 0;

        // Get schedules with payment status
        $schedules = $loan->schedules->map(function($schedule) {
            $schedule->due_amount = $schedule->payment;
            $schedule->penalty_amount = $schedule->penalty ?? 0;
            $schedule->interest_amount = $schedule->interest ?? 0;
            $schedule->principal_amount = $schedule->payment - $schedule->interest_amount;
            $schedule->installment_number = $schedule->id; // Use ID as installment number
            $schedule->amount_paid = $schedule->paid_amount ?? 0;
            
            // Calculate days overdue/remaining
            if ($schedule->status == 1) {
                $schedule->payment_status = 'paid';
                $schedule->days_overdue = null;
            } else {
                $schedule->payment_status = 'pending';
                $schedule->days_overdue = $schedule->payment_date < now() ? 
                                         now()->diffInDays($schedule->payment_date) : 
                                         -now()->diffInDays($schedule->payment_date);
            }
            
            return $schedule;
        });

        // Find next due payment
        $nextDue = $schedules->where('payment_status', 'pending')
                            ->sortBy('payment_date')
                            ->first();

        // Calculate overdue stats
        $overdueSchedules = $schedules->where('payment_status', 'pending')
                                    ->where('payment_date', '<', now());
        $overdueCount = $overdueSchedules->count();
        $overdueAmount = $overdueSchedules->sum(function($schedule) {
            return $schedule->due_amount + $schedule->penalty_amount;
        });

        return view('admin.loans.repayments.schedules', compact(
            'loan', 'schedules', 'nextDue', 'overdueCount', 'overdueAmount'
        ));
    }

    /**
     * Process quick repayment (AJAX)
     */
    public function quickRepayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer',
            'network' => 'required_if:payment_method,mobile_money|in:MTN,AIRTEL',
            'phone' => 'required_if:payment_method,mobile_money|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::findOrFail($request->loan_id);

        DB::beginTransaction();

        try {
            $repaymentData = [
                'type' => $this->getPaymentTypeCode($request->payment_method),
                'details' => $request->notes ?: 'Quick repayment',
                'loan_id' => $request->loan_id,
                'schedule_id' => 0,
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'platform' => 'Web',
            ];

            // Handle mobile money
            if ($request->payment_method === 'mobile_money') {
                $mobileMoneyResult = $this->processMobileMoneyCollection(
                    $loan,
                    $request->amount,
                    $request->phone,
                    $request->network
                );

                if ($mobileMoneyResult['success']) {
                    $repaymentData['status'] = 0; // Pending mobile money confirmation
                    $repaymentData['txn_id'] = $mobileMoneyResult['reference'];
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated';
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                    ], 400);
                }
            } else {
                // For cash/bank transfers, mark as confirmed
                $repaymentData['status'] = 1;
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = 'Payment confirmed';
                $repaymentData['txn_id'] = 'TXN-' . time() . '-' . $request->payment_method;
            }

            $repayment = Repayment::create($repaymentData);

            // Update loan balance if confirmed
            if ($repaymentData['status'] == 1) {
                $loan->increment('paid', $request->amount);
                $this->updateLoanSchedules($loan, $repayment);
            }

            DB::commit();

            $message = "Repayment recorded successfully.";
            if ($request->payment_method === 'mobile_money') {
                $message .= " Mobile money collection request sent.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'repayment_id' => $repayment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Quick repayment failed', [
                'loan_id' => $request->loan_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Repayment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Store repayment from schedules view
     */
    public function storeRepayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'schedule_id' => 'required|exists:loan_schedules,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer,cheque',
            'network' => 'required_if:payment_method,mobile_money|in:MTN,AIRTEL',
            'phone_number' => 'required_if:payment_method,mobile_money|string',
            'payment_date' => 'required|date|before_or_equal:today',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'auto_collect' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::findOrFail($request->loan_id);
        $schedule = LoanSchedule::findOrFail($request->schedule_id);

        DB::beginTransaction();

        try {
            $repaymentData = [
                'type' => $this->getPaymentTypeCode($request->payment_method),
                'details' => $request->notes ?: 'Scheduled payment',
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->schedule_id,
                'amount' => $request->amount,
                'date_created' => $request->payment_date,
                'added_by' => auth()->id(),
                'platform' => 'Web',
                'txn_id' => $request->reference_number ?: ('REF-' . time()),
            ];

            // Handle mobile money with auto-collect
            if ($request->payment_method === 'mobile_money' && $request->auto_collect) {
                $mobileMoneyResult = $this->processMobileMoneyCollection(
                    $loan,
                    $request->amount,
                    $request->phone_number,
                    $request->network
                );

                if ($mobileMoneyResult['success']) {
                    $repaymentData['status'] = 0; // Pending
                    $repaymentData['txn_id'] = $mobileMoneyResult['reference'];
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated';
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                    ]);
                }
            } else {
                // Manual confirmation
                $repaymentData['status'] = 1; // Confirmed
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = 'Payment manually confirmed';
            }

            $repayment = Repayment::create($repaymentData);

            // Update loan and schedule if confirmed
            if ($repaymentData['status'] == 1) {
                $loan->increment('paid', $request->amount);
                
                // Update specific schedule
                $schedule->increment('paid_amount', $request->amount);
                if ($schedule->paid_amount >= $schedule->payment) {
                    $schedule->update(['status' => 1]); // Fully paid
                }
            }

            DB::commit();

            $message = "Payment recorded successfully.";
            if ($request->payment_method === 'mobile_money' && $request->auto_collect) {
                $message .= " Mobile money collection request sent.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'repayment_id' => $repayment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Repayment storage failed', [
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->schedule_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment recording failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Process partial payment
     */
    public function partialPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::findOrFail($request->loan_id);

        DB::beginTransaction();

        try {
            $repaymentData = [
                'type' => $this->getPaymentTypeCode($request->payment_method),
                'details' => 'Partial payment: ' . ($request->notes ?: 'No notes'),
                'loan_id' => $request->loan_id,
                'schedule_id' => 0, // Partial payments don't target specific schedules
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'status' => 1, // Assume confirmed for partial payments
                'platform' => 'Web',
                'txn_id' => 'PARTIAL-' . time(),
                'pay_status' => 'SUCCESS',
                'pay_message' => 'Partial payment confirmed',
            ];

            $repayment = Repayment::create($repaymentData);

            // Update loan balance
            $loan->increment('paid', $request->amount);

            // Apply to oldest outstanding schedules
            $this->applyPartialPayment($loan, $request->amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partial payment recorded successfully.',
                'repayment_id' => $repayment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Partial payment failed', [
                'loan_id' => $request->loan_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Partial payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Process mobile money collection using FlexiPay
     */
    protected function processMobileMoneyCollection($loan, $amount, $phoneNumber, $network)
    {
        try {
            $memberName = $loan->member->fname . ' ' . $loan->member->lname;
            
            // Use MobileMoneyService for collection (reverse of disbursement)
            $result = $this->mobileMoneyService->collectMoney(
                $memberName,
                $phoneNumber,
                $amount,
                "Loan repayment for {$loan->code}"
            );

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Mobile money collection processed',
                'reference' => $result['reference'] ?? 'REF-' . time()
            ];

        } catch (\Exception $e) {
            Log::error('Mobile money collection error', [
                'loan_id' => $loan->id,
                'amount' => $amount,
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
     * Apply partial payment to oldest outstanding schedules
     */
    protected function applyPartialPayment($loan, $amount)
    {
        $schedules = $loan->schedules()
                         ->where('status', 0)
                         ->orderBy('payment_date')
                         ->get();

        $remainingAmount = $amount;

        foreach ($schedules as $schedule) {
            if ($remainingAmount <= 0) break;

            $outstandingAmount = $schedule->payment - ($schedule->paid_amount ?? 0);
            
            if ($outstandingAmount <= 0) continue;

            if ($remainingAmount >= $outstandingAmount) {
                // Can fully pay this schedule
                $schedule->update([
                    'paid_amount' => $schedule->payment,
                    'status' => 1 // Fully paid
                ]);
                $remainingAmount -= $outstandingAmount;
            } else {
                // Partial payment for this schedule
                $newPaidAmount = ($schedule->paid_amount ?? 0) + $remainingAmount;
                $schedule->update(['paid_amount' => $newPaidAmount]);
                $remainingAmount = 0;
            }
        }
    }

    /**
     * Get payment type code for database storage
     */
    protected function getPaymentTypeCode($paymentMethod)
    {
        $mapping = [
            'cash' => 1,
            'mobile_money' => 2,
            'bank_transfer' => 3,
            'cheque' => 3
        ];

        return $mapping[$paymentMethod] ?? 1;
    }

    /**
     * Display a listing of repayments
     */
    public function index(Request $request)
    {
        $query = Repayment::with(['loan.member', 'loan.product', 'addedBy']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('loan', function($loanQuery) use ($search) {
                    $loanQuery->where('code', 'like', "%{$search}%")
                             ->orWhereHas('member', function($memberQuery) use ($search) {
                                 $memberQuery->where('fname', 'like', "%{$search}%")
                                           ->orWhere('lname', 'like', "%{$search}%")
                                           ->orWhere('code', 'like', "%{$search}%");
                             });
                });
            });
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('date_created', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('date_created', '<=', $request->end_date);
        }

        // Filter by payment method
        if ($request->has('method') && $request->method !== '') {
            $query->where('type', $request->method);
        }

        $repayments = $query->orderBy('date_created', 'desc')->paginate(20);

        $branches = Branch::active()->get() ?? collect();

        // Calculate totals for current filter
        $totalQuery = clone $query;
        $totals = [
            'total_amount' => $totalQuery->sum('amount'),
            'total_principal' => 0, // We don't separate these in old structure
            'total_interest' => 0,
            'total_fees' => 0,
            'total_penalty' => 0,
        ];

        return view('admin.repayments.index', compact('repayments', 'branches', 'totals'));
    }

    /**
     * Show the form for creating a new repayment
     */
    public function create(Request $request)
    {
        // Get disbursed loans and filter by outstanding balance in PHP since balance is calculated
        $loans = Loan::with(['member', 'product', 'disbursements', 'repayments'])
                    ->where('status', 2) // Only disbursed loans
                    ->get()
                    ->filter(function($loan) {
                        return $loan->outstanding_balance > 0; // Use the calculated balance accessor
                    });

        // Pre-select loan if passed
        $selectedLoan = null;
        if ($request->has('loan_id')) {
            $selectedLoan = Loan::with(['member', 'product', 'schedules' => function($query) {
                $query->where('status', 0)->orderBy('payment_date');
            }])->find($request->loan_id);
        }

        return view('admin.repayments.create', compact('loans', 'selectedLoan'));
    }

    /**
     * Store a newly created repayment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|integer|in:1,2,3', // 1=cash, 2=mobile, 3=bank
            'txn_id' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'status' => 'nullable|boolean',
            'date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::find($validated['loan_id']);

            // Prepare repayment data for old structure
            $repaymentData = [
                'type' => $validated['type'],
                'details' => $validated['details'] ?? 'Loan payment',
                'loan_id' => $validated['loan_id'],
                'schedule_id' => 0, // We'll update this if needed
                'amount' => $validated['amount'],
                'date_created' => $validated['date'] ?? now(),
                'added_by' => auth()->id(),
                'status' => $validated['status'] ? 1 : 0,
                'platform' => 'Web',
                'txn_id' => $validated['txn_id'],
                'pay_status' => $validated['status'] ? 'SUCCESS' : 'PENDING',
                'pay_message' => $validated['status'] ? 'Payment confirmed' : 'Payment pending verification',
            ];

            $repayment = Repayment::create($repaymentData);

            // Update loan balance - in old system we don't track separate principal/interest
            if ($validated['status']) {
                $loan->increment('paid', $validated['amount']);
                
                // Check if loan has balance field, if not calculate from paid amount
                if (method_exists($loan, 'outstanding_balance')) {
                    if ($loan->outstanding_balance <= 0) {
                        $loan->update(['status' => 3]); // Completed
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('success', 'Repayment recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error recording repayment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified repayment
     */
    public function show(Repayment $repayment)
    {
        $repayment->load([
            'loan.member',
            'loan.product',
            'loan.branch',
            'addedBy'
        ]);

        return view('admin.repayments.show', compact('repayment'));
    }

    /**
     * Show the form for editing the specified repayment
     */
    public function edit(Repayment $repayment)
    {
        // Only allow editing recent repayments (within 24 hours)
        if ($repayment->created_at->diffInHours(now()) > 24) {
            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('error', 'Cannot edit repayments older than 24 hours.');
        }

        $repayment->load('loan.member');

        return view('admin.repayments.edit', compact('repayment'));
    }

    /**
     * Update the specified repayment
     */
    public function update(Request $request, Repayment $repayment)
    {
        // Only allow editing recent repayments
        if ($repayment->date_created && $repayment->date_created->diffInHours(now()) > 24) {
            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('error', 'Cannot edit repayments older than 24 hours.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|integer|in:1,2,3',
            'txn_id' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'status' => 'nullable|boolean',
            'date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $loan = $repayment->loan;
            $oldAmount = $repayment->amount;
            $wasConfirmed = $repayment->status == 1;

            // Reverse the old repayment effects if it was confirmed
            if ($wasConfirmed) {
                $loan->decrement('paid', $oldAmount);
            }

            // Update repayment data
            $updateData = [
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'details' => $validated['details'],
                'txn_id' => $validated['txn_id'],
                'status' => $validated['status'] ? 1 : 0,
                'pay_status' => $validated['status'] ? 'SUCCESS' : 'PENDING',
                'pay_message' => $validated['status'] ? 'Payment confirmed' : 'Payment pending verification',
            ];

            if (isset($validated['date'])) {
                $updateData['date_created'] = $validated['date'];
            }

            $repayment->update($updateData);

            // Apply new repayment if confirmed
            if ($validated['status']) {
                $loan->increment('paid', $validated['amount']);
                
                // Check loan status
                if (method_exists($loan, 'outstanding_balance')) {
                    if ($loan->outstanding_balance <= 0) {
                        $loan->update(['status' => 3]); // Completed
                    } else {
                        $loan->update(['status' => 2]); // Active
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('success', 'Repayment updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating repayment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified repayment
     */
    public function destroy(Repayment $repayment)
    {
        // Only allow deletion of recent repayments (within 24 hours)
        if ($repayment->date_created && $repayment->date_created->diffInHours(now()) > 24) {
            return redirect()->back()
                            ->with('error', 'Cannot delete repayments older than 24 hours.');
        }

        try {
            DB::beginTransaction();

            $loan = $repayment->loan;

            // Reverse the repayment effects if it was confirmed
            if ($repayment->status == 1) {
                $loan->decrement('paid', $repayment->amount);
                
                // Update loan status back to active
                $loan->update(['status' => 2]); // Back to active
            }

            $repayment->delete();

            DB::commit();

            return redirect()->route('admin.repayments.index')
                            ->with('success', 'Repayment deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error deleting repayment: ' . $e->getMessage());
        }
    }

    /**
     * Calculate repayment breakdown
     */
    private function calculateRepaymentBreakdown(Loan $loan, $amount)
    {
        $breakdown = [
            'principal' => 0,
            'interest' => 0,
            'fees' => 0,
            'penalty' => 0
        ];

        // Get next due schedule
        $schedule = LoanSchedule::where('loan_id', $loan->id)
                               ->where('status', 0)
                               ->orderBy('payment_date')
                               ->first();

        if ($schedule) {
            $remaining = $amount;

            // First pay penalties and fees
            if ($schedule->penalty > 0) {
                $penaltyPayment = min($remaining, $schedule->penalty);
                $breakdown['penalty'] = $penaltyPayment;
                $remaining -= $penaltyPayment;
            }

            if ($schedule->fees > 0 && $remaining > 0) {
                $feesPayment = min($remaining, $schedule->fees);
                $breakdown['fees'] = $feesPayment;
                $remaining -= $feesPayment;
            }

            // Then pay interest
            if ($schedule->interest > 0 && $remaining > 0) {
                $interestPayment = min($remaining, $schedule->interest);
                $breakdown['interest'] = $interestPayment;
                $remaining -= $interestPayment;
            }

            // Finally pay principal
            if ($remaining > 0) {
                $breakdown['principal'] = $remaining;
            }
        } else {
            // If no schedule, assume it's all principal
            $breakdown['principal'] = $amount;
        }

        return $breakdown;
    }

    /**
     * Update loan schedules after repayment
     */
    private function updateLoanSchedules(Loan $loan, Repayment $repayment)
    {
        $schedules = LoanSchedule::where('loan_id', $loan->id)
                                ->where('status', 0)
                                ->orderBy('payment_date')
                                ->get();

        $remainingAmount = $repayment->amount;

        foreach ($schedules as $schedule) {
            if ($remainingAmount <= 0) break;

            $totalDue = $schedule->payment + $schedule->penalty + $schedule->fees;
            
            if ($remainingAmount >= $totalDue) {
                // Full payment for this schedule
                $schedule->update([
                    'status' => 1, // Paid
                    'paid_amount' => $totalDue,
                    'payment_date' => $repayment->date
                ]);
                $remainingAmount -= $totalDue;
            } else {
                // Partial payment
                $schedule->update([
                    'paid_amount' => $remainingAmount,
                    'status' => 2 // Partially paid
                ]);
                $remainingAmount = 0;
            }
        }
    }

    /**
     * Get loan details for AJAX
     */
    public function getLoanDetails(Loan $loan)
    {
        $loan->load(['member', 'product', 'schedules' => function($query) {
            $query->where('status', '!=', 1)->orderBy('payment_date'); // Not fully paid schedules
        }]);

        $nextDue = $loan->schedules->first();

        return response()->json([
            'success' => true,
            'loan' => [
                'id' => $loan->id,
                'code' => $loan->code,
                'member_name' => $loan->member->fname . ' ' . $loan->member->lname,
                'principal' => $loan->principal,
                'balance' => $loan->outstanding_balance ?? ($loan->principal - $loan->paid),
                'next_due_date' => $nextDue ? $nextDue->payment_date : null,
                'next_due_amount' => $nextDue ? $nextDue->payment : 0,
                'interest_due' => $nextDue ? $nextDue->interest : 0,
                'penalty' => $nextDue ? ($nextDue->penalty ?? 0) : 0,
                'fees' => $nextDue ? ($nextDue->fees ?? 0) : 0,
            ]
        ]);
    }

    /**
     * Generate repayment receipt
     */
    public function receipt(Repayment $repayment)
    {
        $repayment->load([
            'loan.member',
            'loan.product',
            'loan.branch',
            'addedBy'
        ]);

        return view('admin.repayments.receipt', compact('repayment'));
    }
}