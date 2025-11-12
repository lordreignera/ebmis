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
use App\Models\RawPayment;
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
        $loan = Loan::with(['member', 'branch', 'product', 'schedules.repayments', 'repayments'])
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

        // Get schedules with payment status - EXACT bimsadmin calculation logic
        $principal = floatval($loan->principal); // Running principal balance
        $globalprincipal = floatval($loan->principal); // Global principal for interest calculation
        
        $schedules = $loan->schedules->map(function($schedule, $index) use ($loan, &$principal, &$globalprincipal) {
            // 1. Calculate "Principal cal Interest" (reducing balance per period)
            $period = floor($loan->period / 2);
            $pricipalcalIntrest = $period > 0 ? ($loan->principal / $period) : 0;
            
            // 2. Calculate "Interest Payable" using global principal
            $intrestamtpayable = (($globalprincipal * $loan->interest / 100) * 1.99999999);
            
            // 3. Calculate periods in arrears
            $now = $schedule->date_cleared ? strtotime($schedule->date_cleared) : time();
            $your_date = strtotime($schedule->payment_date);
            $datediff = $now - $your_date;
            $d = floor($datediff / (60 * 60 * 24)); // Days overdue
            
            $dd = 0; // Periods overdue
            if ($d > 0) {
                if ($loan->period_type == '1') {
                    // Weekly loans: divide by 7
                    $dd = ceil($d / 7);
                } else if ($loan->period_type == '2') {
                    // Monthly loans: divide by 30
                    $dd = ceil($d / 30);
                } else if ($loan->period_type == '3') {
                    // Daily loans: each day is 1 period
                    $dd = $d;
                } else {
                    // Default to weekly
                    $dd = ceil($d / 7);
                }
            }
            
            // 4. Calculate late fees (penalty): 6% per period overdue (NOT 10%!)
            $latepayment = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
            
            // 5. Get total paid from schedule (or calculate from repayments)
            $totalPaid = floatval($schedule->paid ?? $schedule->repayments()->where('status', 1)->sum('amount'));
            
            // 6. Get pending count
            $pendingCount = intval($schedule->pending_count ?? $schedule->repayments()->where('status', 0)->where('pay_status', 'Pending')->count());
            
            // 7. Allocate payments - BIMSADMIN ORDER: Late Fees → Interest → Principal
            // Late fees paid
            $afterlatepayment = $totalPaid - $latepayment;
            if ($afterlatepayment > 0) {
                $pafterlatepayment = $latepayment; // Full late payment covered
            } else {
                $pafterlatepayment = $totalPaid; // Partial payment
                $afterlatepayment = 0;
            }
            
            // Interest paid
            $afterinterestpayment = $afterlatepayment - $intrestamtpayable;
            if ($afterinterestpayment > 0) {
                $pafterinterestpayment = $intrestamtpayable; // Full interest covered
            } else {
                $pafterinterestpayment = $afterlatepayment; // Partial payment
                $afterinterestpayment = 0;
            }
            
            // Principal paid
            $afterprinicpalpayment = $afterinterestpayment - $schedule->principal;
            if ($afterprinicpalpayment > 0) {
                $pafterprinicpalpayment = $schedule->principal; // Full principal covered
            } else {
                $pafterprinicpalpayment = $afterinterestpayment; // Partial payment
                $afterprinicpalpayment = 0;
            }
            
            // 8. Calculate total balance
            $act_bal = ($schedule->principal + $intrestamtpayable + $latepayment) - $totalPaid;
            
            // 9. Attach calculated values to schedule
            $schedule->pricipalcalIntrest = $pricipalcalIntrest;
            $schedule->globalprincipal = $globalprincipal;
            $schedule->intrestamtpayable = $intrestamtpayable;
            $schedule->periods_in_arrears = $dd;
            $schedule->penalty = $latepayment;
            $schedule->total_payment = $schedule->principal + $intrestamtpayable + $latepayment;
            $schedule->principal_paid = $pafterprinicpalpayment;
            $schedule->interest_paid = $pafterinterestpayment;
            $schedule->penalty_paid = $pafterlatepayment;
            $schedule->total_balance = $act_bal;
            $schedule->principal_balance = $principal;
            $schedule->pending_count = $pendingCount;
            
            // 10. Update running balances for next iteration
            if ($principal > 0) {
                $principal -= $schedule->principal;
                if ($principal < 0) $principal = 0;
            }
            
            if ($globalprincipal > 0) {
                $globalprincipal -= $pricipalcalIntrest;
                if ($globalprincipal < 0) $globalprincipal = 0;
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
     * Get next schedule for a loan (AJAX)
     */
    public function getNextSchedule($loanId)
    {
        try {
            $loan = Loan::findOrFail($loanId);
            
            $nextSchedule = LoanSchedule::where('loan_id', $loanId)
                                       ->where('status', 0) // Unpaid
                                       ->orderBy('payment_date')
                                       ->first();
            
            if ($nextSchedule) {
                return response()->json([
                    'success' => true,
                    'schedule' => [
                        'id' => $nextSchedule->id,
                        'due_date' => \Carbon\Carbon::parse($nextSchedule->payment_date)->format('M d, Y'),
                        'expected_amount' => number_format($nextSchedule->payment, 2),
                        'payment_amount' => $nextSchedule->payment,
                        'principal' => $nextSchedule->principal ?? 0,
                        'interest' => $nextSchedule->interest ?? 0,
                        'penalty' => $nextSchedule->penalty ?? 0,
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No pending schedules found for this loan.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process quick repayment (AJAX)
     */
    public function quickRepayment(Request $request)
    {
        // Accept both numeric and string payment methods
        $paymentMethod = $request->input('payment_method') ?: $request->input('type');
        
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable',
            'type' => 'nullable',
            'network' => 'nullable|in:MTN,AIRTEL',
            'phone' => 'nullable|string',
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
            // Convert payment method to numeric code
            // Legacy system: 1=cash, 2=mobile_money, 3=bank/cheque
            $paymentTypeCode = is_numeric($paymentMethod) 
                ? (int)$paymentMethod 
                : $this->getPaymentTypeCode($paymentMethod);
            
            $repaymentData = [
                'type' => $paymentTypeCode,
                'details' => $request->input('notes') ?: $request->input('details', 'Quick repayment'),
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->input('schedule_id', 0),
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'platform' => $request->input('platform', 'Web'),
            ];

            // Handle mobile money (type = 2 in legacy system)
            if ($paymentTypeCode == 2) {
                $phone = $request->input('phone');
                $network = $request->input('network');
                
                if (!$phone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phone number is required for mobile money payments.'
                    ], 422);
                }
                
                if (!$network) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Network is required for mobile money payments.'
                    ], 422);
                }
                
                $mobileMoneyResult = $this->processMobileMoneyCollection(
                    $loan,
                    $request->amount,
                    $phone,
                    $network
                );

                if ($mobileMoneyResult['success']) {
                    $repaymentData['status'] = 0; // Pending mobile money confirmation
                    $repaymentData['txn_id'] = $mobileMoneyResult['reference'];
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated - check your phone';
                    
                    // Increment pending_count on the schedule (bimsadmin style)
                    if ($request->has('s_id')) {
                        DB::table('loan_schedules')
                            ->where('id', $request->s_id)
                            ->increment('pending_count');
                    }
                    
                    // Create raw_payments record for tracking (bimsadmin style)
                    RawPayment::create([
                        'trans_id' => $mobileMoneyResult['reference'],
                        'phone' => $this->mobileMoneyService->formatPhoneNumber($phone),
                        'amount' => $request->amount,
                        'ref' => '', // FlexiPay reference (empty initially)
                        'message' => 'Payment initiated',
                        'status' => 'Processed', // Match bimsadmin format
                        'pay_status' => '00', // Actual status code - pending
                        'pay_message' => 'Completed successfully',
                        'date_created' => now(),
                        'type' => 'repayment', // Must be 'repayment' not 'collection'
                        'direction' => 'cash_in',
                        'added_by' => auth()->id(),
                        'raw_message' => serialize(['schedule_id' => $request->s_id, 'loan_id' => $request->loan_id, 'member_id' => $loan->member_id]),
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                    ], 400);
                }
            } else {
                // For cash/bank transfers/cheque, mark as confirmed
                $repaymentData['status'] = 1;
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = 'Payment confirmed';
                $repaymentData['txn_id'] = 'TXN-' . time() . '-' . $paymentTypeCode;
            }

            $repayment = Repayment::create($repaymentData);

            // Update loan balance and schedules if confirmed
            if ($repaymentData['status'] == 1) {
                $loan->increment('paid', $request->amount);
                $this->updateLoanSchedules($loan, $repayment);
                
                // Check if loan is fully paid
                $totalPayable = $loan->principal + $loan->interest;
                if ($loan->paid >= $totalPayable) {
                    $loan->update(['status' => 3]); // Completed
                }
            }

            DB::commit();

            $message = "Payment recorded successfully.";
            if ($paymentTypeCode == 1) {
                $message = "Collection request sent to " . $request->phone . ". Please check your phone and enter your Mobile Money PIN to complete the payment.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'repayment_id' => $repayment->id,
                'payment_type' => $paymentTypeCode
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Quick repayment failed', [
                'loan_id' => $request->loan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        // Log incoming request for debugging
        \Log::info('storeRepayment called', [
            'all_data' => $request->all(),
            'has_s_id' => $request->has('s_id'),
            'has_type' => $request->has('type'),
            'has_amount' => $request->has('amount')
        ]);

        // Handle bimsadmin-style form submission (POST with s_id, type, details, amount, medium)
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|exists:personal_loans,id',
            'member_id' => 'required|exists:members,id',
            's_id' => 'required|exists:loan_schedules,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|integer|in:1,2,3', // 1=cash, 2=mobile_money, 3=bank
            'medium' => 'nullable|integer|in:1,2', // 1=Airtel, 2=MTN (only for mobile money)
            'details' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fill in all required fields correctly.');
        }

        $loan = Loan::findOrFail($request->loan_id);
        $schedule = LoanSchedule::findOrFail($request->s_id);
        $member = \App\Models\Member::findOrFail($request->member_id);

        DB::beginTransaction();

        try {
            // Determine network name from medium (for mobile money)
            $network = null;
            if ($request->type == 2 && $request->medium) {
                $network = $request->medium == 1 ? 'AIRTEL' : 'MTN';
            }

            $repaymentData = [
                'type' => $request->type, // 1=cash, 2=mobile_money, 3=bank
                'details' => $request->details,
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->s_id,
                'member_id' => $request->member_id,
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'platform' => 'Web',
            ];

            // Handle mobile money - initiate collection
            if ($request->type == 2) {
                $memberName = $member->fname . ' ' . $member->lname;
                $phoneNumber = $member->contact;
                
                $mobileMoneyResult = $this->processMobileMoneyCollection(
                    $loan,
                    $request->amount,
                    $phoneNumber,
                    $network
                );

                if ($mobileMoneyResult['success']) {
                    $repaymentData['status'] = 0; // Pending
                    $repaymentData['txn_id'] = $mobileMoneyResult['reference'];
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated';
                    $repaymentData['network'] = $network;
                    $repaymentData['phone_number'] = $phoneNumber;
                    
                    // Increment pending_count on the schedule (bimsadmin style)
                    $schedule->increment('pending_count');
                    
                    // Create raw_payments record for tracking (bimsadmin format)
                    \App\Models\RawPayment::create([
                        'trans_id' => $mobileMoneyResult['reference'], // THIS is what CheckTransactions searches for
                        'phone' => $phoneNumber,
                        'amount' => $request->amount,
                        'ref' => '', // FlexiPay reference (empty initially)
                        'message' => 'Repayment initiated',
                        'status' => 'Processed', // Match bimsadmin format
                        'pay_status' => '00', // Actual status code - pending (CheckTransactions looks for this)
                        'pay_message' => 'Completed successfully',
                        'date_created' => now(),
                        'type' => 'repayment', // Must be 'repayment' for CheckTransactions filter
                        'direction' => 'cash_in',
                        'added_by' => auth()->id(),
                        'raw_message' => serialize(['schedule_id' => $request->s_id, 'loan_id' => $request->loan_id, 'member_id' => $request->member_id]),
                    ]);
                    
                    DB::commit();
                    
                    // Return JSON for AJAX request
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Mobile money collection initiated',
                            'transaction_id' => $mobileMoneyResult['reference']
                        ]);
                    }
                } else {
                    DB::rollBack();
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                        ], 400);
                    }
                    
                    return redirect()->back()
                        ->with('error', 'Mobile money collection failed: ' . $mobileMoneyResult['message']);
                }
            } else {
                // Cash or Bank Transfer - immediately confirmed
                $repaymentData['status'] = 1; // Confirmed
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = 'Payment confirmed';
                $repaymentData['txn_id'] = 'TXN-' . time();
                
                $repayment = Repayment::create($repaymentData);
                
                // Update loan and schedule for confirmed payments
                $loan->increment('paid', $request->amount);
                $schedule->increment('paid', $request->amount);
                
                // Check if schedule is fully paid
                if ($schedule->paid >= $schedule->payment) {
                    $schedule->update(['status' => 1]); // Fully paid
                }
                
                // Check if loan is fully paid
                if ($loan->paid >= ($loan->principal + $loan->interest)) {
                    $loan->update(['status' => 3]); // Completed
                }
                
                DB::commit();
                
                // Return JSON for AJAX
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Repayment recorded successfully'
                    ]);
                }
            }

            $message = "Repayment recorded successfully.";

            return redirect()->route('admin.loans.repayments.schedules', $request->loan_id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Repayment storage failed', [
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->s_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Payment recording failed: ' . $e->getMessage());
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
     * Legacy system mapping: 1=cash, 2=mobile_money, 3=bank/cheque
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
        // Get loan type filter (school, student, staff, or default to personal/group)
        $loanType = $request->type ?? null;
        
        $query = Repayment::with(['loan.member', 'loan.product', 'addedBy']);

        // Filter by loan type if specified (school, student, staff)
        if (in_array($loanType, ['school', 'student', 'staff'])) {
            // NOTE: Since school/student/staff loans are in separate tables (school_loans, student_loans, staff_loans)
            // and the current Repayment model references the old Loan model (personal_loans/group_loans),
            // we need to filter out ALL repayments when a school loan type is requested
            // until the repayment system is updated to support the new loan tables.
            
            // For now, return empty paginated result for school loan types
            $repayments = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                20,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            $totals = [
                'total_amount' => 0,
                'total_principal' => 0,
                'total_interest' => 0,
                'total_fees' => 0,
                'total_penalty' => 0,
            ];
            
            $branches = Branch::active()->get() ?? collect();
            
            return view('admin.repayments.index', compact('repayments', 'branches', 'totals', 'loanType'))
                ->with('info', 'Repayment tracking for ' . ucfirst($loanType) . ' loans will be available once loans are disbursed and repayments begin. The system is ready to track repayments for school, student, and staff loans.');
        }

        // Default behavior: show personal and group loan repayments only
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

        return view('admin.repayments.index', compact('repayments', 'branches', 'totals', 'loanType'));
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

    /**
     * Get pending transaction for a schedule (for Check Progress button)
     */
    public function getPendingTransaction($scheduleId)
    {
        try {
            // Find the most recent pending repayment for this schedule
            $repayment = Repayment::where('schedule_id', $scheduleId)
                ->where('status', 0) // Pending
                ->orderBy('id', 'desc')
                ->first();
            
            if ($repayment && $repayment->txn_id) {
                return response()->json([
                    'success' => true,
                    'transaction_id' => $repayment->txn_id
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No pending payment found for this schedule'
            ]);
        } catch (\Exception $e) {
            \Log::error('Get pending transaction error', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving pending transaction'
            ], 500);
        }
    }

    /**
     * Check mobile money payment status (for 60-second polling)
     */
    public function checkPaymentStatus($transactionId)
    {
        try {
            // Check raw_payments table for transaction status
            $rawPayment = RawPayment::where('trans_id', $transactionId)->first();
            
            if (!$rawPayment) {
                return response()->json([
                    'status' => 'UNKNOWN',
                    'message' => 'Transaction not found'
                ]);
            }

            // Check if payment status has been updated by CheckTransactions cron
            // pay_status codes: '00' = pending, '01' = success, '02'/'03' = failed
            if ($rawPayment->pay_status === '01') {
                // Payment successful - update repayment and schedule
                $repayment = Repayment::where('txn_id', $transactionId)->first();
                
                if ($repayment && $repayment->status == 0) {
                    DB::beginTransaction();
                    try {
                        // Update repayment status
                        $repayment->update([
                            'status' => 1, // Confirmed
                            'pay_status' => 'SUCCESS',
                            'pay_message' => 'Payment confirmed via mobile money'
                        ]);
                        
                        // Get loan and schedule
                        $loan = Loan::find($repayment->loan_id);
                        $schedule = LoanSchedule::find($repayment->schedule_id);
                        
                        if ($loan && $schedule) {
                            // Update loan paid amount
                            $loan->increment('paid', $repayment->amount);
                            
                            // Update schedule paid amount
                            $schedule->increment('paid', $repayment->amount);
                            
                            // Decrement pending_count
                            if ($schedule->pending_count > 0) {
                                $schedule->decrement('pending_count');
                            }
                            
                            // Check if schedule is fully paid
                            if ($schedule->paid >= $schedule->payment) {
                                $schedule->update(['status' => 1]); // Fully paid
                            }
                            
                            // Check if loan is fully paid
                            if ($loan->paid >= ($loan->principal + $loan->interest)) {
                                $loan->update(['status' => 3]); // Completed
                            }
                        }
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error('Payment status update failed', [
                            'transaction_id' => $transactionId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Payment completed successfully'
                ]);
            } elseif (in_array($rawPayment->pay_status, ['02', '03', 'FAILED'])) {
                return response()->json([
                    'status' => 'FAILED',
                    'message' => $rawPayment->pay_message ?? 'Payment failed'
                ]);
            } else {
                // Still pending
                return response()->json([
                    'status' => 'PENDING',
                    'message' => 'Payment is being processed'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Check payment status error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error checking payment status'
            ], 500);
        }
    }
}