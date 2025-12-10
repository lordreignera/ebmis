<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolLoan;
use App\Models\StudentLoan;
use App\Models\StaffLoan;
use App\Models\School;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Member;
use App\Models\Product;
use App\Models\Branch;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SchoolLoanController extends Controller
{
    /**
     * Show the form for creating a new school/student/staff loan
     */
    public function create(Request $request)
    {
        // Get loan type and period from request
        $loanType = $request->type ?? 'school';
        $repayPeriod = $request->period ?? 'daily';

        // Validate loan type is school-related
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            abort(404, 'Invalid loan type');
        }

        // Get eligible entities based on loan type
        if ($loanType === 'school') {
            // Get approved schools WITHOUT active loans
            // A school is eligible if they don't have any approved (status=1) or disbursed (status=2) loans
            // This is similar to how personal loans filter verified members without active loans
            $members = School::where('status', 'approved')
                            ->whereDoesntHave('schoolLoans', function($query) {
                                $query->whereIn('status', [1, 2]); // Approved or Disbursed (active loans)
                            })
                            ->orderBy('school_name')
                            ->get();
        } elseif ($loanType === 'student') {
            // Get active students WITHOUT active loans
            $members = Student::where('status', 'active')
                             ->with('school')
                             ->whereDoesntHave('studentLoans', function($query) {
                                 $query->whereIn('status', [1, 2]); // Approved or Disbursed (active loans)
                             })
                             ->orderBy('first_name')
                             ->orderBy('last_name')
                             ->get();
        } else { // staff
            // Get active staff WITHOUT active loans
            $members = Staff::where('status', 'active')
                           ->with('school')
                           ->whereDoesntHave('staffLoans', function($query) {
                               $query->whereIn('status', [1, 2]); // Approved or Disbursed (active loans)
                           })
                           ->orderBy('first_name')
                           ->orderBy('last_name')
                           ->get();
        }
        
        // Filter products based on loan type and period
        // Map loan type to product loan_type values
        $loanTypeMap = [
            'school' => 4,   // School loans
            'student' => 5,  // Student loans
            'staff' => 6     // Staff loans
        ];
        
        $productLoanType = $loanTypeMap[$loanType] ?? 4;
        // Removed loanProducts() scope to allow products with any 'type' value
        // We filter by loan_type instead which is more specific for school/student/staff loans
        $productsQuery = Product::active()->where('loan_type', $productLoanType);
        
        if ($repayPeriod) {
            // Map repay period to period_type based on database values:
            // period_type: 1=Days, 2=Weeks, 3=Months, 4=Years
            $periodTypeMap = [
                'daily' => 1,    // Days
                'weekly' => 2,   // Weeks  
                'monthly' => 3   // Months
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
        
        // If specific view doesn't exist, fall back to personal loan view
        if (!view()->exists($viewName)) {
            $viewName = "admin.loans.create_personal_{$repayPeriod}";
        }

        return view($viewName, compact('members', 'products', 'branches', 'selectedMember', 'loanType', 'repayPeriod'));
    }

    /**
     * Store a newly created school/student/staff loan
     */
    public function store(Request $request)
    {
        $loanType = $request->input('loan_type', 'school');
        
        // Validate loan type is school-related
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            return back()->withErrors(['loan_type' => 'Invalid loan type']);
        }

        // Build validation rules based on loan type
        $rules = [
            'product_type' => 'required|exists:products,id',
            'interest' => 'required|numeric|min:0|max:100',
            'period' => 'required|integer|min:1',
            'principal' => 'required|numeric|min:1000',
            'max_installment' => 'required|numeric|min:1',
            'branch_id' => 'required|exists:branches,id',
            'loan_type' => 'required|in:school,student,staff',
            'repay_period' => 'required|in:daily,weekly,monthly',
            'repay_strategy' => 'required|in:1,2',
            'business_name' => 'required|string|max:255',
            'business_contact' => 'required|string|max:500',
            'business_license' => 'required|file|max:5120',
            'bank_statement' => 'required|file|max:5120',
            'business_photos' => 'required|file|max:5120',
            'loan_code' => 'nullable|string|max:50',
        ];

        // Add specific entity ID validation based on loan type
        if ($loanType === 'school') {
            $rules['member_id'] = 'required|exists:schools,id';
        } elseif ($loanType === 'student') {
            $rules['member_id'] = 'required|exists:students,id';
        } else { // staff
            $rules['member_id'] = 'required|exists:staff,id';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            // Handle file uploads - using permanent public storage
            $businessLicensePath = null;
            $bankStatementPath = null;
            $businessPhotosPath = null;

            if ($request->hasFile('business_license')) {
                $businessLicensePath = FileStorageService::storeFile($request->file('business_license'), 'loan-documents');
            }

            if ($request->hasFile('bank_statement')) {
                $bankStatementPath = FileStorageService::storeFile($request->file('bank_statement'), 'loan-documents');
            }

            if ($request->hasFile('business_photos')) {
                $businessPhotosPath = FileStorageService::storeFile($request->file('business_photos'), 'loan-documents');
            }

            // Generate loan code if not provided
            $loanCode = $validated['loan_code'] ?? $this->generateLoanCode($loanType);

            // Prepare common loan data
            $loanData = [
                'code' => $loanCode,
                'product_type' => $validated['product_type'],
                'interest' => $validated['interest'],
                'period' => $validated['period'],
                'principal' => $validated['principal'],
                'installment' => $validated['max_installment'],
                'branch_id' => $validated['branch_id'],
                'status' => 0, // Pending approval
                'verified' => 0,
                'added_by' => auth()->id(),
                'repay_period' => $validated['repay_period'],
                'repay_strategy' => $validated['repay_strategy'],
                'business_name' => $validated['business_name'],
                'business_contact' => $validated['business_contact'],
                'business_license' => $businessLicensePath,
                'bank_statement' => $bankStatementPath,
                'business_photos' => $businessPhotosPath,
            ];

            // Create the loan record in appropriate table
            if ($loanType === 'school') {
                $loanData['school_id'] = $validated['member_id'];
                $loan = SchoolLoan::create($loanData);
            } elseif ($loanType === 'student') {
                // Get student's school_id automatically
                $student = Student::findOrFail($validated['member_id']);
                $loanData['student_id'] = $validated['member_id'];
                $loanData['school_id'] = $student->school_id;
                $loan = StudentLoan::create($loanData);
            } else { // staff
                // Get staff's school_id automatically
                $staff = Staff::findOrFail($validated['member_id']);
                $loanData['staff_id'] = $validated['member_id'];
                $loanData['school_id'] = $staff->school_id;
                $loan = StaffLoan::create($loanData);
            }

            DB::commit();

            return redirect()
                ->route('admin.school.loans.approvals', ['type' => $loanType])
                ->with('success', ucfirst($loanType) . ' loan application created successfully! Awaiting approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files if transaction fails
            if ($businessLicensePath) Storage::disk('public')->delete($businessLicensePath);
            if ($bankStatementPath) Storage::disk('public')->delete($bankStatementPath);
            if ($businessPhotosPath) Storage::disk('public')->delete($businessPhotosPath);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create loan: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate a unique loan code based on loan type
     */
    private function generateLoanCode($loanType)
    {
        $prefix = match($loanType) {
            'school' => 'SCH',
            'student' => 'STU',
            'staff' => 'STF',
            default => 'SCH'
        };

        $timestamp = now()->format('YmdHis');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$timestamp}{$random}";
    }

    /**
     * Display approvals for school/student/staff loans
     */
    public function approvals(Request $request)
    {
        $loanType = $request->type ?? 'school';
        
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            abort(404, 'Invalid loan type');
        }

        $showRejected = $request->has('show_rejected') && $request->show_rejected == 1;
        $status = $showRejected ? 4 : 0; // 4 = Rejected, 0 = Pending

        // Get loans from appropriate table with relationships
        if ($loanType === 'school') {
            $loans = SchoolLoan::with(['school', 'product', 'branch', 'addedBy', 'rejectedBy'])
                ->where('status', $status)
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $stats = [
                'pending_approval' => SchoolLoan::where('status', 0)->count(),
                'approved_loans' => SchoolLoan::where('status', 1)->count(),
                'pending_amount' => SchoolLoan::where('status', 0)->sum('principal'),
                'approved_amount' => SchoolLoan::where('status', 1)->sum('principal'),
            ];
        } elseif ($loanType === 'student') {
            $loans = StudentLoan::with(['student', 'school', 'product', 'branch', 'addedBy', 'rejectedBy'])
                ->where('status', $status)
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $stats = [
                'pending_approval' => StudentLoan::where('status', 0)->count(),
                'approved_loans' => StudentLoan::where('status', 1)->count(),
                'pending_amount' => StudentLoan::where('status', 0)->sum('principal'),
                'approved_amount' => StudentLoan::where('status', 1)->sum('principal'),
            ];
        } else { // staff
            $loans = StaffLoan::with(['staff', 'school', 'product', 'branch', 'addedBy', 'rejectedBy'])
                ->where('status', $status)
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $stats = [
                'pending_approval' => StaffLoan::where('status', 0)->count(),
                'approved_loans' => StaffLoan::where('status', 1)->count(),
                'pending_amount' => StaffLoan::where('status', 0)->sum('principal'),
                'approved_amount' => StaffLoan::where('status', 1)->sum('principal'),
            ];
        }

        $loanTypeDisplay = ucfirst($loanType);
        
        return view('admin.loans.approvals', compact('loans', 'showRejected', 'loanType', 'loanTypeDisplay', 'stats'));
    }

    /**
     * Display pending disbursements for school/student/staff loans
     */
    public function disbursements(Request $request)
    {
        $loanType = $request->type ?? 'school';
        
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            abort(404, 'Invalid loan type');
        }

        // Get approved loans without successful disbursement records
        if ($loanType === 'school') {
            $loans = SchoolLoan::with(['school', 'product', 'branch', 'fees'])
                ->where('status', 1) // Approved
                ->whereDoesntHave('disbursements', function($query) {
                    $query->where('status', 2); // Status 2 = Successfully Disbursed
                })
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $allPending = SchoolLoan::where('status', 1)
                ->whereDoesntHave('disbursements', function($query) {
                    $query->where('status', 2);
                })
                ->get();
            
            $stats = [
                'total_pending' => $allPending->count(),
                'total_amount' => $allPending->sum('principal'),
                'pending_today' => SchoolLoan::where('status', 1)
                    ->whereDoesntHave('disbursements', function($query) {
                        $query->where('status', 2);
                    })
                    ->whereDate('date_approved', today())
                    ->count(),
            ];
        } elseif ($loanType === 'student') {
            $loans = StudentLoan::with(['student', 'school', 'product', 'branch', 'fees'])
                ->where('status', 1) // Approved
                ->whereDoesntHave('disbursements', function($query) {
                    $query->where('status', 2); // Status 2 = Successfully Disbursed
                })
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $allPending = StudentLoan::where('status', 1)
                ->whereDoesntHave('disbursements', function($query) {
                    $query->where('status', 2);
                })
                ->get();
            
            $stats = [
                'total_pending' => $allPending->count(),
                'total_amount' => $allPending->sum('principal'),
                'pending_today' => StudentLoan::where('status', 1)
                    ->whereDoesntHave('disbursements', function($query) {
                        $query->where('status', 2);
                    })
                    ->whereDate('date_approved', today())
                    ->count(),
            ];
        } else { // staff
            $loans = StaffLoan::with(['staff', 'school', 'product', 'branch', 'fees'])
                ->where('status', 1) // Approved
                ->whereDoesntHave('disbursements', function($query) {
                    $query->where('status', 2); // Status 2 = Successfully Disbursed
                })
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $allPending = StaffLoan::where('status', 1)
                ->whereDoesntHave('disbursements', function($query) {
                    $query->where('status', 2);
                })
                ->get();
            
            $stats = [
                'total_pending' => $allPending->count(),
                'total_amount' => $allPending->sum('principal'),
                'pending_today' => StaffLoan::where('status', 1)
                    ->whereDoesntHave('disbursements', function($query) {
                        $query->where('status', 2);
                    })
                    ->whereDate('date_approved', today())
                    ->count(),
            ];
        }

        $loanTypeDisplay = ucfirst($loanType);
        
        // Get branches and products for filters
        $branches = Branch::active()->get();
        $products = Product::active()->where('loan_type', 1)->get(); // Individual loan products
        
        return view('admin.loans.disbursements.pending', compact('loans', 'loanType', 'loanTypeDisplay', 'stats', 'branches', 'products'));
    }

    /**
     * Display active school/student/staff loans
     */
    public function active(Request $request)
    {
        $loanType = $request->type ?? 'school';
        
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            abort(404, 'Invalid loan type');
        }

        // Get disbursed loans with unpaid schedules
        if ($loanType === 'school') {
            $loans = SchoolLoan::with(['school', 'product', 'branch', 'schedules'])
                ->where('status', 2) // Disbursed
                ->whereHas('schedules', function($query) {
                    $query->where('status', 0); // Unpaid schedules
                })
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $allActive = SchoolLoan::where('status', 2)
                ->whereHas('schedules', function($query) {
                    $query->where('status', 0);
                })
                ->with('schedules')
                ->get();
            
            $stats = [
                'total_active' => $allActive->count(),
                'outstanding_amount' => $allActive->sum(function($loan) {
                    return $loan->schedules->where('status', 0)->sum('principal');
                }),
                'overdue_count' => $allActive->filter(function($loan) {
                    return $loan->schedules->where('status', 0)->where('payment_date', '<', now())->count() > 0;
                })->count(),
                'collections_today' => $allActive->sum(function($loan) {
                    return $loan->schedules->where('status', 1)->whereDate('date_modified', today())->sum('payment');
                }),
            ];
        } elseif ($loanType === 'student') {
            $loans = StudentLoan::with(['student', 'school', 'product', 'branch', 'schedules'])
                ->where('status', 2) // Disbursed
                ->whereHas('schedules', function($query) {
                    $query->where('status', 0); // Unpaid schedules
                })
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $allActive = StudentLoan::where('status', 2)
                ->whereHas('schedules', function($query) {
                    $query->where('status', 0);
                })
                ->with('schedules')
                ->get();
            
            $stats = [
                'total_active' => $allActive->count(),
                'outstanding_amount' => $allActive->sum(function($loan) {
                    return $loan->schedules->where('status', 0)->sum('principal');
                }),
                'overdue_count' => $allActive->filter(function($loan) {
                    return $loan->schedules->where('status', 0)->where('payment_date', '<', now())->count() > 0;
                })->count(),
                'collections_today' => $allActive->sum(function($loan) {
                    return $loan->schedules->where('status', 1)->whereDate('date_modified', today())->sum('payment');
                }),
            ];
        } else { // staff
            $loans = StaffLoan::with(['staff', 'school', 'product', 'branch', 'schedules'])
                ->where('status', 2) // Disbursed
                ->whereHas('schedules', function($query) {
                    $query->where('status', 0); // Unpaid schedules
                })
                ->orderBy('datecreated', 'desc')
                ->paginate(20);
            
            // Calculate stats
            $allActive = StaffLoan::where('status', 2)
                ->whereHas('schedules', function($query) {
                    $query->where('status', 0);
                })
                ->with('schedules')
                ->get();
            
            $stats = [
                'total_active' => $allActive->count(),
                'outstanding_amount' => $allActive->sum(function($loan) {
                    return $loan->schedules->where('status', 0)->sum('principal');
                }),
                'overdue_count' => $allActive->filter(function($loan) {
                    return $loan->schedules->where('status', 0)->where('payment_date', '<', now())->count() > 0;
                })->count(),
                'collections_today' => $allActive->sum(function($loan) {
                    return $loan->schedules->where('status', 1)->whereDate('date_modified', today())->sum('payment');
                }),
            ];
        }

        $loanTypeDisplay = ucfirst($loanType);
        
        // Get branches and products for filters
        $branches = Branch::active()->get();
        $products = Product::active()->where('loan_type', 1)->get(); // Individual loan products
        
        return view('admin.loans.active', compact('loans', 'loanType', 'loanTypeDisplay', 'stats', 'branches', 'products'));
    }

    /**
     * Display portfolio for school/student/staff loans
     */
    public function portfolio(Request $request)
    {
        $loanType = $request->type ?? 'school';
        
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            abort(404, 'Invalid loan type');
        }

        // Get all loans from appropriate table
        if ($loanType === 'school') {
            $loans = SchoolLoan::with(['school', 'product', 'branch'])->get();
        } elseif ($loanType === 'student') {
            $loans = StudentLoan::with(['student', 'school', 'product', 'branch'])->get();
        } else { // staff
            $loans = StaffLoan::with(['staff', 'school', 'product', 'branch'])->get();
        }

        $stats = [
            'total_loans' => $loans->count(),
            'total_principal' => $loans->sum('principal'),
            'pending' => $loans->where('status', 0)->count(),
            'approved' => $loans->where('status', 1)->count(),
            'disbursed' => $loans->where('status', 2)->count(),
            'completed' => $loans->where('status', 3)->count(),
            'rejected' => $loans->where('status', 4)->count(),
        ];

        $loanTypeDisplay = ucfirst($loanType);
        
        return view('admin.portfolio.individual', compact('loans', 'stats', 'loanType', 'loanTypeDisplay'));
    }

    /**
     * Display repayments for school/student/staff loans
     */
    public function repayments(Request $request)
    {
        $loanType = $request->type ?? 'school';
        
        if (!in_array($loanType, ['school', 'student', 'staff'])) {
            abort(404, 'Invalid loan type');
        }

        // NOTE: This method is placeholder for future implementation
        // The current repayment system (Repayment model) references the old Loan model
        // which uses personal_loans and group_loans tables.
        // 
        // To fully implement repayments for school/student/staff loans, we need to:
        // 1. Update the Repayment model to support polymorphic relationships
        // 2. Update LoanSchedule to reference school_loans/student_loans/staff_loans
        // 3. Update disbursement tracking for new loan types
        // 4. Create repayment views specific to school loan types
        //
        // For now, redirect to active loans page
        return redirect()->route('admin.school.loans.active', ['type' => $loanType])
            ->with('info', 'Repayment management for ' . ucfirst($loanType) . ' loans is currently under development. Please use the Active Loans page to view loan schedules.');
    }
}
