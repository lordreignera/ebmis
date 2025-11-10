<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Product;
use App\Models\Branch;
use App\Models\LoanSchedule;
use App\Models\Guarantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class LoanController extends Controller
{
    /**
     * Display a listing of loans from all loan tables
     */
    public function index(Request $request)
    {
        // Get loans from both tables with unified structure
        $personalLoans = PersonalLoan::with(['member', 'product', 'branch', 'addedBy'])
            ->selectRaw("
                id, code, member_id, product_type, principal, interest, period,
                installment as max_installment, branch_id, status, verified, added_by,
                datecreated as loan_date,
                datecreated as created_at, NULL as updated_at,
                'personal' as loan_type_display,
                NULL as group_id
            ");

        $groupLoans = GroupLoan::with(['group.members', 'product', 'branch', 'addedBy'])
            ->selectRaw("
                id, code, group_id, product_type, principal, interest, period,
                NULL as max_installment, branch_id, status, verified, added_by,
                datecreated as loan_date,
                datecreated as created_at, NULL as updated_at,
                'group' as loan_type_display
            ");

        // Apply search filters to each query
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            
            $personalLoans->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($memberQuery) use ($search) {
                      $memberQuery->where('fname', 'like', "%{$search}%")
                                  ->orWhere('lname', 'like', "%{$search}%")
                                  ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $personalLoans->where('status', $request->status);
            $groupLoans->where('status', $request->status);
        }

        // Apply branch filter
        if ($request->has('branch_id') && $request->branch_id) {
            $personalLoans->where('branch_id', $request->branch_id);
            $groupLoans->where('branch_id', $request->branch_id);
        }

        // Combine all loans and paginate
        $allPersonalLoans = $personalLoans->get()->map(function($loan) {
            $loan->table_source = 'personal_loans';
            return $loan;
        });

        $allGroupLoans = $groupLoans->get()->map(function($loan) {
            $loan->table_source = 'group_loans';
            // Create a virtual member object for group loans to maintain consistency with the view
            if ($loan->group && $loan->group->members->count() > 0) {
                // Use the first member as the display member, but mark it as a group loan
                $firstMember = $loan->group->members->first();
                $loan->member = (object) [
                    'id' => $loan->group_id,
                    'fname' => $loan->group->name,
                    'lname' => '(Group)',
                    'contact' => $firstMember->contact ?? 'N/A',
                    'pp_file' => null,
                    'status' => 'approved', // Groups should be approved to have loans
                    'isApproved' => function() { return true; }
                ];
                $loan->member_id = $loan->group_id;
            } else {
                // Fallback for groups without members
                $loan->member = (object) [
                    'id' => $loan->group_id,
                    'fname' => $loan->group->name ?? 'Unknown Group',
                    'lname' => '(Group)',
                    'contact' => 'N/A',
                    'pp_file' => null,
                    'status' => 'approved',
                    'isApproved' => function() { return true; }
                ];
                $loan->member_id = $loan->group_id;
            }
            return $loan;
        });

        // Combine and sort all loans by date
        $allLoans = $allPersonalLoans->concat($allGroupLoans)
            ->sortByDesc('loan_date')
            ->values();

        // Manual pagination
        $currentPage = $request->get('page', 1);
        $perPage = 20;
        $total = $allLoans->count();
        $offset = ($currentPage - 1) * $perPage;
        
        $loans = $allLoans->slice($offset, $perPage)->values();

        // Create pagination object
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $loans,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $branches = Branch::active()->get();
        $products = Product::loanProducts()->active()->get();

        // Get loan type and period for page title
        $loanType = $request->type ?? 'all';
        $repayPeriod = $request->period ?? 'all';

        // Get statistics from all tables
        $stats = [
            'total' => PersonalLoan::count() + GroupLoan::count(),
            'pending' => PersonalLoan::where('status', 0)->count() + GroupLoan::where('status', 0)->count(),
            'approved' => PersonalLoan::where('status', 1)->count() + GroupLoan::where('status', 1)->count(),
            'disbursed' => PersonalLoan::where('status', 2)->count() + GroupLoan::where('status', 2)->count(),
            'completed' => PersonalLoan::where('status', 3)->count() + GroupLoan::where('status', 3)->count(),
            'total_value' => PersonalLoan::sum('principal') + GroupLoan::sum('principal'),
        ];

        return view('admin.loans.index', compact('loans', 'branches', 'products', 'stats', 'loanType', 'repayPeriod'));
    }

    /**
     * Show the form for creating a new loan
     */
    public function create(Request $request)
    {
        // Get loan type and period from request
        $loanType = $request->type ?? 'personal';
        $repayPeriod = $request->period ?? 'daily';

        // Get eligible members for loan application (only approved and verified members WITHOUT active loans)
        $members = Member::with(['branch', 'loans.schedules'])
                         ->approved()
                         ->verified()
                         ->notDeleted()
                         ->whereDoesntHave('loans', function($query) {
                             $query->whereIn('status', [1, 2]) // Approved or Disbursed
                                   ->whereHas('schedules', function($subQuery) {
                                       $subQuery->where('status', 0); // Unpaid schedules
                                   });
                         })
                         ->orderBy('fname')
                         ->orderBy('lname')
                         ->get();
        
        // Get groups for group loans (using verified column instead of status)
        $groups = \App\Models\Group::where('verified', 1)->get();
        
        // Filter products based on loan type and period
        $productsQuery = Product::loanProducts()->active();
        
        if ($loanType === 'personal') {
            $productsQuery->where('loan_type', 1); // 1 = Individual/Personal
        } elseif ($loanType === 'group') {
            $productsQuery->where('loan_type', 2); // 2 = Group
        }
        
        if ($repayPeriod) {
            // Map repay period to period_type based on actual database values:
            // period_type: 1=Weekly, 2=Monthly, 3=Daily
            $periodTypeMap = [
                'daily' => 3,
                'weekly' => 1,
                'monthly' => 2
            ];
            
            if (isset($periodTypeMap[$repayPeriod])) {
                $productsQuery->where('period_type', $periodTypeMap[$repayPeriod]);
            }
        }
        
        $products = $productsQuery->get();
        $branches = Branch::active()->get();

        // Pre-select member if passed
        $selectedMember = null;
        if ($request->has('member_id')) {
            $selectedMember = Member::find($request->member_id);
        }

        // Determine which view to load based on type and period
        $viewName = "admin.loans.create_{$loanType}_{$repayPeriod}";
        
        // If specific view doesn't exist, fall back to generic create view
        if (!view()->exists($viewName)) {
            $viewName = 'admin.loans.create';
        }

        return view($viewName, compact('members', 'groups', 'products', 'branches', 'selectedMember', 'loanType', 'repayPeriod'));
    }

    /**
     * Store a newly created loan
     */
    public function store(Request $request)
    {
        $loanType = $request->input('loan_type', 'personal');
        
        // Different validation rules for personal vs group loans
        if ($loanType === 'group') {
            $validated = $request->validate([
                'group_id' => 'required|exists:groups,id',
                'product_type' => 'required|exists:products,id',
                'interest' => 'required|numeric|min:0|max:100',
                'period' => 'required|integer|min:1',
                'principal' => 'nullable|numeric|min:100',
                'total_amount' => 'nullable|numeric|min:1000',
                'equal_sharing' => 'required|in:yes,no',
                'max_installment' => 'required|numeric|min:1',
                'branch_id' => 'required|exists:branches,id',
                'loan_type' => 'required|in:personal,group',
                'repay_period' => 'required|in:daily,weekly,monthly',
                'collection_days' => 'nullable|array',
                'collection_day' => 'nullable|string',
                'meeting_day' => 'nullable|string',
                'meeting_time' => 'nullable|string',
                'meeting_location' => 'nullable|string',
                'loan_purpose' => 'nullable|string',
                'guarantor_required' => 'nullable|string',
                'collateral_type' => 'nullable|string',
            ]);
        } else {
            $validated = $request->validate([
                'member_id' => 'required|exists:members,id',
                'product_type' => 'required|exists:products,id',
                'interest' => 'required|numeric|min:0|max:100',
                'period' => 'required|integer|min:1',
                'principal' => 'required|numeric|min:500',
                'max_installment' => 'required|numeric|min:1',
                'branch_id' => 'required|exists:branches,id',
                'loan_type' => 'required|in:personal,group',
                'repay_period' => 'required|in:daily,weekly,monthly',
                'repay_strategy' => 'required|integer|in:1,2,3',
                'business_name' => 'required|string|max:255',
                'business_contact' => 'required|string|max:255',
                'business_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'bank_statement' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'business_photos' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'monthly_income' => 'nullable|numeric',
                'weekly_income' => 'nullable|numeric',
                'loan_purpose' => 'nullable|string',
            ]);
        }

        try {
            DB::beginTransaction();

            if ($loanType === 'personal') {
                // CRITICAL: Validate that the member is approved before allowing loan creation
                $member = Member::find($validated['member_id']);
                if (!$member->isApproved()) {
                    return redirect()->back()
                                   ->withInput()
                                   ->with('error', 'Loan application denied: Member ' . $member->fname . ' ' . $member->lname . 
                                          ' is not approved. Only approved members can apply for loans. Current status: ' . 
                                          $member->status_display);
                }

                // CRITICAL: Check if member has any active loans (loans with unpaid repayment schedules)
                if ($member->hasActiveLoan()) {
                    return redirect()->back()
                                   ->withInput()
                                   ->with('error', 'Loan application denied: Member ' . $member->fname . ' ' . $member->lname . 
                                          ' has an active loan with outstanding repayment schedules. Please clear all existing loan obligations before applying for a new loan.');
                }
                
                // Generate personal loan code
                $validated['code'] = $this->generateLoanCode('P', $validated['repay_period']);
                
                // Map business fields to database fields
                $validated['repay_name'] = $validated['business_name'];
                $validated['repay_address'] = $validated['business_contact'];
                
                // Handle file uploads for personal loans
                if ($request->hasFile('business_license')) {
                    $validated['trading_file'] = $request->file('business_license')->store('loan-documents', 'public');
                }

                if ($request->hasFile('bank_statement')) {
                    $validated['bank_file'] = $request->file('bank_statement')->store('loan-documents', 'public');
                }

                if ($request->hasFile('business_photos')) {
                    $validated['business_file'] = $request->file('business_photos')->store('loan-documents', 'public');
                }

                $validated['installment'] = $validated['max_installment'];
                $validated['added_by'] = auth()->id();
                $validated['status'] = 0; // Pending approval
                $validated['created_at'] = now();
                $validated['updated_at'] = now();

                // Create the personal loan
                $loan = PersonalLoan::create($validated);
                $loanCode = $validated['code'];
                
            } else {
                // Handle group loans
                $group = \App\Models\Group::find($validated['group_id']);
                if (!$group || $group->verified != 1) {
                    return redirect()->back()
                                   ->withInput()
                                   ->with('error', 'Loan application denied: Selected group is not verified.');
                }

                // CRITICAL: Check if any group members have active loans
                $membersWithActiveLoans = $group->members()->whereHas('loans', function($query) {
                    $query->whereIn('status', [1, 2]) // approved or disbursed loans
                          ->whereHas('schedules', function($scheduleQuery) {
                              $scheduleQuery->where('status', 0); // unpaid schedules
                          });
                })->get();

                if ($membersWithActiveLoans->count() > 0) {
                    $memberNames = $membersWithActiveLoans->pluck('fname', 'lname')->map(function($lname, $fname) {
                        return $fname . ' ' . $lname;
                    })->implode(', ');
                    
                    return redirect()->back()
                                   ->withInput()
                                   ->with('error', 'Group loan application denied: The following group members have active loans with outstanding schedules: ' . 
                                          $memberNames . '. All group members must clear their existing loan obligations before applying for a group loan.');
                }
                
                // Generate group loan code
                $validated['code'] = $this->generateLoanCode('G', $validated['repay_period']);
                
                // Handle equal sharing logic
                if ($validated['equal_sharing'] === 'yes' && isset($validated['total_amount'])) {
                    $memberCount = $group->members()->count();
                    if ($memberCount > 0) {
                        $validated['principal'] = $validated['total_amount'] / $memberCount;
                    }
                }
                
                // Set required fields for group loan
                $validated['added_by'] = auth()->id();
                $validated['status'] = 0; // Pending approval
                $validated['verified'] = false;

                // Create the group loan using GroupLoan model
                $loan = \App\Models\GroupLoan::create($validated);
                $loanCode = $validated['code'];
            }

            DB::commit();

            return redirect()->route('admin.loans.index')
                           ->with('success', 'Loan application submitted successfully. Loan Code: ' . $loanCode);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Loan creation failed: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to create loan application. Please try again.');
        }
    }

    /**
     * Generate a loan code based on type and period
     */
    private function generateLoanCode($type, $period)
    {
        $currentTime = now();
        $periodCode = strtoupper(substr($period, 0, 1)); // D, W, M
        $dailyCount = PersonalLoan::whereDate('created_at', today())->count() + 
                     GroupLoan::whereDate('created_at', today())->count() + 1;
        
        return $type . $periodCode . 'LOAN' . $currentTime->format('ymdHi') . sprintf('%03d', $dailyCount);
    }

    /**
     * Display the specified loan
     */
    public function show(Loan $loan)
    {
        $loan->load([
            'member.country',
            'member.branch',
            'product',
            'branch',
            'addedBy',
            'assignedTo',
            'repayments.addedBy',
            'disbursements.addedBy',
            'schedules',
            'guarantors.member',
            'charges',
            'attachments'
        ]);

        return view('admin.loans.show', compact('loan'));
    }

    /**
     * Show the form for editing the specified loan
     */
    public function edit(Loan $loan)
    {
        // Only allow editing of pending loans
        if ($loan->status > 0) {
            return redirect()->route('admin.loans.show', $loan)
                            ->with('error', 'Cannot edit approved/disbursed loans.');
        }

        // Get eligible members for loan application (only approved members can apply for loans)
        $members = Member::approved()->verified()->notDeleted()->get();
        $products = Product::loanProducts()->active()->get();
        $branches = Branch::active()->get();
        $guarantors = $loan->guarantors()->with('member')->get();

        return view('admin.loans.edit', compact('loan', 'members', 'products', 'branches', 'guarantors'));
    }

    /**
     * Update the specified loan
     */
    public function update(Request $request, Loan $loan)
    {
        // Only allow editing of pending loans
        if ($loan->status > 0) {
            return redirect()->route('admin.loans.show', $loan)
                            ->with('error', 'Cannot edit approved/disbursed loans.');
        }

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'product_type' => 'required|exists:products,id',
            'interest' => 'required|numeric|min:0|max:100',
            'period' => 'required|integer|min:1',
            'principal' => 'required|numeric|min:1000',
            'installment' => 'required|numeric|min:1',
            'branch_id' => 'required|exists:branches,id',
            'trading_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'business_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'repay_strategy' => 'nullable|integer',
            'repay_name' => 'nullable|string|max:1000',
            'repay_address' => 'nullable|string|max:1000',
            'comments' => 'nullable|string',
        ]);

        // CRITICAL: Validate that the member is approved before allowing loan updates
        $member = Member::find($validated['member_id']);
        if (!$member->isApproved()) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Loan update denied: Member ' . $member->fname . ' ' . $member->lname . 
                                  ' is not approved. Only approved members can have loans. Current status: ' . 
                                  $member->status_display);
        }

        // Handle file uploads
        if ($request->hasFile('trading_file')) {
            if ($loan->trading_file) {
                Storage::disk('public')->delete($loan->trading_file);
            }
            $validated['trading_file'] = $request->file('trading_file')->store('loan-documents', 'public');
        }

        if ($request->hasFile('bank_file')) {
            if ($loan->bank_file) {
                Storage::disk('public')->delete($loan->bank_file);
            }
            $validated['bank_file'] = $request->file('bank_file')->store('loan-documents', 'public');
        }

        if ($request->hasFile('business_file')) {
            if ($loan->business_file) {
                Storage::disk('public')->delete($loan->business_file);
            }
            $validated['business_file'] = $request->file('business_file')->store('loan-documents', 'public');
        }

        $loan->update($validated);

        // Regenerate loan schedule if principal or terms changed
        if ($loan->wasChanged(['principal', 'interest', 'period', 'installment'])) {
            // Delete existing schedules
            $loan->schedules()->delete();
            // Generate new schedule
            $this->generateLoanSchedule($loan);
        }

        return redirect()->route('admin.loans.show', $loan)
                        ->with('success', 'Loan updated successfully.');
    }

    /**
     * Approve a loan
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'loan_type' => 'required|in:personal,group',
            'comments' => 'nullable|string|max:500'
        ]);

        $loanType = $request->input('loan_type');
        
        try {
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
            } else {
                $loan = GroupLoan::findOrFail($id);
            }

            // Check if loan is in correct status for approval
            if ($loan->status != 0) { // Assuming 0 = pending
                return response()->json([
                    'success' => false,
                    'message' => 'Loan is not in pending status and cannot be approved.'
                ], 400);
            }

            $loan->update([
                'status' => 1, // Approved
                'verified' => 1,
                'comments' => $request->input('comments'),
                'date_approved' => now(),
                'approved_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loan approved successfully and is now ready for disbursement.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve loan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a loan
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'loan_type' => 'required|in:personal,group',
            'comments' => 'required|string|max:500'
        ]);

        $loanType = $request->input('loan_type');
        
        try {
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
            } else {
                $loan = GroupLoan::findOrFail($id);
            }

            // Check if loan is in correct status for rejection
            if ($loan->status != 0) { // Assuming 0 = pending
                return response()->json([
                    'success' => false,
                    'message' => 'Loan is not in pending status and cannot be rejected.'
                ], 400);
            }

            $loan->update([
                'status' => 4, // Rejected
                'verified' => 0,
                'Rcomments' => $request->input('comments'),
                'date_rejected' => now(),
                'rejected_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loan has been rejected successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject loan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate loan schedule
     */
    private function generateLoanSchedule(Loan $loan)
    {
        $principal = $loan->principal;
        $interest = $loan->interest / 100; // Convert percentage to decimal
        $period = $loan->period;
        $installment = $loan->installment;
        
        $balance = $principal;
        $monthlyInterestRate = $interest / 12; // Assuming monthly payments
        
        for ($i = 1; $i <= $period; $i++) {
            $interestAmount = $balance * $monthlyInterestRate;
            $principalAmount = $installment - $interestAmount;
            $balance -= $principalAmount;
            
            // Ensure balance doesn't go negative
            if ($balance < 0) {
                $principalAmount += $balance;
                $balance = 0;
            }
            
            LoanSchedule::create([
                'loan_id' => $loan->id,
                'payment_date' => now()->addMonths($i)->format('Y-m-d'),
                'payment' => $installment,
                'interest' => round($interestAmount, 2),
                'principal' => round($principalAmount, 2),
                'balance' => round($balance, 2),
                'status' => 0 // Pending
            ]);
            
            if ($balance <= 0) break;
        }
    }

    /**
     * Get loan calculation for AJAX
     */
    public function calculateLoan(Request $request)
    {
        $principal = $request->principal;
        $interest = $request->interest / 100;
        $period = $request->period;
        
        $monthlyInterestRate = $interest / 12;
        $installment = ($principal * $monthlyInterestRate * pow(1 + $monthlyInterestRate, $period)) / 
                      (pow(1 + $monthlyInterestRate, $period) - 1);
        
        return response()->json([
            'success' => true,
            'installment' => round($installment, 2),
            'total_payable' => round($installment * $period, 2),
            'total_interest' => round(($installment * $period) - $principal, 2)
        ]);
    }

    /**
     * Display eSign loans
     */
    public function esignIndex(Request $request)
    {
        // Define common columns that exist in both tables
        $commonColumns = [
            'id',
            'code', 
            'product_type',
            'interest',
            'period', 
            'principal',
            'status',
            'verified',
            'added_by',
            'datecreated',
            'branch_id',
            'comments',
            'charge_type',
            'date_closed'
        ];
        
        // Get eSign loans from both personal and group loans
        $personalLoans = PersonalLoan::with(['member', 'product', 'branch', 'addedBy'])
                         ->where('is_esign', true)
                         ->select(array_merge($commonColumns, [
                             'member_id',
                             DB::raw("'personal' as loan_type"),
                             DB::raw("NULL as group_id")
                         ]));

        $groupLoans = GroupLoan::with(['group', 'product', 'branch', 'addedBy'])
                      ->where('is_esign', true)
                      ->select(array_merge($commonColumns, [
                          'group_id',
                          DB::raw("'group' as loan_type"),
                          DB::raw("NULL as member_id")
                      ]));

        // Apply filters if needed
        if ($request->filled('status')) {
            $personalLoans->where('status', $request->status);
            $groupLoans->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $personalLoans->where('branch_id', $request->branch_id);
            $groupLoans->where('branch_id', $request->branch_id);
        }

        // Union the queries and paginate
        $loans = $personalLoans->union($groupLoans)->paginate(20);
        
        $stats = [
            'total_esign' => PersonalLoan::where('is_esign', true)->count() + 
                           GroupLoan::where('is_esign', true)->count(),
            'pending_esign' => PersonalLoan::where('is_esign', true)->where('status', 'pending')->count() +
                             GroupLoan::where('is_esign', true)->where('status', 'pending')->count(),
            'approved_esign' => PersonalLoan::where('is_esign', true)->where('status', 'approved')->count() +
                              GroupLoan::where('is_esign', true)->where('status', 'approved')->count(),
            'disbursed_esign' => PersonalLoan::where('is_esign', true)->where('status', 'disbursed')->count() +
                               GroupLoan::where('is_esign', true)->where('status', 'disbursed')->count(),
        ];

        return view('admin.loans.esign', compact('loans', 'stats'));
    }

    /**
     * Display loan approvals
     */
    public function approvalsIndex(Request $request)
    {
        // Define common columns that exist in both tables
        $commonColumns = [
            'id',
            'code', 
            'product_type',
            'interest',
            'period', 
            'principal',
            'status',
            'verified',
            'added_by',
            'datecreated',
            'branch_id',
            'comments',
            'charge_type',
            'date_closed'
        ];
        
        // Get approval pending loans from both personal and group loans
        $personalLoans = PersonalLoan::with(['member', 'product', 'branch', 'addedBy'])
                         ->whereIn('status', [0, 1]) // 0=pending, 1=approved
                         ->select(array_merge($commonColumns, [
                             'member_id',
                             DB::raw("'personal' as loan_type"),
                             DB::raw("NULL as group_id")
                         ]));

        $groupLoans = GroupLoan::with(['group', 'product', 'branch', 'addedBy'])
                      ->whereIn('status', [0, 1]) // 0=pending, 1=approved
                      ->select(array_merge($commonColumns, [
                          'group_id',
                          DB::raw("'group' as loan_type"),
                          DB::raw("NULL as member_id")
                      ]));

        // Apply filters if needed
        if ($request->filled('status')) {
            $statusValue = $request->status === 'pending' ? 0 : ($request->status === 'approved' ? 1 : $request->status);
            $personalLoans->where('status', $statusValue);
            $groupLoans->where('status', $statusValue);
        }

        if ($request->filled('branch_id')) {
            $personalLoans->where('branch_id', $request->branch_id);
            $groupLoans->where('branch_id', $request->branch_id);
        }

        // Union the queries and paginate
        $loans = $personalLoans->union($groupLoans)->paginate(20);
        
        $stats = [
            'pending_approval' => PersonalLoan::where('status', 0)->count() + 
                                GroupLoan::where('status', 0)->count(),
            'approved_loans' => PersonalLoan::where('status', 1)->count() + 
                              GroupLoan::where('status', 1)->count(),
            'pending_amount' => PersonalLoan::where('status', 0)->sum('principal') + 
                              GroupLoan::where('status', 0)->sum('principal'),
            'approved_amount' => PersonalLoan::where('status', 1)->sum('principal') + 
                               GroupLoan::where('status', 1)->sum('principal'),
        ];

        return view('admin.loans.approvals', compact('loans', 'stats'));
    }

    /**
     * Apply common filters to loan queries
     */
    private function applyCommonFilters($query, Request $request)
    {
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($memberQuery) use ($search) {
                      $memberQuery->where('fname', 'like', "%{$search}%")
                                  ->orWhere('lname', 'like', "%{$search}%")
                                  ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && !empty($request->type)) {
            if (in_array($request->type, ['personal', 'group'])) {
                $query->where('loan_type', $request->type);
            }
        }

        // Filter by period
        if ($request->has('period') && !empty($request->period)) {
            if (in_array($request->period, ['daily', 'weekly', 'monthly'])) {
                $query->where('payment_frequency', $request->period);
            }
        }

        // Filter by branch
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Date range filtering
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    }
    
    /**
     * Generate loan schedule using new service
     */
    public function generateScheduleWithService(Request $request)
    {
        try {
            $loanId = $request->input('loan_id');
            $loanType = $request->input('loan_type', 'personal');
            
            // Get loan based on type
            if ($loanType === 'personal') {
                $loan = PersonalLoan::find($loanId);
            } else {
                $loan = GroupLoan::find($loanId);
            }
            
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }
            
            // Use the loan schedule service
            $scheduleService = app(\App\Services\LoanScheduleService::class);
            $result = $scheduleService->generateAndSaveSchedule($loan);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Loan schedule generated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate loan schedule'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Schedule generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating schedule: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Calculate loan fees using fee management service
     */
    public function calculateFeesWithService(Request $request)
    {
        try {
            $loanId = $request->input('loan_id');
            $loanType = $request->input('loan_type', 'personal');
            
            // Get loan based on type
            if ($loanType === 'personal') {
                $loan = PersonalLoan::find($loanId);
            } else {
                $loan = GroupLoan::find($loanId);
            }
            
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }
            
            // Use the fee management service
            $feeService = app(\App\Services\FeeManagementService::class);
            $fees = $feeService->calculateLoanFees($loan);
            $disbursementAmount = $feeService->calculateDisbursementAmount($loan);
            
            return response()->json([
                'success' => true,
                'fees' => $fees,
                'disbursement_calculation' => $disbursementAmount
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Fee calculation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error calculating fees: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get loan eligibility check using approval service
     */
    public function checkEligibilityWithService(Request $request)
    {
        try {
            $loanId = $request->input('loan_id');
            $loanType = $request->input('loan_type', 'personal');
            
            // Get loan based on type
            if ($loanType === 'personal') {
                $loan = PersonalLoan::find($loanId);
            } else {
                $loan = GroupLoan::find($loanId);
            }
            
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }
            
            // Use the loan approval service
            $approvalService = app(\App\Services\LoanApprovalService::class);
            $eligibility = $approvalService->checkLoanEligibility($loan);
            
            return response()->json([
                'success' => true,
                'eligibility' => $eligibility
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Eligibility check error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking eligibility: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Display loan agreements pending signature
     */
    public function agreements(Request $request)
    {
        // Build query for loans needing signature (approved = 1, but not yet signed)
        $personalLoans = PersonalLoan::with(['member', 'product', 'branch'])
            ->where('status', 1) // Approved
            ->where('verified', 1) // Verified
            ->selectRaw("
                id, code, member_id, NULL as group_id, product_type, principal,
                status, verified, otp_code, otp_expires_at, signature_status,
                signature_date, signature_comments, datecreated as created_at,
                'personal' as loan_type
            ");

        $groupLoans = GroupLoan::with(['group.members', 'product', 'branch'])
            ->where('status', 1) // Approved  
            ->where('verified', 1) // Verified
            ->selectRaw("
                id, code, NULL as member_id, group_id, product_type, principal,
                status, verified, otp_code, otp_expires_at, signature_status,
                signature_date, signature_comments, datecreated as created_at,
                'group' as loan_type
            ");

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            
            $personalLoans->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($memberQuery) use ($search) {
                      $memberQuery->where('fname', 'like', "%{$search}%")
                                  ->orWhere('lname', 'like', "%{$search}%");
                  });
            });
            
            $groupLoans->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('group', function($groupQuery) use ($search) {
                      $groupQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply loan type filter
        if ($request->filled('loan_type')) {
            if ($request->loan_type === 'personal') {
                $groupLoans = $groupLoans->whereRaw('0 = 1'); // Exclude group loans
            } elseif ($request->loan_type === 'group') {
                $personalLoans = $personalLoans->whereRaw('0 = 1'); // Exclude personal loans
            }
        }

        // Apply signature status filter
        if ($request->filled('status')) {
            $status = $request->status;
            
            if ($status === 'pending') {
                $personalLoans->where(function($q) {
                    $q->whereNull('signature_status')
                      ->orWhere('signature_status', 'pending');
                });
                $groupLoans->where(function($q) {
                    $q->whereNull('signature_status')
                      ->orWhere('signature_status', 'pending');
                });
            } elseif ($status === 'signed') {
                $personalLoans->where('signature_status', 'signed');
                $groupLoans->where('signature_status', 'signed');
            } elseif ($status === 'expired') {
                $personalLoans->where(function($q) {
                    $q->where('otp_expires_at', '<', now())
                      ->whereNotNull('otp_code')
                      ->where(function($sq) {
                          $sq->whereNull('signature_status')
                            ->orWhere('signature_status', 'pending');
                      });
                });
                $groupLoans->where(function($q) {
                    $q->where('otp_expires_at', '<', now())
                      ->whereNotNull('otp_code')
                      ->where(function($sq) {
                          $sq->whereNull('signature_status')
                            ->orWhere('signature_status', 'pending');
                      });
                });
            }
        }

        // Combine and paginate
        $loansQuery = $personalLoans->union($groupLoans)->orderBy('created_at', 'desc');
        
        // Get paginated results
        $loans = DB::table(DB::raw("({$loansQuery->toSql()}) as combined_loans"))
            ->mergeBindings($loansQuery->getQuery())
            ->paginate(20);

        // Transform results for display
        $transformedLoans = $loans->through(function($loan) {
            // Get member or group name
            if ($loan->loan_type === 'personal' && $loan->member_id) {
                $member = Member::find($loan->member_id);
                $loan->member_name = $member ? "{$member->fname} {$member->lname}" : 'Unknown Member';
                $loan->member_contact = $member ? $member->contact : '';
            } else if ($loan->loan_type === 'group' && $loan->group_id) {
                $group = \App\Models\Group::find($loan->group_id);
                $loan->member_name = $group ? $group->name : 'Unknown Group';
                $loan->member_contact = '';
            }

            $loan->loan_code = $loan->code;
            $loan->amount = $loan->principal;
            
            // Determine signature status
            if (empty($loan->signature_status)) {
                $loan->signature_status = 'pending';
            }
            
            // Parse dates
            $loan->created_at = \Carbon\Carbon::parse($loan->created_at);
            if ($loan->otp_expires_at) {
                $loan->otp_expires_at = \Carbon\Carbon::parse($loan->otp_expires_at);
            }

            return $loan;
        });

        // Calculate statistics
        $statistics = [
            'pending_signature' => DB::table(DB::raw("({$personalLoans->union($groupLoans)->toSql()}) as stats"))
                ->mergeBindings($personalLoans->union($groupLoans)->getQuery())
                ->where(function($q) {
                    $q->whereNull('signature_status')
                      ->orWhere('signature_status', 'pending');
                })
                ->count(),
            'signed_today' => DB::table(DB::raw("({$personalLoans->union($groupLoans)->toSql()}) as stats"))
                ->mergeBindings($personalLoans->union($groupLoans)->getQuery())
                ->where('signature_status', 'signed')
                ->whereDate('signature_date', today())
                ->count(),
            'total_amount' => number_format(
                DB::table(DB::raw("({$personalLoans->union($groupLoans)->toSql()}) as stats"))
                    ->mergeBindings($personalLoans->union($groupLoans)->getQuery())
                    ->sum('principal')
            ),
            'expired_signatures' => DB::table(DB::raw("({$personalLoans->union($groupLoans)->toSql()}) as stats"))
                ->mergeBindings($personalLoans->union($groupLoans)->getQuery())
                ->where('otp_expires_at', '<', now())
                ->whereNotNull('otp_code')
                ->where(function($q) {
                    $q->whereNull('signature_status')
                      ->orWhere('signature_status', 'pending');
                })
                ->count(),
        ];

        return view('admin.loans.agreements', compact('loans', 'statistics'));
    }

    /**
     * Send OTP for loan agreement signing
     */
    public function sendOTP(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|in:personal,group'
        ]);

        try {
            // Get the loan
            if ($request->loan_type === 'personal') {
                $loan = PersonalLoan::with('member')->find($request->loan_id);
                $recipient = $loan->member;
                $phone = $loan->member->contact;
            } else {
                $loan = GroupLoan::with('group.members')->find($request->loan_id);
                // For group loans, send to group leader or first member
                $recipient = $loan->group->members->first();
                $phone = $recipient->contact;
            }

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }

            // Generate OTP
            $otp = sprintf('%06d', mt_rand(0, 999999));
            $expiresAt = now()->addMinutes(30); // OTP expires in 30 minutes

            // Update loan with OTP
            $loan->update([
                'otp_code' => $otp,
                'otp_expires_at' => $expiresAt,
                'signature_status' => 'pending'
            ]);

            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);

            // Send SMS (implement your SMS service here)
            $message = "Your loan agreement OTP is: {$otp}. This code expires in 30 minutes. Do not share this code.";
            
            // Log the OTP sending (in production, integrate with SMS service)
            \Log::info("OTP sent to {$formattedPhone}: {$otp}");

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to member\'s phone number'
            ]);

        } catch (\Exception $e) {
            \Log::error('OTP sending error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error sending OTP: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sign loan agreement with OTP verification
     */
    public function signAgreement(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|in:personal,group',
            'otp_code' => 'required|string|size:6',
            'comments' => 'nullable|string|max:500'
        ]);

        try {
            // Get the loan
            if ($request->loan_type === 'personal') {
                $loan = PersonalLoan::find($request->loan_id);
            } else {
                $loan = GroupLoan::find($request->loan_id);
            }

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }

            // Verify OTP
            if ($loan->otp_code !== $request->otp_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code'
                ]);
            }

            // Check if OTP has expired
            if ($loan->otp_expires_at && now()->gt($loan->otp_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new OTP.'
                ]);
            }

            // Sign the agreement
            $loan->update([
                'signature_status' => 'signed',
                'signature_date' => now(),
                'signature_comments' => $request->comments,
                'otp_code' => null, // Clear OTP after successful use
                'otp_expires_at' => null
            ]);

            // Log the signing
            \Log::info("Loan agreement signed - ID: {$loan->id}, Type: {$request->loan_type}");

            return response()->json([
                'success' => true,
                'message' => 'Loan agreement signed successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Agreement signing error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error signing agreement: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * View loan agreement PDF
     */
    public function viewAgreement($loanId, $type)
    {
        try {
            // Get loan with related data
            if ($type === 'personal') {
                $loan = PersonalLoan::with(['member', 'product', 'branch'])->find($loanId);
                $borrower = $loan->member;
            } else {
                $loan = GroupLoan::with(['group.members', 'product', 'branch'])->find($loanId);
                $borrower = $loan->group;
            }

            if (!$loan) {
                abort(404, 'Loan not found');
            }

            // Generate loan agreement PDF
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.loans.agreement-pdf', compact('loan', 'borrower', 'type'));
            
            return $pdf->stream("loan-agreement-{$loan->code}.pdf");

        } catch (\Exception $e) {
            \Log::error('Agreement viewing error: ' . $e->getMessage());
            abort(500, 'Error generating agreement');
        }
    }

    /**
     * Download signed loan agreement
     */
    public function downloadSignedAgreement($loanId, $type)
    {
        try {
            // Get loan
            if ($type === 'personal') {
                $loan = PersonalLoan::with(['member', 'product', 'branch'])->find($loanId);
                $borrower = $loan->member;
            } else {
                $loan = GroupLoan::with(['group.members', 'product', 'branch'])->find($loanId);
                $borrower = $loan->group;
            }

            if (!$loan || $loan->signature_status !== 'signed') {
                abort(404, 'Signed agreement not found');
            }

            // Generate signed agreement PDF with signature info
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.loans.signed-agreement-pdf', compact('loan', 'borrower', 'type'));
            
            return $pdf->download("signed-agreement-{$loan->code}.pdf");

        } catch (\Exception $e) {
            \Log::error('Signed agreement download error: ' . $e->getMessage());
            abort(500, 'Error downloading signed agreement');
        }
    }

    /**
     * Format phone number for SMS
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format for Ugandan numbers
        if (substr($phone, 0, 3) === '256') {
            return $phone; // Already has country code
        } elseif (substr($phone, 0, 1) === '0') {
            return '256' . substr($phone, 1); // Replace leading 0 with 256
        } else {
            return '256' . $phone; // Add country code
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
                'status' => $loan->status,
                'product_name' => $loan->product->name ?? 'N/A'
            ]
        ]);
    }
}