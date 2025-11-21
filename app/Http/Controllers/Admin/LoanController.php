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
use App\Models\Repayment;
use App\Models\Fee;
use App\Models\FeeType;
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

        // Get eligible members for loan application (only verified members WITHOUT active loans)
        // Note: Using verified() instead of approved() because verified = 1 is the primary indicator
        // of member eligibility, even if status column may still be 'pending'
        // ACTIVE LOAN = Only disbursed loans (status = 2)
        // Exclude members with:
        //   - Disbursed loans (status = 2) - These are ACTIVE loans being repaid
        // Include members with:
        //   - Pending loans (status = 0) - Not yet approved, can apply again
        //   - Approved loans (status = 1) - Approved but NOT disbursed yet, can still apply
        //   - Completed loans (status = 3) - Paid off their loan, can reapply
        //   - Rejected loans (status = 4) - Can reapply
        $members = Member::with(['branch', 'loans'])
                         ->verified()
                         ->notDeleted()
                         ->whereDoesntHave('loans', function($query) {
                             $query->where('status', 2); // Only exclude Disbursed (2) loans - these are ACTIVE
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

            // CRITICAL: Override interest rate with product's interest rate
            // This ensures that the product's rate is always used, regardless of user input
            $product = Product::findOrFail($validated['product_type']);
            $validated['interest'] = $product->interest;

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
                $validated['datecreated'] = now(); // Use datecreated instead of created_at
                $validated['verified'] = 0; // Not verified initially
                $validated['sign_code'] = 0; // Not an eSign loan by default

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
        $dailyCount = PersonalLoan::whereDate('datecreated', today())->count() + 
                     GroupLoan::whereDate('datecreated', today())->count() + 1;
        
        return $type . $periodCode . 'LOAN' . $currentTime->format('ymdHi') . sprintf('%03d', $dailyCount);
    }

    /**
     * Display the specified loan
     */
    public function show(Request $request, $id)
    {
        $loanType = $request->query('type', 'personal');
        
        // Get loan based on type
        if ($loanType === 'group') {
            $loan = GroupLoan::with([
                'group.members',
                'product.charges',
                'branch',
                'addedBy',
                'assignedTo',
                'approvedBy',
                'schedules',
                'guarantors.member',
                'loanMembers.member',
                'disbursements',
                'charges'
            ])->findOrFail($id);
            
            // Set loan_type attribute for view
            $loan->loan_type = 'group';
        } else {
            $loan = PersonalLoan::with([
                'member.country',
                'member.branch',
                'member.savings',
                'product.charges',
                'branch',
                'addedBy',
                'assignedTo',
                'approvedBy',
                'repayments',
                'disbursements',
                'schedules',
                'guarantors.member',
                'charges'
            ])->findOrFail($id);
            
            // Set loan_type attribute for view
            $loan->loan_type = 'personal';
        }

        return view('admin.loans.show', compact('loan', 'loanType'));
    }

    /**
     * Show the form for editing the specified loan
     */
    public function edit(Request $request, $id)
    {
        $loanType = $request->query('type', 'personal');
        
        // Get loan based on type
        if ($loanType === 'group') {
            $loan = GroupLoan::with(['group', 'product', 'branch'])->findOrFail($id);
            $loan->loan_type = 'group';
        } else {
            $loan = PersonalLoan::with(['member', 'product', 'branch'])->findOrFail($id);
            $loan->loan_type = 'personal';
        }
        
        // Only allow editing of pending loans
        if ($loan->status > 0) {
            return redirect()->route('admin.loans.show', ['id' => $id, 'type' => $loanType])
                            ->with('error', 'Cannot edit approved/disbursed loans.');
        }

        // Get eligible members for loan application (only approved members can apply for loans)
        $members = Member::approved()->verified()->notDeleted()->get();
        $groups = \App\Models\Group::where('verified', 1)->get();
        $products = Product::loanProducts()->active()->get();
        $branches = Branch::active()->get();
        $guarantors = $loan->guarantors()->with('member')->get();

        return view('admin.loans.edit', compact('loan', 'members', 'groups', 'products', 'branches', 'guarantors', 'loanType'));
    }

    /**
     * Update the specified loan
     */
    public function update(Request $request, $id)
    {
        $loanType = $request->input('loan_type', 'personal');
        
        // Get loan based on type
        if ($loanType === 'group') {
            $loan = GroupLoan::findOrFail($id);
        } else {
            $loan = PersonalLoan::findOrFail($id);
        }
        
        // Only allow editing of pending loans
        if ($loan->status > 0) {
            return redirect()->route('admin.loans.show', ['id' => $id, 'type' => $loanType])
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

        // CRITICAL: Override interest rate with product's interest rate
        // This ensures that the product's rate is always used, regardless of user input
        $product = Product::findOrFail($validated['product_type']);
        $validated['interest'] = $product->interest;

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

        return redirect()->route('admin.loans.show', ['id' => $id, 'type' => $loanType])
                        ->with('success', 'Loan updated successfully.');
    }

    /**
     * Approve a loan
     */
    public function approve(Request $request, $id)
    {
        \Log::info('Approve loan called', [
            'id' => $id,
            'request_all' => $request->all()
        ]);
        
        $request->validate([
            'loan_type' => 'required|in:personal,group',
            'comments' => 'nullable|string|max:500'
        ]);

        $loanType = $request->input('loan_type');
        
        \Log::info('Looking for loan', [
            'id' => $id,
            'type' => $loanType
        ]);
        
        try {
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
            } else {
                $loan = GroupLoan::findOrFail($id);
            }

            // Check if loan is in correct status for approval
            if ($loan->status != 0) { // Assuming 0 = pending
                $message = 'Loan is not in pending status and cannot be approved.';
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                return back()->with('error', $message);
            }

            // VALIDATE MANDATORY FEES BEFORE APPROVAL (for charge_type = 2)
            if ($loan->charge_type == 2) {
                $product = \App\Models\Product::find($loan->product_type);
                
                if ($product) {
                    $productCharges = $product->charges()->where('isactive', 1)->get();
                    $unpaidFees = [];
                    
                    foreach ($productCharges as $charge) {
                        $memberId = $loanType === 'personal' ? $loan->member_id : null;
                        
                        // Check if this fee is paid for this loan
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
                        
                        if (!$paidFee) {
                            $unpaidFees[] = $charge->name;
                        }
                    }
                    
                    // If there are unpaid mandatory fees, reject the approval
                    if (count($unpaidFees) > 0) {
                        $message = 'Cannot approve loan. The following mandatory fees must be paid first: ' . implode(', ', $unpaidFees);
                        
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $message], 400);
                        }
                        return back()->with('error', $message);
                    }
                }
            }

            $loan->update([
                'status' => 1, // Approved
                'verified' => 1,
                'comments' => $request->input('comments'),
                'date_approved' => now(),
                'approved_by' => auth()->id()
            ]);

            $message = 'Loan approved successfully and is now ready for disbursement.';
            
            // Return JSON for AJAX requests, redirect for form submissions
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            
            return redirect()->route('admin.loans.approvals')->with('success', $message);

        } catch (\Exception $e) {
            $message = 'Failed to approve loan: ' . $e->getMessage();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
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
            // Allow rejection of pending (0) or approved (1) loans only
            if (!in_array($loan->status, [0, 1])) {
                $message = 'Only pending or approved loans can be rejected. This loan has already been disbursed or completed.';
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                return back()->with('error', $message);
            }

            $loan->update([
                'status' => 4, // Rejected
                'verified' => 0,
                'Rcomments' => $request->input('comments'),
                'date_rejected' => now(),
                'rejected_by' => auth()->id()
            ]);

            $message = 'Loan has been rejected successfully.';
            
            // Return JSON for AJAX requests, redirect for form submissions
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            
            return redirect()->route('admin.loans.approvals')->with('success', $message);

        } catch (\Exception $e) {
            $message = 'Failed to reject loan: ' . $e->getMessage();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * Pay upfront fees for a loan
     */
    public function payFees(Request $request, $id)
    {
        $request->validate([
            'loan_type' => 'required|in:personal,group',
            'fees' => 'required|array',
            'fees.*' => 'integer',
            'payment_method' => 'required|integer|in:1,2,3,4',
            'payment_reference' => 'nullable|string|max:100',
            'payment_notes' => 'nullable|string|max:500'
        ]);

        $loanType = $request->input('loan_type');
        
        try {
            DB::beginTransaction();

            // Get the loan
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
                $memberId = $loan->member_id;
            } else {
                $loan = GroupLoan::with('group')->findOrFail($id);
                // For group loans, we might need to handle this differently
                // For now, let's use the first member of the group
                $memberId = $loan->group->members()->first()->id ?? null;
            }

            if (!$memberId) {
                throw new \Exception('Could not determine member for payment.');
            }

            $selectedFeeIds = $request->input('fees');
            $paymentMethod = $request->input('payment_method');
            $paymentRef = $request->input('payment_reference');
            $paymentNotes = $request->input('payment_notes');

            // Get product charges for this loan
            $product = Product::with('charges')->find($loan->product_type);
            if (!$product) {
                throw new \Exception('Product not found.');
            }

            $paidFeesCount = 0;
            $totalAmount = 0;

            foreach ($product->charges as $charge) {
                // Only process upfront charges (charge_type = 2)
                if ($charge->charge_type != 2) {
                    continue;
                }

                // Check if this fee is in the selected list
                if (!in_array($charge->id, $selectedFeeIds)) {
                    continue;
                }

                // Calculate the actual charge amount
                $chargeAmount = 0;
                if ($charge->type == 1) { // Fixed
                    $chargeAmount = floatval($charge->getRawOriginal('value') ?? 0);
                } elseif ($charge->type == 2) { // Percentage
                    $percentageValue = floatval($charge->getRawOriginal('value') ?? 0);
                    $chargeAmount = ($loan->principal * $percentageValue) / 100;
                } elseif ($charge->type == 3) { // Per Day
                    $perDayValue = floatval($charge->getRawOriginal('value') ?? 0);
                    $chargeAmount = $perDayValue * $loan->period;
                } elseif ($charge->type == 4) { // Per Month
                    $perMonthValue = floatval($charge->getRawOriginal('value') ?? 0);
                    $chargeAmount = $perMonthValue * $loan->period;
                }

                // Check if registration fee has already been paid by this member
                $isRegistrationFee = stripos($charge->name, 'registration') !== false;
                if ($isRegistrationFee) {
                    $existingPayment = \App\Models\Fee::where('member_id', $memberId)
                        ->where('fees_type_id', $charge->id)
                        ->where('status', 1) // Paid
                        ->first();

                    if ($existingPayment) {
                        // Skip this fee - already paid once
                        continue;
                    }
                }

                // Create fee payment record
                $fee = \App\Models\Fee::create([
                    'member_id' => $memberId,
                    'loan_id' => $loan->id,
                    'fees_type_id' => $charge->id,
                    'payment_type' => $paymentMethod,
                    'amount' => $chargeAmount,
                    'description' => 'Upfront payment for ' . $charge->name . ' - Loan ' . $loan->code,
                    'added_by' => auth()->id(),
                    'payment_status' => 'Paid',
                    'payment_description' => $paymentNotes,
                    'pay_ref' => $paymentRef,
                    'status' => 1, // Paid
                    'datecreated' => now()
                ]);

                $totalAmount += $chargeAmount;
                $paidFeesCount++;
            }

            DB::commit();

            $message = "Successfully recorded payment of UGX " . number_format($totalAmount, 0) . " for {$paidFeesCount} fee(s).";
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Failed to record payment: ' . $e->getMessage();
            return back()->with('error', $message);
        }
    }

    /**
     * Pay a single upfront fee for a loan
     */
    public function paySingleFee(Request $request, $id)
    {
        $request->validate([
            'loan_type' => 'required|in:personal,group',
            'fee_id' => 'required|integer',
            'payment_method' => 'required|integer|in:1,2,3,4',
            'payment_reference' => 'nullable|string|max:100',
            'payment_notes' => 'nullable|string|max:500'
        ]);

        $loanType = $request->input('loan_type');
        
        try {
            DB::beginTransaction();

            // Get the loan
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
                $memberId = $loan->member_id;
            } else {
                $loan = GroupLoan::with('group')->findOrFail($id);
                $memberId = $loan->group->members()->first()->id ?? null;
            }

            if (!$memberId) {
                throw new \Exception('Could not determine member for payment.');
            }

            $feeId = $request->input('fee_id');
            $paymentMethod = $request->input('payment_method');
            $paymentRef = $request->input('payment_reference');
            $paymentNotes = $request->input('payment_notes');

            // Get the charge from product charges
            $charge = \App\Models\ProductCharge::find($feeId);
            if (!$charge) {
                throw new \Exception('Charge not found.');
            }

            // Calculate the actual charge amount
            $chargeAmount = 0;
            if ($charge->type == 1) { // Fixed
                $chargeAmount = floatval($charge->getRawOriginal('value') ?? 0);
            } elseif ($charge->type == 2) { // Percentage
                $percentageValue = floatval($charge->getRawOriginal('value') ?? 0);
                $chargeAmount = ($loan->principal * $percentageValue) / 100;
            } elseif ($charge->type == 3) { // Per Day
                $perDayValue = floatval($charge->getRawOriginal('value') ?? 0);
                $chargeAmount = $perDayValue * $loan->period;
            } elseif ($charge->type == 4) { // Per Month
                $perMonthValue = floatval($charge->getRawOriginal('value') ?? 0);
                $chargeAmount = $perMonthValue * $loan->period;
            }

            // Check if registration fee has already been paid by this member
            $isRegistrationFee = stripos($charge->name, 'registration') !== false;
            if ($isRegistrationFee) {
                $existingPayment = \App\Models\Fee::where('member_id', $memberId)
                    ->where('fees_type_id', $charge->id)
                    ->where('status', 1) // Paid
                    ->first();

                if ($existingPayment) {
                    throw new \Exception('Registration fee has already been paid by this member.');
                }
            }

            // Check if this specific loan charge has already been paid
            $existingLoanPayment = \App\Models\Fee::where('loan_id', $loan->id)
                ->where('fees_type_id', $charge->id)
                ->where('status', 1)
                ->first();

            if ($existingLoanPayment) {
                throw new \Exception('This charge has already been paid for this loan.');
            }

            // Create fee payment record
            $fee = \App\Models\Fee::create([
                'member_id' => $memberId,
                'loan_id' => $loan->id,
                'fees_type_id' => $charge->id,
                'payment_type' => $paymentMethod,
                'amount' => $chargeAmount,
                'description' => 'Upfront payment for ' . $charge->name . ' - Loan ' . $loan->code,
                'added_by' => auth()->id(),
                'payment_status' => 'Paid',
                'payment_description' => $paymentNotes,
                'pay_ref' => $paymentRef,
                'status' => 1, // Paid
                'datecreated' => now()
            ]);

            DB::commit();

            $message = "Successfully recorded payment of UGX " . number_format($chargeAmount, 0) . " for " . $charge->name . ".";
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Failed to record payment: ' . $e->getMessage();
            return back()->with('error', $message);
        }
    }

    /**
     * Store loan upfront charge payment with mobile money collection
     */
    public function storeLoanMobileMoneyPayment(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'fees_type_id' => 'required|integer', // Product charge ID, not fee type
            'loan_id' => 'required|exists:personal_loans,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:100',
            'member_phone' => 'required|string',
            'member_name' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $member = Member::findOrFail($validated['member_id']);
            
            // Get the product charge (not fee type)
            $charge = \App\Models\ProductCharge::findOrFail($validated['fees_type_id']);

            // Create fee record with pending status
            $fee = Fee::create([
                'member_id' => $validated['member_id'],
                'loan_id' => $validated['loan_id'],
                'fees_type_id' => $validated['fees_type_id'],
                'payment_type' => 1, // Mobile Money
                'payment_phone' => $validated['member_phone'], // Save actual phone used
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? 'Loan upfront charge payment',
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
                "Loan Charge: {$charge->name}"
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
            \Log::error("Mobile Money Loan Fee Payment Error", [
                'member_id' => $validated['member_id'] ?? null,
                'loan_id' => $validated['loan_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check mobile money payment status for loan charges
     */
    public function checkLoanMmStatus($transactionRef)
    {
        try {
            \Log::info("=== CHECKING LOAN MOBILE MONEY STATUS ===", [
                'transaction_ref' => $transactionRef
            ]);
            
            // Find the fee by transaction reference
            $fee = Fee::where('pay_ref', $transactionRef)->first();
            
            if (!$fee) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Payment not found'
                ], 404);
            }

            // If already paid, return success
            if ($fee->status == 1) {
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully'
                ]);
            }

            // Check status with FlexiPay
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);
            $statusResult = $mobileMoneyService->checkTransactionStatus($transactionRef);

            \Log::info("FlexiPay Status Result", [
                'transaction_ref' => $transactionRef,
                'status' => $statusResult['status'] ?? 'unknown',
                'full_result' => $statusResult
            ]);

            // Update fee based on status
            if ($statusResult['status'] === 'completed') {
                $fee->update([
                    'status' => 1, // Paid
                    'payment_status' => 'Paid',
                    'payment_description' => $statusResult['message'] ?? 'Payment completed',
                    'payment_raw' => json_encode($statusResult)
                ]);

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
                    \Log::info("Loan payment marked as pending - still within retry window", [
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
            \Log::error("Loan Mobile Money Status Check Error", [
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
     * Retry a failed mobile money payment for loan charges
     */
    public function retryLoanMobileMoneyPayment(Request $request)
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

            // Reset fee to pending status
            $fee->update([
                'status' => 0, // Pending
                'payment_status' => 'Pending',
                'payment_description' => 'Retrying mobile money payment',
                'payment_phone' => $validated['member_phone'],
                'amount' => $validated['amount'],
                'original_amount' => $originalAmount,
                'datecreated' => now() // Reset timestamp for 2-minute grace period
            ]);

            // Initialize Mobile Money Service
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

            // Retry collection (Stanbic will generate new short request ID)
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
            
            $fee->update([
                'pay_ref' => $transactionRef,
                'payment_raw' => json_encode($result),
                'payment_description' => $result['message'] ?? 'Mobile money retry request sent'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment retry request sent to member\'s phone',
                'transaction_reference' => $transactionRef,
                'fee_id' => $fee->id
            ]);

        } catch (\Exception $e) {
            \Log::error("Loan Mobile Money Retry Error", [
                'fee_id' => $validated['fee_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update charge type for a loan
     */
    public function updateChargeType(Request $request, $id)
    {
        $request->validate([
            'loan_type' => 'required|in:personal,group',
            'charge_type' => 'required|in:1,2'
        ]);

        $loanType = $request->input('loan_type');
        $chargeType = $request->input('charge_type');
        
        try {
            DB::beginTransaction();

            // Get the loan
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
                $memberId = $loan->member_id;
            } else {
                $loan = GroupLoan::with('group')->findOrFail($id);
                $memberId = $loan->group->members()->first()->id ?? null;
            }

            // Only allow changes for pending loans
            if ($loan->status != 0) {
                return back()->with('error', 'Cannot change charge type for approved or disbursed loans.');
            }

            // Update charge type
            $loan->update(['charge_type' => $chargeType]);

            // If charge_type = 1 (Deduct from Disbursement), automatically mark all charges as paid
            if ($chargeType == 1) {
                // Get product charges
                $product = Product::with('charges')->find($loan->product_type);
                if ($product && $product->charges) {
                    $paidCount = 0;
                    
                    foreach ($product->charges as $charge) {
                        // Skip if not active
                        if ($charge->isactive != 1) {
                            continue;
                        }

                        // Check if fee already exists for this loan and charge
                        $existingFee = \App\Models\Fee::where('loan_id', $loan->id)
                            ->where('fees_type_id', $charge->id)
                            ->first();

                        if ($existingFee) {
                            // Update existing fee to paid status
                            $existingFee->update([
                                'status' => 1,
                                'payment_status' => 'Paid - Deducted from Disbursement',
                                'payment_description' => 'Automatically marked as paid - charges deducted from disbursement amount'
                            ]);
                        } else {
                            // Calculate the actual charge amount
                            $chargeAmount = 0;
                            if ($charge->type == 1) { // Fixed
                                $chargeAmount = floatval($charge->getRawOriginal('value') ?? 0);
                            } elseif ($charge->type == 2) { // Percentage
                                $percentageValue = floatval($charge->getRawOriginal('value') ?? 0);
                                $chargeAmount = ($loan->principal * $percentageValue) / 100;
                            } elseif ($charge->type == 3) { // Per Day
                                $perDayValue = floatval($charge->getRawOriginal('value') ?? 0);
                                $chargeAmount = $perDayValue * $loan->period;
                            } elseif ($charge->type == 4) { // Per Month
                                $perMonthValue = floatval($charge->getRawOriginal('value') ?? 0);
                                $chargeAmount = $perMonthValue * $loan->period;
                            }

                            // Create fee payment record marked as paid
                            \App\Models\Fee::create([
                                'member_id' => $memberId,
                                'loan_id' => $loan->id,
                                'fees_type_id' => $charge->id,
                                'payment_type' => 1, // Cash (default for deducted charges)
                                'amount' => $chargeAmount,
                                'description' => 'Charge deducted from disbursement - ' . $charge->name . ' - Loan ' . $loan->code,
                                'added_by' => auth()->id(),
                                'payment_status' => 'Paid - Deducted from Disbursement',
                                'payment_description' => 'Automatically marked as paid - charges deducted from disbursement amount',
                                'pay_ref' => 'AUTO-' . $loan->code,
                                'status' => 1, // Paid
                                'datecreated' => now()
                            ]);
                        }
                        $paidCount++;
                    }
                    
                    DB::commit();
                    
                    $message = "Charge type updated: Charges will be deducted from disbursement. {$paidCount} charge(s) automatically marked as paid.";
                } else {
                    DB::commit();
                    $message = 'Charge type updated: Charges will be deducted from disbursement amount.';
                }
            } elseif ($chargeType == 2) {
                // If switching to upfront payment, delete auto-generated fee records
                \App\Models\Fee::where('loan_id', $loan->id)
                    ->where('payment_status', 'Paid - Deducted from Disbursement')
                    ->delete();

                DB::commit();
                $message = 'Charge type updated: Member must pay charges upfront before disbursement.';
            } else {
                DB::commit();
                $message = 'Charge type updated successfully.';
            }
            
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update charge type: ' . $e->getMessage());
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
        $period = $request->period;
        $repayPeriod = $request->repay_period ?? 'daily'; // daily, weekly, monthly
        $productId = $request->product_type;
        
        // CRITICAL: Get interest rate from the product, not from user input
        // This ensures the product's interest rate is always used
        if (!$productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product type is required'
            ], 400);
        }
        
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        // Use the product's interest rate (already stored as percentage in DB)
        $interest = $product->interest / 100;
        
        // Flat interest calculation matching old bimsadmin system
        // The old system DOUBLES the interest rate per installment
        // Example: 0.7% interest means 1.4% per installment (0.7% × 2)
        // For 2 periods: 10,000 × 0.014 × 2 = 280 total interest
        // Installment: (10,000 + 280) / 2 = 5,140
        
        $interestRatePerInstallment = $interest * 2; // Double the interest rate
        $totalInterest = $principal * $interestRatePerInstallment * $period;
        $totalPayable = $principal + $totalInterest;
        $installment = $totalPayable / $period;
        
        return response()->json([
            'success' => true,
            'installment' => round($installment, 2),
            'total_payable' => round($totalPayable, 2),
            'total_interest' => round($totalInterest, 2),
            'period' => $period,
            'repay_period' => $repayPeriod,
            'product_interest' => $product->interest // Return actual interest rate used
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
            'date_closed',
            'sign_code'
        ];
        
        // Get eSign loans - get them separately to maintain relationships
        $personalLoansQuery = PersonalLoan::where('sign_code', '>', 0);
        $groupLoansQuery = GroupLoan::where('sign_code', '>', 0);

        // Apply filters if needed
        if ($request->filled('status')) {
            $personalLoansQuery->where('status', $request->status);
            $groupLoansQuery->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $personalLoansQuery->where('branch_id', $request->branch_id);
            $groupLoansQuery->where('branch_id', $request->branch_id);
        }

        // Get both types and merge them
        $personalLoans = $personalLoansQuery->with(['member', 'product', 'branch', 'addedBy'])->get()->map(function($loan) {
            $loan->loan_type = 'personal';
            return $loan;
        });

        $groupLoans = $groupLoansQuery->with(['group', 'product', 'branch', 'addedBy'])->get()->map(function($loan) {
            $loan->loan_type = 'group';
            return $loan;
        });

        // Merge and paginate manually
        $allLoans = $personalLoans->merge($groupLoans)->sortByDesc('datecreated');
        
        // Manual pagination
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $allLoans->forPage($currentPage, $perPage),
            $allLoans->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $stats = [
            'total_esign' => PersonalLoan::where('sign_code', '>', 0)->count() + 
                           GroupLoan::where('sign_code', '>', 0)->count(),
            'pending_esign' => PersonalLoan::where('sign_code', '>', 0)->where('status', 0)->count() +
                             GroupLoan::where('sign_code', '>', 0)->where('status', 0)->count(),
            'approved_esign' => PersonalLoan::where('sign_code', '>', 0)->where('status', 1)->count() +
                              GroupLoan::where('sign_code', '>', 0)->where('status', 1)->count(),
            'disbursed_esign' => PersonalLoan::where('sign_code', '>', 0)->where('status', 2)->count() +
                               GroupLoan::where('sign_code', '>', 0)->where('status', 2)->count(),
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
        
        // Check if showing rejected loans or pending loans
        $showRejected = $request->has('show_rejected');
        $status = $showRejected ? 4 : 0; // 4 = Rejected, 0 = Pending
        
        // Get loans based on status filter
        $personalLoansQuery = PersonalLoan::where('status', $status);
        $groupLoansQuery = GroupLoan::where('status', $status);

        if ($request->filled('branch_id')) {
            $personalLoansQuery->where('branch_id', $request->branch_id);
            $groupLoansQuery->where('branch_id', $request->branch_id);
        }

        // Get both types and merge them
        $personalLoans = $personalLoansQuery->with(['member', 'product', 'branch', 'addedBy', 'rejectedBy'])->get()->map(function($loan) {
            $loan->loan_type = 'personal';
            return $loan;
        });

        $groupLoans = $groupLoansQuery->with(['group', 'product', 'branch', 'addedBy', 'rejectedBy'])->get()->map(function($loan) {
            $loan->loan_type = 'group';
            return $loan;
        });

        // Merge and paginate manually
        $allLoans = $personalLoans->merge($groupLoans)->sortByDesc('datecreated');
        
        // Manual pagination
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $allLoans->forPage($currentPage, $perPage),
            $allLoans->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
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
    public function viewAgreement($id, $type)
    {
        try {
            // Get loan with related data
            if ($type === 'personal') {
                $loan = PersonalLoan::with(['member', 'product', 'branch'])->findOrFail($id);
                if (!$loan->member) {
                    abort(404, 'Member not found for this loan');
                }
                $borrower = $loan->member;
            } else {
                $loan = GroupLoan::with(['group.members', 'product', 'branch'])->findOrFail($id);
                if (!$loan->group) {
                    abort(404, 'Group not found for this loan');
                }
                $borrower = $loan->group;
            }

            // Use DOMPDF directly
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Load HTML from view
            $html = view('admin.loans.agreement-pdf', compact('loan', 'borrower', 'type'))->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Stream the PDF
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="loan-agreement-' . $loan->code . '.pdf"');

        } catch (\Exception $e) {
            \Log::error('Agreement viewing error: ' . $e->getMessage());
            return back()->with('error', 'Error generating agreement: ' . $e->getMessage());
        }
    }

    /**
     * Download signed loan agreement
     */
    public function downloadSignedAgreement($id, $type)
    {
        try {
            // Get loan
            if ($type === 'personal') {
                $loan = PersonalLoan::with(['member', 'product', 'branch'])->find($id);
                $borrower = $loan->member;
            } else {
                $loan = GroupLoan::with(['group.members', 'product', 'branch'])->find($id);
                $borrower = $loan->group;
            }

            if (!$loan || $loan->signature_status !== 'signed') {
                abort(404, 'Signed agreement not found');
            }

            // Use DOMPDF directly
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Load HTML from view
            $html = view('admin.loans.signed-agreement-pdf', compact('loan', 'borrower', 'type'))->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Download the PDF
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="signed-agreement-' . $loan->code . '.pdf"');

        } catch (\Exception $e) {
            \Log::error('Signed agreement download error: ' . $e->getMessage());
            return back()->with('error', 'Error downloading signed agreement: ' . $e->getMessage());
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
    public function getLoanDetails($id, Request $request)
    {
        $loanType = $request->get('type', 'personal');
        
        // Determine which model to use
        if ($loanType === 'group') {
            $loan = GroupLoan::with(['group', 'product', 'branch'])->findOrFail($id);
            $borrowerName = $loan->group->name ?? 'N/A';
        } else {
            $loan = PersonalLoan::with(['member', 'product', 'branch'])->findOrFail($id);
            $borrowerName = ($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? '');
        }

        // Get loan schedules (unpaid)
        $schedules = LoanSchedule::where('loan_id', $id)
            ->where('status', 0) // Unpaid
            ->orderBy('payment_date')
            ->get();

        $nextDue = $schedules->first();
        
        // Calculate total paid
        $totalPaid = Repayment::where('loan_id', $id)
            ->where('status', 1) // Successful payments
            ->sum('amount');

        // Return HTML view for modal
        return view('admin.loans.partials.loan-details', compact('loan', 'borrowerName', 'schedules', 'nextDue', 'totalPaid', 'loanType'));
    }

    /**
     * Export loans to CSV
     */
    public function export(Request $request)
    {
        try {
            // Get loans based on filters
            $personalLoans = PersonalLoan::with(['member', 'product', 'branch'])
                ->select([
                    'id', 'code', 'member_id', 'product_type', 'principal', 
                    'interest', 'period', 'status', 'verified', 'datecreated', 'branch_id'
                ]);

            $groupLoans = GroupLoan::with(['group', 'product', 'branch'])
                ->select([
                    'id', 'code', 'group_id', 'product_type', 'principal', 
                    'interest', 'period', 'status', 'verified', 'datecreated', 'branch_id'
                ]);

            // Apply filters
            if ($request->filled('status')) {
                $personalLoans->where('status', $request->status);
                $groupLoans->where('status', $request->status);
            }

            if ($request->filled('type')) {
                if ($request->type === 'personal') {
                    $loans = $personalLoans->get();
                } elseif ($request->type === 'group') {
                    $loans = $groupLoans->get();
                } else {
                    $loans = $personalLoans->get()->merge($groupLoans->get());
                }
            } else {
                $loans = $personalLoans->get()->merge($groupLoans->get());
            }

            // Prepare CSV data
            $filename = 'loans_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $callback = function() use ($loans) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers
                fputcsv($file, [
                    'Loan Code',
                    'Borrower Name',
                    'Type',
                    'Product',
                    'Principal (UGX)',
                    'Interest (%)',
                    'Period',
                    'Status',
                    'Branch',
                    'Date Created'
                ]);
                
                // Add data rows
                foreach ($loans as $loan) {
                    $borrowerName = 'N/A';
                    $loanType = 'Personal';
                    
                    if (isset($loan->member)) {
                        $borrowerName = $loan->member->fname . ' ' . $loan->member->lname;
                    } elseif (isset($loan->group)) {
                        $borrowerName = $loan->group->name ?? 'Unknown Group';
                        $loanType = 'Group';
                    }
                    
                    $statusName = [
                        0 => 'Pending',
                        1 => 'Approved',
                        2 => 'Disbursed',
                        3 => 'Completed',
                        4 => 'Rejected'
                    ][$loan->status] ?? 'Unknown';
                    
                    fputcsv($file, [
                        $loan->code,
                        $borrowerName,
                        $loanType,
                        $loan->product->name ?? 'N/A',
                        number_format($loan->principal, 0),
                        $loan->interest,
                        $loan->period,
                        $statusName,
                        $loan->branch->name ?? 'N/A',
                        date('Y-m-d H:i:s', strtotime($loan->datecreated))
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Loan export error: ' . $e->getMessage());
            
            return redirect()->back()->with('error', 'Failed to export loans: ' . $e->getMessage());
        }
    }
}