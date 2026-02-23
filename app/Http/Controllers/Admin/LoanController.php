<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\Product;
use App\Models\Branch;
use App\Models\LoanSchedule;
use App\Models\Guarantor;
use App\Models\Repayment;
use App\Models\Fee;
use App\Models\FeeType;
use App\Services\FileStorageService;
use App\Services\LoanClosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

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
                'business_license' => 'nullable|file|max:51200',
                'bank_statement' => 'nullable|file|max:51200',
                'business_photos' => 'nullable|file|max:51200',
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
                
                // Handle file uploads - using FileStorageService (auto-uploads to DigitalOcean Spaces in production)
                if ($request->hasFile('business_license')) {
                    try {
                        $file = $request->file('business_license');
                        if ($file->isValid()) {
                            $validated['trading_file'] = FileStorageService::storeFile($file, 'loan-documents');
                            \Log::info('Trading license uploaded successfully', ['path' => $validated['trading_file'], 'loan_code' => $validated['code']]);
                        } else {
                            \Log::error('Trading license file is not valid', ['loan_code' => $validated['code']]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error uploading trading license: ' . $e->getMessage(), ['loan_code' => $validated['code']]);
                    }
                }

                if ($request->hasFile('bank_statement')) {
                    try {
                        $file = $request->file('bank_statement');
                        if ($file->isValid()) {
                            $validated['bank_file'] = FileStorageService::storeFile($file, 'loan-documents');
                            \Log::info('Bank statement uploaded successfully', ['path' => $validated['bank_file'], 'loan_code' => $validated['code']]);
                        } else {
                            \Log::error('Bank statement file is not valid', ['loan_code' => $validated['code']]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error uploading bank statement: ' . $e->getMessage(), ['loan_code' => $validated['code']]);
                    }
                }

                if ($request->hasFile('business_photos')) {
                    try {
                        $file = $request->file('business_photos');
                        if ($file->isValid()) {
                            // Store as business_file since business_photos column doesn't exist
                            $businessFilePath = FileStorageService::storeFile($file, 'loan-documents');
                            // Only set business_file if it wasn't already set by business_license
                            if (!isset($validated['business_file'])) {
                                $validated['business_file'] = $businessFilePath;
                            }
                            \Log::info('Business photos uploaded successfully', ['path' => $businessFilePath, 'loan_code' => $validated['code']]);
                        } else {
                            \Log::error('Business photos file is not valid', ['loan_code' => $validated['code']]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error uploading business photos: ' . $e->getMessage(), ['loan_code' => $validated['code']]);
                    }
                }

                $validated['installment'] = $validated['max_installment'];
                $validated['added_by'] = auth()->id();
                $validated['status'] = 0; // Pending approval
                $validated['datecreated'] = now(); // Use datecreated instead of created_at
                $validated['verified'] = 0; // Not verified initially
                $validated['sign_code'] = 0; // Not an eSign loan by default
                $validated['interest_method'] = $request->input('interest_method', 2); // 1=FLAT, 2=DECLINING (default)

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
                // Note: group_loans table doesn't have installment column, so we remove it from validated
                if (isset($validated['max_installment'])) {
                    unset($validated['max_installment']);
                }
                $validated['added_by'] = auth()->id();
                $validated['status'] = 0; // Pending approval
                $validated['verified'] = false;
                $validated['datecreated'] = now();

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
            'trading_file' => 'nullable|file|max:51200',
            'bank_file' => 'nullable|file|max:51200',
            'business_file' => 'nullable|file|max:51200',
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

        // Handle file uploads - using FileStorageService (auto-uploads to DigitalOcean Spaces in production)
        if ($request->hasFile('trading_file')) {
            $validated['trading_file'] = FileStorageService::storeFile($request->file('trading_file'), 'loan-documents');
        }

        if ($request->hasFile('bank_file')) {
            $validated['bank_file'] = FileStorageService::storeFile($request->file('bank_file'), 'loan-documents');
        }

        if ($request->hasFile('business_file')) {
            $validated['business_file'] = FileStorageService::storeFile($request->file('business_file'), 'loan-documents');
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

                // Post to General Ledger
                try {
                    $accountingService = new \App\Services\AccountingService();
                    $journal = $accountingService->postFeeCollectionEntry($fee, $charge->name);
                    if ($journal) {
                        \Log::info('Upfront fee GL entry posted', [
                            'fee_id' => $fee->id,
                            'loan_id' => $loan->id,
                            'journal_number' => $journal->journal_number
                        ]);
                    }
                } catch (\Exception $glError) {
                    \Log::error('Upfront fee GL posting failed', [
                        'fee_id' => $fee->id,
                        'loan_id' => $loan->id,
                        'error' => $glError->getMessage()
                    ]);
                    // Continue - don't fail fee recording if GL posting fails
                }

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
        // Use the LoanScheduleService for proper schedule generation
        $scheduleService = app(\App\Services\LoanScheduleService::class);
        $scheduleService->generateAndSaveSchedule($loan);
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
        
        // HALF-TERM INTEREST FORMULA (Universal for ALL loan types)
        // ALL interest is paid in FIRST HALF of loan term
        // SECOND HALF = Principal only (no interest)
        // period_type: 1=Weekly, 2=Monthly, 3=Daily
        $periodType = $product->period_type ?? 3;
        
        $principalPerPeriod = $principal / $period;
        $halfTerm = floor($period / 2);
        
        // Calculate total interest based on loan type
        if ($periodType == 2) {
            // MONTHLY loans: Interest rate is DOUBLED
            $totalInterest = $principal * ($interest * 2);
        } else {
            // WEEKLY and DAILY loans: Interest rate as-is
            $totalInterest = $principal * $interest;
        }
        
        // Distribute interest over FIRST HALF of term
        if ($halfTerm > 0) {
            $interestPerPeriod = $totalInterest / $halfTerm;
            // Max installment is in first half (principal + interest)
            $maxInstallment = $principalPerPeriod + $interestPerPeriod;
        } else {
            // Edge case: 1 period loan - all interest in single payment
            $maxInstallment = $principalPerPeriod + $totalInterest;
        }
        
        $totalPayable = $principal + $totalInterest;
        $installment = $maxInstallment; // Return the highest/equal installment amount
        
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
        return $this->generateAgreementPDF($id, $type, false);
    }

    /**
     * Save electronic signature data
     */
    public function saveESignature(Request $request, $id)
    {
        try {
            \DB::beginTransaction();

            $loanType = $request->input('loan_type', 'personal');
            $saveDraft = $request->input('save_draft', false);

            // Get loan
            if ($loanType === 'personal') {
                $loan = PersonalLoan::with(['member', 'product', 'branch', 'guarantors.member'])->findOrFail($id);
                $borrower = $loan->member;
            } else {
                $loan = GroupLoan::with(['group.members', 'product', 'branch', 'guarantors.member'])->findOrFail($id);
                $borrower = $loan->group;
            }

            // Prevent editing finalized agreements (unless saving draft before finalization)
            if ($loan->agreement_finalized_at && !$saveDraft) {
                return back()->with('error', 'This agreement has already been finalized and cannot be modified.');
            }

            // Validate if not just saving draft
            if (!$saveDraft) {
                $rules = [
                    'loan_purpose' => 'required|string',
                ];

                // Require signatures only when finalizing
                if (!$request->has('borrower_signature_data') && !$request->hasFile('borrower_signature_file')) {
                    return back()->with('error', 'Borrower signature is required')->withInput();
                }
                if (!$request->has('lender_signature_data') && !$request->hasFile('lender_signature_file')) {
                    return back()->with('error', 'Lender signature is required')->withInput();
                }

                $request->validate($rules);
            }

            // Update basic fields
            $loan->loan_purpose = $request->input('loan_purpose');
            if ($loanType === 'personal' && $borrower) {
                $borrowerName = trim(($borrower->fname ?? '') . ' ' . ($borrower->mname ?? '') . ' ' . ($borrower->lname ?? ''));
                $loan->cash_account_number = $borrower->cash_security_account_number ?: $request->input('cash_account_number');
                $loan->cash_account_name = $borrowerName ?: $request->input('cash_account_name');
            } else {
                $loan->cash_account_number = $request->input('cash_account_number');
                $loan->cash_account_name = $request->input('cash_account_name');
            }
            $loan->immovable_assets = $request->input('immovable_assets');
            $loan->moveable_assets = $request->input('moveable_assets');
            $loan->intellectual_property = $request->input('intellectual_property');
            $loan->stocks_collateral = $request->input('stocks_collateral');
            $loan->livestock_collateral = $request->input('livestock_collateral');
            
            // Witness details
            $loan->witness_name = $request->input('witness_name');
            $loan->witness_nin = $request->input('witness_nin');

            // Handle witness signature
            if ($request->has('witness_signature_data') && !empty($request->input('witness_signature_data'))) {
                $loan->witness_signature = $request->input('witness_signature_data');
                $loan->witness_signature_type = 'drawn';
                $loan->witness_signature_date = now();
            } elseif ($request->hasFile('witness_signature_file')) {
                $file = $request->file('witness_signature_file');
                $path = FileStorageService::storeFile($file, 'signatures/witness');
                $loan->witness_signature = $path;
                $loan->witness_signature_type = 'uploaded';
                $loan->witness_signature_date = now();
            }

            // Group-specific fields
            if ($loanType === 'group') {
                $loan->group_banker_name = $request->input('group_banker_name');
                $loan->group_banker_nin = $request->input('group_banker_nin');
                $loan->group_banker_occupation = $request->input('group_banker_occupation');
                $loan->group_banker_residence = $request->input('group_banker_residence');
                $loan->group_representative_name = $request->input('group_representative_name');
                $loan->group_representative_phone = $request->input('group_representative_phone');
            }

            // Handle borrower signature
            if ($request->has('borrower_signature_data') && !empty($request->input('borrower_signature_data'))) {
                $loan->borrower_signature = $request->input('borrower_signature_data');
                $loan->borrower_signature_type = 'drawn';
                $loan->borrower_signature_date = now();
            } elseif ($request->hasFile('borrower_signature_file')) {
                $file = $request->file('borrower_signature_file');
                $path = FileStorageService::storeFile($file, 'signatures/borrower');
                $loan->borrower_signature = $path;
                $loan->borrower_signature_type = 'uploaded';
                $loan->borrower_signature_date = now();
            }

            // Handle lender signature
            if ($request->has('lender_signature_data') && !empty($request->input('lender_signature_data'))) {
                $loan->lender_signature = $request->input('lender_signature_data');
                $loan->lender_signature_type = 'drawn';
                $loan->lender_signature_date = now();
                $loan->lender_signed_by = auth()->id(); // Store user ID, not name
                $loan->lender_title = $request->input('lender_title', 'Branch Manager');
            } elseif ($request->hasFile('lender_signature_file')) {
                $file = $request->file('lender_signature_file');
                $path = FileStorageService::storeFile($file, 'signatures/lender');
                $loan->lender_signature = $path;
                $loan->lender_signature_type = 'uploaded';
                $loan->lender_signature_date = now();
                $loan->lender_signed_by = auth()->id(); // Store user ID, not name
                $loan->lender_title = $request->input('lender_title', 'Branch Manager');
            }

            // If not saving draft, finalize the agreement
            if (!$saveDraft) {
                $loan->agreement_finalized_at = now();

                // Generate signed PDF
                $pdfPath = $this->generateSignedAgreementPDF($loan, $borrower, $loanType);
                $loan->signed_agreement_path = $pdfPath;
                
                // Save to member documents
                $this->savePDFToMemberDocuments($loan, $pdfPath, $loanType);
            }

            $loan->save();

            \DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $saveDraft ? 'Draft saved successfully' : 'Agreement finalized successfully'
                ]);
            }

            return back()->with('success', $saveDraft ? 'Draft saved successfully' : 'Agreement finalized and signed PDF generated successfully');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('E-Signature save error: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error saving e-signature: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error saving e-signature: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Generate signed agreement PDF with signatures
     */
    private function generateSignedAgreementPDF($loan, $borrower, $type)
    {
        try {
            // Use DOMPDF
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('chroot', public_path());
            
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Load HTML from view with signatures
            $html = view('admin.loans.agreement-pdf', compact('loan', 'borrower', 'type'))->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Get PDF content
            $pdfContent = $dompdf->output();
            
            // Determine member ID
            $memberId = $type === 'personal' ? $loan->member_id : $loan->group_id;
            
            // Create filename
            $filename = $loan->code . '-signed-agreement-' . time() . '.pdf';
            
            // Store directly in public/uploads/member-documents/{member_id}/
            $uploadPath = public_path('uploads/member-documents/' . $memberId);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Save PDF file
            $filePath = $uploadPath . '/' . $filename;
            file_put_contents($filePath, $pdfContent);
            
            // Return relative path
            return 'uploads/member-documents/' . $memberId . '/' . $filename;

        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Regenerate signed agreement PDF with current data (guarantors, etc.)
     */
    public function regenerateAgreement(Request $request, $id)
    {
        try {
            \Log::info('Regenerate agreement request for loan ID: ' . $id);
            
            $loanType = $request->input('loan_type', 'personal');
            \Log::info('Loan type: ' . $loanType);

            // Get loan
            if ($loanType === 'personal') {
                $loan = PersonalLoan::with(['member', 'product', 'branch', 'guarantors.member'])->findOrFail($id);
                $borrower = $loan->member;
                \Log::info('Personal loan found. Guarantors count: ' . $loan->guarantors->count());
            } else {
                $loan = GroupLoan::with(['group.members', 'product', 'branch', 'guarantors.member'])->findOrFail($id);
                $borrower = $loan->group;
                \Log::info('Group loan found. Guarantors count: ' . $loan->guarantors->count());
            }

            // Check if agreement is finalized
            if (!$loan->agreement_finalized_at) {
                \Log::warning('Agreement not finalized for loan: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Agreement must be finalized first'
                ], 400);
            }

            \Log::info('Regenerating PDF for loan: ' . $loan->code);
            
            // Regenerate PDF with current data
            $pdfPath = $this->generateSignedAgreementPDF($loan, $borrower, $loanType);
            $loan->signed_agreement_path = $pdfPath;
            $loan->save();
            
            // Update member document
            $this->savePDFToMemberDocuments($loan, $pdfPath, $loanType, true);

            \Log::info('PDF regenerated successfully. Path: ' . $pdfPath);

            // Always return JSON for this endpoint
            return response()->json([
                'success' => true,
                'message' => 'Agreement regenerated successfully with current guarantor data (' . $loan->guarantors->count() . ' guarantors)',
                'pdf_url' => FileStorageService::getFileUrl($pdfPath)
            ]);

        } catch (\Exception $e) {
            \Log::error('Agreement regeneration error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Always return JSON for this endpoint
            return response()->json([
                'success' => false,
                'message' => 'Error regenerating agreement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save guarantor signature
     */
    public function saveGuarantorSignature(Request $request, $id)
    {
        try {
            $guarantorId = $request->input('guarantor_id');
            $guarantor = \App\Models\Guarantor::findOrFail($guarantorId);

            // Verify guarantor belongs to this loan
            if ($guarantor->loan_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guarantor does not belong to this loan'
                ], 403);
            }

            $signatureType = $request->input('signature_type');

            if ($signatureType === 'drawn') {
                $signatureData = $request->input('signature_data');
                if (!$signatureData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Signature data is required'
                    ], 400);
                }
                $guarantor->signature = $signatureData;
            } else {
                // Handle uploaded signature
                if (!$request->hasFile('signature_file')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Signature file is required'
                    ], 400);
                }
                $file = $request->file('signature_file');
                $path = FileStorageService::storeFile($file, 'signatures/guarantors');
                $guarantor->signature = $path;
            }

            $guarantor->signature_type = $signatureType;
            $guarantor->signature_date = now();
            $guarantor->save();

            return response()->json([
                'success' => true,
                'message' => 'Guarantor signature saved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Guarantor signature save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving signature: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save PDF to member documents table
     */
    private function savePDFToMemberDocuments($loan, $pdfPath, $loanType, $isRegeneration = false)
    {
        try {
            $memberId = $loanType === 'personal' ? $loan->member_id : $loan->group_id;
            $filePath = public_path($pdfPath);
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
            
            // Check if document already exists for this loan
            $existingDoc = MemberDocument::where('member_id', $memberId)
                ->where('document_type', 'loan_agreement')
                ->where('document_name', 'LIKE', $loan->code . '%')
                ->first();
            
            if ($isRegeneration && $existingDoc) {
                // Update existing document
                $existingDoc->file_path = $pdfPath;
                $existingDoc->file_size = $fileSize;
                $existingDoc->updated_at = now();
                $existingDoc->save();
                \Log::info('Updated existing member document for loan: ' . $loan->code);
            } else {
                // Create new document record
                MemberDocument::create([
                    'member_id' => $memberId,
                    'document_type' => 'loan_agreement',
                    'document_name' => $loan->code . ' - Signed Loan Agreement',
                    'file_path' => $pdfPath,
                    'file_type' => 'application/pdf',
                    'file_size' => $fileSize,
                    'description' => 'Electronically signed loan agreement for loan ' . $loan->code,
                    'uploaded_by' => auth()->id(),
                ]);
                \Log::info('Created new member document for loan: ' . $loan->code);
            }
        } catch (\Exception $e) {
            \Log::error('Error saving PDF to member documents: ' . $e->getMessage());
            // Don't throw - this is not critical to the main flow
        }
    }

    /**
     * Download signed loan agreement
     */
    public function downloadSignedAgreement($id, $type)
    {
        return $this->generateAgreementPDF($id, $type, true);
    }

    /**
     * Helper method to generate agreement PDF (view or download)
     */
    private function generateAgreementPDF($id, $type, $isDownload = false)
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

            // For downloads, check if agreement is signed
            if ($isDownload && $loan->signature_status !== 'signed') {
                abort(404, 'Signed agreement not found');
            }

            // Use DOMPDF directly
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Load HTML from view (use signed agreement view for downloads)
            $viewName = $isDownload ? 'admin.loans.signed-agreement-pdf' : 'admin.loans.agreement-pdf';
            $html = view($viewName, compact('loan', 'borrower', 'type'))->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Determine disposition (inline for view, attachment for download)
            $disposition = $isDownload ? 'attachment' : 'inline';
            $filenamePrefix = $isDownload ? 'signed-agreement-' : 'loan-agreement-';
            
            // Stream the PDF
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', $disposition . '; filename="' . $filenamePrefix . $loan->code . '.pdf"');

        } catch (\Exception $e) {
            \Log::error('Agreement PDF generation error: ' . $e->getMessage());
            return back()->with('error', 'Error generating agreement: ' . $e->getMessage());
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

    /**
     * Print loan payment statement
     */
    public function printStatement($id)
    {
        $loan = PersonalLoan::with(['member', 'product', 'branch'])
            ->findOrFail($id);
        
        $schedules = LoanSchedule::where('loan_id', $id)
            ->orderBy('installment')
            ->get();
        
        $repayments = Repayment::where('loan_id', $id)
            ->orderBy('created_at')
            ->get();
        
        return view('admin.loans.print.statement', compact('loan', 'schedules', 'repayments'));
    }

    /**
     * Print loan repayment schedule
     */
    public function printSchedule($id)
    {
        return $this->printLoanDocument($id, 'schedule', function($query) {
            return $query->orderBy('installment');
        });
    }

    /**
     * Print overdue notice
     */
    public function printNotice($id)
    {
        return $this->printLoanDocument($id, 'notice', function($query) {
            return $query->where('status', 0)
                ->where('installment_date', '<', now())
                ->orderBy('installment');
        });
    }

    /**
     * Helper method to print loan documents
     */
    private function printLoanDocument($id, $viewType, $scheduleFilter = null)
    {
        $loan = PersonalLoan::with(['member', 'product', 'branch'])
            ->findOrFail($id);
        
        $schedulesQuery = LoanSchedule::where('loan_id', $id);
        
        if ($scheduleFilter) {
            $schedulesQuery = $scheduleFilter($schedulesQuery);
        } else {
            $schedulesQuery = $schedulesQuery->orderBy('installment');
        }
        
        $schedules = $schedulesQuery->get();
        
        return view('admin.loans.print.' . $viewType, compact('loan', 'schedules'));
    }

    /**
     * Display rejected loans
     */
    public function rejectedLoans(Request $request)
    {
        $type = $request->get('type', 'personal');
        
        if ($type === 'personal') {
            $query = PersonalLoan::with(['member', 'product', 'branch', 'rejectedBy'])
                ->where('status', 4); // Rejected status
        } else {
            $query = GroupLoan::with(['group', 'product', 'branch', 'rejectedBy'])
                ->where('status', 4);
        }

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            if ($type === 'personal') {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                      ->orWhereHas('member', function ($q2) use ($search) {
                          $q2->where('fname', 'LIKE', "%{$search}%")
                             ->orWhere('lname', 'LIKE', "%{$search}%");
                      });
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                      ->orWhereHas('group', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date_rejected', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date_rejected', '<=', $request->end_date);
        }

        $loans = $query->orderBy('date_rejected', 'desc')->paginate(20);
        
        $branches = \App\Models\Branch::all();
        
        // Statistics
        $stats = [
            'total_rejected' => $query->count(),
            'rejected_this_month' => $query->whereMonth('date_rejected', now()->month)
                                          ->whereYear('date_rejected', now()->year)
                                          ->count(),
        ];

        return view('admin.loans.rejected', compact('loans', 'branches', 'stats', 'type'));
    }

    /**
     * Export rejected loans
     */
    public function exportRejectedLoans(Request $request)
    {
        $type = $request->get('type', 'personal');
        
        if ($type === 'personal') {
            $query = PersonalLoan::with(['member', 'product', 'branch', 'rejectedBy'])
                ->where('status', 4);
        } else {
            $query = GroupLoan::with(['group', 'product', 'branch', 'rejectedBy'])
                ->where('status', 4);
        }

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            if ($type === 'personal') {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                      ->orWhereHas('member', function ($q2) use ($search) {
                          $q2->where('fname', 'LIKE', "%{$search}%")
                             ->orWhere('lname', 'LIKE', "%{$search}%");
                      });
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                      ->orWhereHas('group', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date_rejected', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date_rejected', '<=', $request->end_date);
        }

        $loans = $query->orderBy('date_rejected', 'desc')->get();

        $filename = 'rejected_' . $type . '_loans_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($loans, $type) {
            $file = fopen('php://output', 'w');
            
            // Header
            if ($type === 'personal') {
                fputcsv($file, ['Loan Code', 'Member', 'Branch', 'Amount', 'Interest', 'Period', 'Date Applied', 'Date Rejected', 'Rejected By', 'Rejection Reason']);
            } else {
                fputcsv($file, ['Loan Code', 'Group', 'Branch', 'Amount', 'Interest', 'Period', 'Date Applied', 'Date Rejected', 'Rejected By', 'Rejection Reason']);
            }

            foreach ($loans as $loan) {
                if ($type === 'personal') {
                    $memberName = $loan->member ? $loan->member->fname . ' ' . $loan->member->lname : 'N/A';
                } else {
                    $memberName = $loan->group ? $loan->group->name : 'N/A';
                }

                fputcsv($file, [
                    $loan->code,
                    $memberName,
                    $loan->branch ? $loan->branch->name : 'N/A',
                    number_format($loan->principal, 2),
                    $loan->interest . '%',
                    $loan->period,
                    $loan->datecreated ? date('Y-m-d', strtotime($loan->datecreated)) : 'N/A',
                    $loan->date_rejected ? date('Y-m-d', strtotime($loan->date_rejected)) : 'N/A',
                    $loan->rejectedBy ? $loan->rejectedBy->name : 'System',
                    $loan->comments ?? $loan->Rcomments ?? 'No reason provided'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show loan restructure form
     */
    public function restructure($id)
    {
        $loan = PersonalLoan::with(['member', 'product', 'branch', 'schedules'])
            ->findOrFail($id);

        // Check if loan is eligible for restructuring
        if ($loan->status != 2) {
            return redirect()->back()->with('error', 'Only active/disbursed loans can be restructured.');
        }

        // Calculate loan statistics
        $schedules = $loan->schedules;
        $paidCount = $schedules->where('status', 1)->count();
        $pendingCount = $schedules->where('status', 0)->count();
        $overdueCount = $schedules->where('periods_in_arrears', '>', 0)->count();

        // Get active late fees from late_fees table
        $totalLateFees = DB::table('late_fees')
            ->where('loan_id', $id)
            ->where('status', '!=', 2) // Exclude waived fees
            ->sum('amount');

        // Prepare loan summary data
        $loanData = (object)[
            'id' => $loan->id,
            'code' => $loan->code,
            'borrower_name' => $loan->member->fname . ' ' . $loan->member->lname,
            'product_name' => $loan->product->name ?? 'N/A',
            'principal_amount' => $loan->principal,
            'interest_rate' => $loan->interest,
            'loan_term' => $loan->period,
            'period_type_name' => $this->getPeriodTypeName($loan->product->period_type ?? 3),
            'disbursement_date' => $loan->date_approved,
            'total_payable' => $schedules->sum('payment'),
            'amount_paid' => $schedules->sum('paid'),
            'outstanding_balance' => $schedules->sum('payment') - $schedules->sum('paid'),
            'total_late_fees' => $totalLateFees, // Use late_fees table data
            'days_overdue' => $this->calculateDaysOverdue($loan),
        ];

        return view('admin.loans.restructure', [
            'loan' => $loanData,
            'paidCount' => $paidCount,
            'pendingCount' => $pendingCount,
            'overdueCount' => $overdueCount,
        ]);
    }

    /**
     * Store restructured loan
     */
    public function restructureStore(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'comments' => 'required|string|min:20',
            'new_interest' => 'required|numeric|min:0|max:100',
            'new_period' => 'required|integer|min:1|max:260',
            'grace_period' => 'nullable|integer|min:0|max:12',
            'include_late_fees' => 'nullable|boolean',
            'confirm' => 'required|accepted',
        ]);

        try {
            DB::beginTransaction();

            $originalLoan = PersonalLoan::with(['member', 'product', 'schedules'])->findOrFail($id);

            // Verify loan can be restructured
            if ($originalLoan->status != 2) {
                return redirect()->back()->with('error', 'Only active loans can be restructured.');
            }

            if ($originalLoan->restructured == 1) {
                return redirect()->back()->with('error', 'This loan has already been restructured.');
            }

            // Calculate outstanding balance automatically
            $schedules = $originalLoan->schedules;
            $totalPayable = $schedules->sum('payment');
            $totalPaid = $schedules->sum('paid');
            $outstandingBalance = $totalPayable - $totalPaid;
            
            // Get total late fees (from late_fees table for accuracy)
            $totalLateFees = DB::table('late_fees')
                ->where('loan_id', $originalLoan->id)
                ->where('status', '!=', 2) // Exclude waived fees
                ->sum('amount'); // Column is 'amount' not 'late_fee'
            
            // Calculate new principal (include late fees if requested)
            $newPrincipal = $outstandingBalance;
            if (isset($validated['include_late_fees']) && $validated['include_late_fees']) {
                $newPrincipal += $totalLateFees;
            }
            
            // Ensure minimum principal
            if ($newPrincipal < 1000) {
                return redirect()->back()->with('error', 'Calculated principal is too low. Outstanding balance: ' . number_format($outstandingBalance) . ' UGX');
            }

            // Create restructured loan
            $restructuredLoan = new PersonalLoan();
            $restructuredLoan->member_id = $originalLoan->member_id;
            $restructuredLoan->product_type = $originalLoan->product_type;
            $restructuredLoan->code = 'R' . $originalLoan->code; // Prefix with R
            $restructuredLoan->interest = $validated['new_interest'];
            $restructuredLoan->interest_method = $originalLoan->interest_method;
            $restructuredLoan->period = $validated['new_period'];
            $restructuredLoan->principal = $newPrincipal;
            
            // Use LoanScheduleService to calculate installment
            $scheduleService = app(\App\Services\LoanScheduleService::class);
            
            // Temporarily save loan to generate schedules (LoanScheduleService needs a saved loan)
            $restructuredLoan->status = 2; // Active/Disbursed (restructured loans are immediately active)
            $restructuredLoan->verified = 1; // Automatically verified
            $restructuredLoan->date_approved = now()->format('Y-m-d H:i:s'); // Set approval date
            $restructuredLoan->added_by = auth()->id();
            $restructuredLoan->branch_id = $originalLoan->branch_id;
            $restructuredLoan->repay_strategy = $originalLoan->repay_strategy;
            $restructuredLoan->repay_name = $originalLoan->repay_name;
            $restructuredLoan->repay_address = $originalLoan->repay_address;
            $restructuredLoan->charge_type = $originalLoan->charge_type;
            $restructuredLoan->restructured = 1;
            $restructuredLoan->OLoanID = $originalLoan->code;
            $restructuredLoan->sign_code = $originalLoan->sign_code ?? 'RESTRUCTURED';
            $restructuredLoan->Rcomments = "Restructured Loan. Reason: {$validated['reason']}. Comments: {$validated['comments']}. Outstanding: " . number_format($outstandingBalance) . " UGX" . ((isset($validated['include_late_fees']) && $validated['include_late_fees']) ? ", Late Fees: " . number_format($totalLateFees) . " UGX" : ", Late Fees Waived");
            $restructuredLoan->datecreated = now();
            
            // Calculate a simple installment for now (will be recalculated by schedules)
            $totalInterest = $newPrincipal * ($validated['new_interest'] / 100);
            $totalPayable = $newPrincipal + $totalInterest;
            $restructuredLoan->installment = $totalPayable / $validated['new_period'];
            
            $restructuredLoan->save();
            
            // Now generate proper schedules using LoanScheduleService
            $schedules = $scheduleService->generateSchedule($restructuredLoan);
            
            // Insert schedules
            foreach ($schedules as $schedule) {
                LoanSchedule::create([
                    'loan_id' => $restructuredLoan->id,
                    'payment_date' => $schedule['payment_date'],
                    'principal' => $schedule['principal'],
                    'interest' => $schedule['interest'],
                    'payment' => $schedule['payment'],
                    'balance' => $schedule['balance'],
                    'paid' => 0,
                    'status' => 0, // Unpaid
                ]);
            }

            // Update original loan
            $originalLoan->status = 5; // Restructured (status 5: 0=Pending, 1=Approved, 2=Disbursed, 3=Closed, 4=Rejected, 5=Restructured)
            $originalLoan->restructured = 1;
            $originalLoan->Rcomments = "Loan restructured to {$restructuredLoan->code} on " . now()->format('Y-m-d H:i:s') . ". New Principal: " . number_format($newPrincipal) . " UGX";
            $originalLoan->date_closed = now();
            $originalLoan->save();

            // Waive late fees on old loan if not included in new principal
            if (!(isset($validated['include_late_fees']) && $validated['include_late_fees'])) {
                DB::table('late_fees')
                    ->where('loan_id', $originalLoan->id)
                    ->update(['status' => 2]); // Mark as waived
            }

            DB::commit();

            return redirect()->route('admin.loans.active')
                ->with('success', 'Loan restructured successfully! New loan code: ' . $restructuredLoan->code . 
                       '. Outstanding balance: ' . number_format($outstandingBalance) . ' UGX' .
                       ((isset($validated['include_late_fees']) && $validated['include_late_fees']) ? ' + Late Fees: ' . number_format($totalLateFees) . ' UGX' : ' (Late fees waived)') .
                       ' = New Principal: ' . number_format($newPrincipal) . ' UGX. New loan is now active and schedules have been generated.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Loan restructure error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error restructuring loan: ' . $e->getMessage());
        }
    }

    /**
     * Get period type name
     */
    private function getPeriodTypeName($periodType)
    {
        switch ($periodType) {
            case 1:
                return 'Weeks';
            case 2:
                return 'Months';
            case 3:
                return 'Days';
            default:
                return 'Periods';
        }
    }

    /**
     * Calculate days overdue for a loan
     */
    private function calculateDaysOverdue($loan)
    {
        $overdueSchedule = $loan->schedules()
            ->where('status', 0)
            ->whereRaw("STR_TO_DATE(payment_date, '%d-%m-%Y') < CURDATE()")
            ->orderBy('payment_date', 'asc')
            ->first();

        if ($overdueSchedule) {
            // payment_date is stored as 'Y-m-d' in database, not 'd-m-Y'
            $dueDate = \Carbon\Carbon::parse($overdueSchedule->payment_date);
            return now()->diffInDays($dueDate, false) * -1;
        }

        return 0;
    }

    /**
     * Upload or re-upload loan document
     */
    public function uploadDocument(Request $request, $id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'document_type' => 'required|in:trading,bank,business',
                'document' => 'required|file|max:51200'
            ]);

            // Find the loan
            $loan = PersonalLoan::findOrFail($id);

            // Map document type to database field
            $fieldMap = [
                'trading' => 'trading_file',
                'bank' => 'bank_file',
                'business' => 'business_file'
            ];

            $field = $fieldMap[$validated['document_type']];

            // Delete old file if it exists (check both old and new storage locations)
            if ($loan->$field) {
                // Try new location first (public/uploads)
                $oldFilePath = public_path($loan->$field);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                    \Log::info('Old document deleted from public/uploads', [
                        'loan_id' => $id,
                        'document_type' => $validated['document_type'],
                        'old_path' => $loan->$field
                    ]);
                } else {
                    // Try old location (storage/app/public)
                    $oldFilePath = storage_path('app/public/' . $loan->$field);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                        \Log::info('Old document deleted from storage/app/public', [
                            'loan_id' => $id,
                            'document_type' => $validated['document_type'],
                            'old_path' => $loan->$field
                        ]);
                    }
                }
            }

            // Upload new file - using FileStorageService (auto-uploads to DigitalOcean Spaces in production)
            $file = $request->file('document');
            if ($file->isValid()) {
                $path = FileStorageService::storeFile($file, 'loan-documents');
                $loan->$field = $path;
                $loan->save();

                \Log::info('Document uploaded successfully', [
                    'loan_id' => $id,
                    'loan_code' => $loan->code,
                    'document_type' => $validated['document_type'],
                    'path' => $path
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully',
                    'path' => $path
                ]);
            } else {
                \Log::error('Invalid file upload', [
                    'loan_id' => $id,
                    'document_type' => $validated['document_type']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is invalid'
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', $e->errors())
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Document upload error: ' . $e->getMessage(), [
                'loan_id' => $id,
                'document_type' => $request->document_type ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revert rejected loan back to pending approval
     */
    public function revertLoan(Request $request, $id)
    {
        try {
            // Check if user has permission to revert loans
            if (!in_array(auth()->user()->user_type, ['super_admin', 'administrator', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only super admin and administrator can revert rejected loans.'
                ], 403);
            }

            $loanType = $request->input('loan_type', 'personal');

            // Find the loan based on type
            if ($loanType === 'personal') {
                $loan = PersonalLoan::findOrFail($id);
            } else {
                $loan = GroupLoan::findOrFail($id);
            }

            // Check if loan is actually rejected
            if ($loan->status != 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only rejected loans can be reverted to pending.'
                ], 400);
            }

            DB::beginTransaction();

            // Revert loan to pending status
            $loan->status = 0; // Pending
            $loan->verified = 0; // Not verified
            $loan->rejected_by = null;
            $loan->date_rejected = null;
            $loan->comments = null;
            $loan->Rcomments = null;
            $loan->save();

            DB::commit();

            \Log::info('Loan reverted to pending', [
                'loan_id' => $id,
                'loan_code' => $loan->code,
                'loan_type' => $loanType,
                'reverted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Loan reverted to pending approval successfully. It can now go through the approval process again.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Loan revert error: ' . $e->getMessage(), [
                'loan_id' => $id,
                'loan_type' => $request->loan_type ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error reverting loan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete loan document
     */
    public function deleteDocument(Request $request, $id)
    {
        try {
            // Check if user has permission to delete documents
            if (!in_array(auth()->user()->user_type, ['super_admin', 'administrator', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only super admin and administrator can delete documents.'
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'document_type' => 'required|in:trading,bank,business'
            ]);

            // Find the loan
            $loan = PersonalLoan::findOrFail($id);

            // Map document type to database field
            $fieldMap = [
                'trading' => 'trading_file',
                'bank' => 'bank_file',
                'business' => 'business_file'
            ];

            $field = $fieldMap[$validated['document_type']];

            // Check if document exists
            if (!$loan->$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'No document to delete'
                ], 404);
            }

            // Delete file from storage (check both old and new locations)
            $filePath = public_path($loan->$field);
            if (file_exists($filePath)) {
                unlink($filePath);
                \Log::info('Document file deleted from public/uploads', [
                    'loan_id' => $id,
                    'document_type' => $validated['document_type'],
                    'path' => $loan->$field
                ]);
            } else {
                // Try old location (storage/app/public)
                $filePath = storage_path('app/public/' . $loan->$field);
                if (file_exists($filePath)) {
                    unlink($filePath);
                    \Log::info('Document file deleted from storage/app/public', [
                        'loan_id' => $id,
                        'document_type' => $validated['document_type'],
                        'path' => $loan->$field
                    ]);
                }
            }

            // Clear database field
            $loan->$field = null;
            $loan->save();

            \Log::info('Document deleted successfully', [
                'loan_id' => $id,
                'loan_code' => $loan->code,
                'document_type' => $validated['document_type']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', $e->errors())
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Document delete error: ' . $e->getMessage(), [
                'loan_id' => $id,
                'document_type' => $request->document_type ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add guarantor to loan
     */
    public function addGuarantor(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'member_id' => 'required|exists:members,id',
                'loan_type' => 'required|in:personal,group'
            ]);

            $loanType = $validated['loan_type'];
            
            // Get the loan
            if ($loanType === 'group') {
                $loan = \App\Models\GroupLoan::findOrFail($id);
            } else {
                $loan = \App\Models\PersonalLoan::findOrFail($id);
            }

            // Check if member is already a guarantor
            $existingGuarantor = \App\Models\Guarantor::where('loan_id', $id)
                ->where('member_id', $validated['member_id'])
                ->first();

            if ($existingGuarantor) {
                return response()->json([
                    'success' => false,
                    'message' => 'This member is already a guarantor for this loan'
                ], 422);
            }

            // Check if member is the borrower (for personal loans)
            if ($loanType === 'personal' && $loan->member_id == $validated['member_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'The borrower cannot be their own guarantor'
                ], 422);
            }

            // Create guarantor record
            \App\Models\Guarantor::create([
                'loan_id' => $id,
                'member_id' => $validated['member_id'],
                'added_by' => auth()->id()
            ]);

            \Log::info('Guarantor added to loan', [
                'loan_id' => $id,
                'loan_code' => $loan->code,
                'member_id' => $validated['member_id'],
                'added_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Guarantor added successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Add guarantor error: ' . $e->getMessage(), [
                'loan_id' => $id,
                'member_id' => $request->member_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding guarantor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove guarantor from loan
     */
    public function removeGuarantor($guarantorId)
    {
        try {
            $guarantor = \App\Models\Guarantor::findOrFail($guarantorId);
            
            \Log::info('Removing guarantor', [
                'guarantor_id' => $guarantorId,
                'loan_id' => $guarantor->loan_id,
                'member_id' => $guarantor->member_id,
                'removed_by' => auth()->id()
            ]);

            $guarantor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Guarantor removed successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Remove guarantor error: ' . $e->getMessage(), [
                'guarantor_id' => $guarantorId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error removing guarantor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually close a loan that has cleared schedules
     */
    public function closeLoanManually(Request $request, int $loanId, LoanClosureService $loanClosureService)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $loanClosureService->closeLoan($loanId, auth()->id());

            $message = $result['already_closed'] ?? false
                ? 'Loan is already marked as closed.'
                : 'Loan closed successfully.';

            if (($result['overpayment'] ?? 0) > 0) {
                $message .= ' Overpayment balance of UGX ' . number_format($result['overpayment'], 2) . ' remains.';
            }

            Log::info('Manual loan closure executed', [
                'loan_id' => $loanId,
                'user_id' => auth()->id(),
                'notes' => $request->input('notes'),
                'result' => $result,
            ]);

            $payload = [
                'success' => true,
                'message' => $message,
                'loan_id' => $loanId,
                'data' => $result,
            ];

            if ($request->wantsJson()) {
                return response()->json($payload);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Manual loan closure failed', [
                'loan_id' => $loanId,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}