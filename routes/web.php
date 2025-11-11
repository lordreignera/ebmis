

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SchoolRegistrationController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Explicit login route (in case Fortify doesn't register it)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// School Registration Routes (Public)
Route::get('/school/register', [SchoolRegistrationController::class, 'show'])->name('school.register');
Route::post('/school/register', [SchoolRegistrationController::class, 'store'])->name('school.register.store');
Route::get('/school/assessment', [SchoolRegistrationController::class, 'showAssessment'])->name('school.assessment');
Route::post('/school/assessment', [SchoolRegistrationController::class, 'storeAssessment'])->name('school.assessment.store');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        // Redirect Super Admin to admin dashboard
        if ($user->hasRole('Super Administrator') || $user->hasRole('superadmin')) {
            return redirect()->route('admin.home');
        }
        
        // Redirect Branch Managers to admin dashboard
        if ($user->hasRole('Branch Manager')) {
            return redirect()->route('admin.home');
        }
        
        // Redirect School users to school dashboard
        if ($user->user_type === 'school' && $user->school) {
            return redirect()->route('school.dashboard');
        }
        
        // Default dashboard for other users
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin/home', [\App\Http\Controllers\AdminController::class, 'home'])->name('admin.home');
    
    // Table Demo Route (for testing enhanced tables)
    Route::get('/admin/tables-demo', function() {
        return view('admin.tables-demo');
    })->name('admin.tables.demo');
    
    // School Dashboard Route
    Route::get('/school/dashboard', [\App\Http\Controllers\School\SchoolDashboardController::class, 'index'])
        ->name('school.dashboard');
});

// Admin user management routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/admin/users/{user}/edit', [\App\Http\Controllers\AdminController::class, 'editUser'])->name('admin.users.edit');
});

// EBIMS Module Routes (Super Admin + Branch Manager access)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'ebims_module'
])->prefix('admin')->name('admin.')->group(function () {
    
    // Member Management Routes
    Route::resource('members', \App\Http\Controllers\Admin\MemberController::class);
    Route::get('/members-pending', [\App\Http\Controllers\Admin\MemberController::class, 'pending'])->name('members.pending');
    Route::post('/members/{member}/approve', [\App\Http\Controllers\Admin\MemberController::class, 'approve'])->name('members.approve');
    Route::post('/members/{member}/reject', [\App\Http\Controllers\Admin\MemberController::class, 'reject'])->name('members.reject');
    Route::post('/members/{member}/suspend', [\App\Http\Controllers\Admin\MemberController::class, 'suspend'])->name('members.suspend');
    Route::get('/members/{member}/details', [\App\Http\Controllers\Admin\MemberController::class, 'getMemberDetails'])->name('members.details');
    Route::post('/members/check-duplicate', [\App\Http\Controllers\Admin\MemberController::class, 'checkDuplicate'])->name('members.check-duplicate');
    
    // Tables Demo Route
    Route::get('/tables-demo', function () {
        return view('admin.tables-demo');
    })->name('tables.demo');
    
    // Modern Table Demo Route
    Route::get('/modern-table', function () {
        return view('admin.modern-table');
    })->name('modern.table');
    
    // Loan Additional Routes (Must be BEFORE resource route to avoid conflicts)
    Route::get('/loans/esign', [\App\Http\Controllers\Admin\LoanController::class, 'esignIndex'])->name('loans.esign');
    Route::get('/loans/approvals', [\App\Http\Controllers\Admin\LoanController::class, 'approvalsIndex'])->name('loans.approvals');
    Route::get('/loans/export', [\App\Http\Controllers\Admin\LoanController::class, 'export'])->name('loans.export');
    Route::get('/loans/active', [\App\Http\Controllers\Admin\RepaymentController::class, 'activeLoans'])->name('loans.active');
    Route::get('/loans/active/export', [\App\Http\Controllers\Admin\RepaymentController::class, 'exportActiveLoans'])->name('loans.active.export');
    
    // Loan Create Route (Must be BEFORE {id} routes)
    Route::get('/loans/create', [\App\Http\Controllers\Admin\LoanController::class, 'create'])->name('loans.create');
    
    // Loan Show Route (explicit to handle type parameter)
    Route::get('/loans/{id}', [\App\Http\Controllers\Admin\LoanController::class, 'show'])->name('loans.show');
    Route::get('/loans/{id}/edit', [\App\Http\Controllers\Admin\LoanController::class, 'edit'])->name('loans.edit');
    
    // Loan Management Routes
    Route::resource('loans', \App\Http\Controllers\Admin\LoanController::class)->except(['show', 'edit', 'create']);
    Route::get('/loans/{loan}/details', [\App\Http\Controllers\Admin\LoanController::class, 'getLoanDetails'])->name('loans.details');
    Route::post('/loans/{loan}/approve', [\App\Http\Controllers\Admin\LoanController::class, 'approve'])->name('loans.approve');
    Route::post('/loans/{loan}/reject', [\App\Http\Controllers\Admin\LoanController::class, 'reject'])->name('loans.reject');
    Route::post('/loans/{loan}/pay-fees', [\App\Http\Controllers\Admin\LoanController::class, 'payFees'])->name('loans.pay-fees');
    Route::post('/loans/{loan}/pay-single-fee', [\App\Http\Controllers\Admin\LoanController::class, 'paySingleFee'])->name('loans.pay-single-fee');
    Route::put('/loans/{loan}/update-charge-type', [\App\Http\Controllers\Admin\LoanController::class, 'updateChargeType'])->name('loans.update-charge-type');
    
    // Enhanced Loan Services Integration
    Route::post('/loans/generate-schedule-service', [\App\Http\Controllers\Admin\LoanController::class, 'generateScheduleWithService'])->name('loans.generate-schedule-service');
    Route::post('/loans/calculate-fees-service', [\App\Http\Controllers\Admin\LoanController::class, 'calculateFeesWithService'])->name('loans.calculate-fees-service');
    Route::post('/loans/check-eligibility-service', [\App\Http\Controllers\Admin\LoanController::class, 'checkEligibilityWithService'])->name('loans.check-eligibility-service');
    
    // Loan Agreement & Signing Routes
    Route::get('/loans/agreements', [\App\Http\Controllers\Admin\LoanController::class, 'agreements'])->name('loans.agreements');
    Route::post('/loans/send-otp', [\App\Http\Controllers\Admin\LoanController::class, 'sendOTP'])->name('loans.send-otp');
    Route::post('/loans/sign-agreement', [\App\Http\Controllers\Admin\LoanController::class, 'signAgreement'])->name('loans.sign-agreement');
    Route::get('/loans/view-agreement/{id}/{type}', [\App\Http\Controllers\Admin\LoanController::class, 'viewAgreement'])->name('loans.view-agreement');
    Route::get('/loans/download-signed-agreement/{id}/{type}', [\App\Http\Controllers\Admin\LoanController::class, 'downloadSignedAgreement'])->name('loans.download-signed-agreement');
    
    // Enhanced Loan Management Routes (New Services Integration)
    Route::prefix('loan-management')->name('loan-management.')->group(function () {
        // Loan Approval Routes
        Route::get('/approve/{id}/{type?}', [\App\Http\Controllers\Admin\LoanManagementController::class, 'showLoanApproval'])->name('approve.show');
        Route::post('/approve', [\App\Http\Controllers\Admin\LoanManagementController::class, 'approveLoan'])->name('approve');
        Route::post('/reject', [\App\Http\Controllers\Admin\LoanManagementController::class, 'rejectLoan'])->name('reject');
        
        // Disbursement Routes
        Route::get('/disbursements', [\App\Http\Controllers\Admin\LoanManagementController::class, 'showDisbursements'])->name('disbursements');
        Route::post('/disbursements/process', [\App\Http\Controllers\Admin\LoanManagementController::class, 'processDisbursement'])->name('disbursements.process');
        
        // Repayment Routes
        Route::get('/repayments', [\App\Http\Controllers\Admin\LoanManagementController::class, 'showRepayments'])->name('repayments');
        Route::post('/repayments/process', [\App\Http\Controllers\Admin\LoanManagementController::class, 'processRepayment'])->name('repayments.process');
        
        // Schedule Routes
        Route::get('/schedule/{id}/{type?}', [\App\Http\Controllers\Admin\LoanManagementController::class, 'showLoanSchedule'])->name('schedule.show');
        Route::post('/schedule/generate', [\App\Http\Controllers\Admin\LoanManagementController::class, 'generateSchedule'])->name('schedule.generate');
        
        // Fee Management Routes
        Route::get('/fees/{id}/{type?}', [\App\Http\Controllers\Admin\LoanManagementController::class, 'showLoanFees'])->name('fees.show');
        
        // Mobile Money Routes
        Route::post('/mobile-money/callback', [\App\Http\Controllers\Admin\LoanManagementController::class, 'mobileMoneyCallback'])->name('mobile-money.callback');
        Route::get('/mobile-money/test-connection', [\App\Http\Controllers\Admin\LoanManagementController::class, 'testMobileMoneyConnection'])->name('mobile-money.test');
    });
    
    // Disbursement Management Routes
    Route::resource('disbursements', \App\Http\Controllers\Admin\DisbursementController::class);
    Route::get('/disbursements/loan-details/{loan}', [\App\Http\Controllers\Admin\DisbursementController::class, 'getLoanDetails'])->name('disbursements.loan-details');
    Route::post('/disbursements/{disbursement}/complete', [\App\Http\Controllers\Admin\DisbursementController::class, 'complete'])->name('disbursements.complete');
    Route::post('/disbursements/{disbursement}/cancel', [\App\Http\Controllers\Admin\DisbursementController::class, 'cancel'])->name('disbursements.cancel');
    Route::post('/disbursements/{disbursement}/retry', [\App\Http\Controllers\Admin\DisbursementController::class, 'retry'])->name('disbursements.retry');
    
    // NEW: Enhanced Disbursement Routes for UI
    Route::prefix('loans/disbursements')->name('loans.disbursements.')->group(function () {
        Route::get('/pending', [\App\Http\Controllers\Admin\DisbursementController::class, 'pending'])->name('pending');
        Route::get('/approve/{id}', [\App\Http\Controllers\Admin\DisbursementController::class, 'showApprove'])->name('approve.show');
        Route::put('/approve/{id}', [\App\Http\Controllers\Admin\DisbursementController::class, 'approve'])->name('approve');
        Route::post('/check-status/{id}', [\App\Http\Controllers\Admin\DisbursementController::class, 'checkStatus'])->name('check-status');
        Route::get('/export', [\App\Http\Controllers\Admin\DisbursementController::class, 'export'])->name('export');
    });
    
    // NEW: Enhanced Repayment Routes for UI
    Route::resource('repayments', \App\Http\Controllers\Admin\RepaymentController::class);
    Route::get('/repayments/pending', [\App\Http\Controllers\Admin\RepaymentController::class, 'pending'])->name('repayments.pending');
    Route::get('/repayments/history', [\App\Http\Controllers\Admin\RepaymentController::class, 'history'])->name('repayments.history');
    Route::get('/repayments/loan-details/{loan}', [\App\Http\Controllers\Admin\RepaymentController::class, 'getLoanDetails'])->name('repayments.loan-details');
    Route::get('/repayments/{repayment}/receipt', [\App\Http\Controllers\Admin\RepaymentController::class, 'receipt'])->name('repayments.receipt');
    
    // NEW: Enhanced Repayment Routes for UI
    Route::prefix('loans/repayments')->name('loans.repayments.')->group(function () {
        Route::get('/schedules/{id}', [\App\Http\Controllers\Admin\RepaymentController::class, 'schedules'])->name('schedules');
        Route::post('/quick', [\App\Http\Controllers\Admin\RepaymentController::class, 'quickRepayment'])->name('quick');
        Route::post('/store', [\App\Http\Controllers\Admin\RepaymentController::class, 'storeRepayment'])->name('store');
        Route::post('/partial', [\App\Http\Controllers\Admin\RepaymentController::class, 'partialPayment'])->name('partial');
    });
    
    // Loan next schedule route
    Route::get('/loans/{id}/next-schedule', [\App\Http\Controllers\Admin\RepaymentController::class, 'getNextSchedule'])->name('loans.next-schedule');
    
    // NEW: Loan History and Statements Routes
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/{id}/history', [\App\Http\Controllers\Admin\LoanController::class, 'history'])->name('history');
        Route::get('/{id}/restructure', [\App\Http\Controllers\Admin\LoanController::class, 'restructure'])->name('restructure');
        Route::get('/{id}/statements/print', [\App\Http\Controllers\Admin\LoanController::class, 'printStatement'])->name('statements.print');
        Route::get('/{id}/schedules/print', [\App\Http\Controllers\Admin\LoanController::class, 'printSchedule'])->name('schedules.print');
        Route::get('/{id}/notices/print', [\App\Http\Controllers\Admin\LoanController::class, 'printNotice'])->name('notices.print');
    });
    
    // Portfolio Management Routes
    Route::get('/portfolio/running', [\App\Http\Controllers\Admin\PortfolioController::class, 'running'])->name('portfolio.running');
    Route::get('/portfolio/pending', [\App\Http\Controllers\Admin\PortfolioController::class, 'pending'])->name('portfolio.pending');
    Route::get('/portfolio/overdue', [\App\Http\Controllers\Admin\PortfolioController::class, 'overdue'])->name('portfolio.overdue');
    Route::get('/portfolio/paid', [\App\Http\Controllers\Admin\PortfolioController::class, 'paid'])->name('portfolio.paid');
    Route::get('/portfolio/bad', [\App\Http\Controllers\Admin\PortfolioController::class, 'bad'])->name('portfolio.bad');
    Route::get('/portfolio/branch', [\App\Http\Controllers\Admin\PortfolioController::class, 'branch'])->name('portfolio.branch');
    Route::get('/portfolio/product', [\App\Http\Controllers\Admin\PortfolioController::class, 'product'])->name('portfolio.product');
    Route::get('/portfolio/individual', [\App\Http\Controllers\Admin\PortfolioController::class, 'individual'])->name('portfolio.individual');
    Route::get('/portfolio/group', [\App\Http\Controllers\Admin\PortfolioController::class, 'group'])->name('portfolio.group');
    
    // Bulk SMS Routes
    Route::resource('bulk-sms', \App\Http\Controllers\Admin\BulkSmsController::class);
    
    // Groups Management Routes
    Route::resource('groups', \App\Http\Controllers\Admin\GroupController::class);
    Route::post('/groups/{group}/approve', [\App\Http\Controllers\Admin\GroupController::class, 'approve'])->name('groups.approve');
    Route::post('/groups/{group}/suspend', [\App\Http\Controllers\Admin\GroupController::class, 'suspend'])->name('groups.suspend');
    Route::post('/groups/{group}/activate', [\App\Http\Controllers\Admin\GroupController::class, 'activate'])->name('groups.activate');
    Route::get('/groups/{group}/members', [\App\Http\Controllers\Admin\GroupController::class, 'members'])->name('groups.members');
    Route::post('/groups/{group}/add-member', [\App\Http\Controllers\Admin\GroupController::class, 'addMember'])->name('groups.add-member');
    Route::delete('/groups/{group}/remove-member/{member}', [\App\Http\Controllers\Admin\GroupController::class, 'removeMember'])->name('groups.remove-member');
    Route::get('/groups/{group}/available-members', [\App\Http\Controllers\Admin\GroupController::class, 'getAvailableMembers'])->name('groups.available-members');
    Route::get('/groups/{group}/loan-eligibility', [\App\Http\Controllers\Admin\GroupController::class, 'checkLoanEligibility'])->name('groups.loan-eligibility');
    
    // Fee Management Routes
    Route::resource('fees', \App\Http\Controllers\Admin\FeeController::class);
    Route::post('/fees/{fee}/mark-paid', [\App\Http\Controllers\Admin\FeeController::class, 'markAsPaid'])->name('fees.mark-paid');
    Route::get('/fees/{fee}/receipt', [\App\Http\Controllers\Admin\FeeController::class, 'receipt'])->name('fees.receipt');
    Route::get('/fees/{fee}/receipt-modal', [\App\Http\Controllers\Admin\FeeController::class, 'getReceiptModal'])->name('fees.receipt-modal');
    Route::get('/members/{member}/quick-info', [\App\Http\Controllers\Admin\MemberController::class, 'quickInfo'])->name('members.quick-info');
    Route::get('/members/{member}/loans', [\App\Http\Controllers\Admin\MemberController::class, 'getLoans'])->name('members.loans');
    Route::get('/members/{member}/recent-fees', [\App\Http\Controllers\Admin\MemberController::class, 'getRecentFees'])->name('members.recent-fees');
    Route::get('/fee-types/{feeType}/info', [\App\Http\Controllers\Admin\FeeController::class, 'getFeeTypeInfo'])->name('fee-types.info');
    
    // Savings Management Routes
    Route::resource('savings', \App\Http\Controllers\Admin\SavingController::class);
    Route::get('/savings/pending', [\App\Http\Controllers\Admin\SavingController::class, 'pending'])->name('savings.pending');
    Route::get('/savings/approved', [\App\Http\Controllers\Admin\SavingController::class, 'approved'])->name('savings.approved');
    Route::post('/savings/{saving}/approve', [\App\Http\Controllers\Admin\SavingController::class, 'approve'])->name('savings.approve');
    Route::post('/savings/{saving}/reject', [\App\Http\Controllers\Admin\SavingController::class, 'reject'])->name('savings.reject');
    
    // Investment Management Routes
    Route::prefix('investments')->name('investments.')->group(function () {
        // Investment Dashboard
        Route::get('/', [\App\Http\Controllers\Admin\InvestmentController::class, 'index'])->name('index');
        
        // Investor Management
        Route::get('/investors', [\App\Http\Controllers\Admin\InvestmentController::class, 'investors'])->name('investors');
        Route::get('/investors/create', [\App\Http\Controllers\Admin\InvestmentController::class, 'createInvestor'])->name('create-investor');
        Route::post('/investors', [\App\Http\Controllers\Admin\InvestmentController::class, 'storeInvestor'])->name('store-investor');
        Route::get('/investors/{investor}', [\App\Http\Controllers\Admin\InvestmentController::class, 'showInvestor'])->name('show-investor');
        Route::get('/investors/{investor}/edit', [\App\Http\Controllers\Admin\InvestmentController::class, 'editInvestor'])->name('edit-investor');
        Route::put('/investors/{investor}', [\App\Http\Controllers\Admin\InvestmentController::class, 'updateInvestor'])->name('update-investor');
        Route::post('/investors/{investor}/activate', [\App\Http\Controllers\Admin\InvestmentController::class, 'activateInvestor'])->name('activate-investor');
        Route::post('/investors/{investor}/deactivate', [\App\Http\Controllers\Admin\InvestmentController::class, 'deactivateInvestor'])->name('deactivate-investor');
        
        // Investment Management
        Route::get('/investments/{investor}/create', [\App\Http\Controllers\Admin\InvestmentController::class, 'createInvestment'])->name('create-investment');
        Route::post('/investments/{investor}', [\App\Http\Controllers\Admin\InvestmentController::class, 'storeInvestment'])->name('store-investment');
        Route::get('/investments/{investment}/show', [\App\Http\Controllers\Admin\InvestmentController::class, 'showInvestment'])->name('show-investment');
        Route::get('/investments/{investment}/edit', [\App\Http\Controllers\Admin\InvestmentController::class, 'editInvestment'])->name('edit-investment');
        Route::put('/investments/{investment}', [\App\Http\Controllers\Admin\InvestmentController::class, 'updateInvestment'])->name('update-investment');
        Route::delete('/investments/{investment}', [\App\Http\Controllers\Admin\InvestmentController::class, 'destroyInvestment'])->name('destroy');
        
        // Investment Calculations
        Route::post('/calculate-returns', [\App\Http\Controllers\Admin\InvestmentController::class, 'calculateReturns'])->name('calculate-returns');
        
        // API Routes for dropdowns
        Route::get('/api/investor-portfolio/{investor}', [\App\Http\Controllers\Admin\InvestmentController::class, 'getInvestorPortfolio'])->name('api.investor-portfolio');
        Route::get('/api/investment-statistics', [\App\Http\Controllers\Admin\InvestmentController::class, 'getInvestmentStatistics'])->name('api.investment-statistics');
    });
    
    // Location API Routes
    Route::get('/api/states', function(\Illuminate\Http\Request $request) {
        $countryId = $request->get('country_id');
        $states = \App\Models\State::where('country_id', $countryId)->orderBy('name')->get(['id', 'name']);
        return response()->json($states);
    })->name('api.states');
    
    Route::get('/api/cities', function(\Illuminate\Http\Request $request) {
        $stateId = $request->get('state_id');
        $cities = \App\Models\City::where('state_id', $stateId)->orderBy('name')->get(['id', 'name']);
        return response()->json($cities);
    })->name('api.cities');
    
    // Reports Routes (Available to Branch Managers)
    Route::get('/reports/pending-loans', [\App\Http\Controllers\Admin\ReportsController::class, 'pendingLoans'])->name('reports.pending-loans');
    Route::get('/reports/disbursed-loans', [\App\Http\Controllers\Admin\ReportsController::class, 'disbursedLoans'])->name('reports.disbursed-loans');
    Route::get('/reports/rejected-loans', [\App\Http\Controllers\Admin\ReportsController::class, 'rejectedLoans'])->name('reports.rejected-loans');
    Route::get('/reports/loans-due', [\App\Http\Controllers\Admin\ReportsController::class, 'loansDue'])->name('reports.loans-due');
    Route::get('/reports/paid-loans', [\App\Http\Controllers\Admin\ReportsController::class, 'paidLoans'])->name('reports.paid-loans');
    Route::get('/reports/loan-repayments', [\App\Http\Controllers\Admin\ReportsController::class, 'loanRepayments'])->name('reports.loan-repayments');
    Route::get('/reports/payment-transactions', [\App\Http\Controllers\Admin\ReportsController::class, 'paymentTransactions'])->name('reports.payment-transactions');
    Route::get('/reports/loan-interest', [\App\Http\Controllers\Admin\ReportsController::class, 'loanInterest'])->name('reports.loan-interest');
    Route::get('/reports/cash-securities', [\App\Http\Controllers\Admin\ReportsController::class, 'cashSecurities'])->name('reports.cash-securities');
    Route::get('/reports/loan-charges', [\App\Http\Controllers\Admin\ReportsController::class, 'loanCharges'])->name('reports.loan-charges');
});

// Super Admin Only Routes (School Management, Access Control, System Settings)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'super_admin'
])->prefix('admin')->name('admin.')->group(function () {
    // Access Control Dashboard
    Route::get('/access-control', [\App\Http\Controllers\Admin\AccessControlController::class, 'index'])->name('access-control.index');
    
    // School Management Routes
    Route::resource('schools', \App\Http\Controllers\Admin\SchoolsController::class);
    Route::post('/schools/{school}/approve', [\App\Http\Controllers\Admin\SchoolsController::class, 'approve'])->name('schools.approve');
    Route::post('/schools/{school}/reject', [\App\Http\Controllers\Admin\SchoolsController::class, 'reject'])->name('schools.reject');
    Route::post('/schools/{school}/suspend', [\App\Http\Controllers\Admin\SchoolsController::class, 'suspend'])->name('schools.suspend');
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\Admin\AccessControlController::class, 'users'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\Admin\AccessControlController::class, 'createUser'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\Admin\AccessControlController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\AccessControlController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\AccessControlController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\AccessControlController::class, 'deleteUser'])->name('users.delete');
    
    // Role Management
    Route::get('/roles', [\App\Http\Controllers\Admin\AccessControlController::class, 'roles'])->name('roles.index');
    Route::get('/roles/create', [\App\Http\Controllers\Admin\AccessControlController::class, 'createRole'])->name('roles.create');
    Route::post('/roles', [\App\Http\Controllers\Admin\AccessControlController::class, 'storeRole'])->name('roles.store');
    Route::get('/roles/{role}/edit', [\App\Http\Controllers\Admin\AccessControlController::class, 'editRole'])->name('roles.edit');
    Route::put('/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'updateRole'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\AccessControlController::class, 'deleteRole'])->name('roles.delete');
    
    // Permission Management
    Route::get('/permissions', [\App\Http\Controllers\Admin\AccessControlController::class, 'permissions'])->name('permissions.index');
    Route::get('/permissions/create', [\App\Http\Controllers\Admin\AccessControlController::class, 'createPermission'])->name('permissions.create');
    Route::post('/permissions', [\App\Http\Controllers\Admin\AccessControlController::class, 'storePermission'])->name('permissions.store');
    Route::delete('/permissions/{permission}', [\App\Http\Controllers\Admin\AccessControlController::class, 'deletePermission'])->name('permissions.delete');
    
    // System Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        // Settings Dashboard
        Route::get('/', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'dashboard'])->name('dashboard');
        
        // Organization Settings
        Route::get('/agencies', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'agencies'])->name('agencies');
        Route::get('/branches', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'branches'])->name('branches');
        Route::get('/company-info', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'companyInfo'])->name('company-info');
        
        // Product Settings
        Route::get('/loan-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'loanProducts'])->name('loan-products');
        Route::get('/savings-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'savingsProducts'])->name('savings-products');
        Route::get('/fees-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'feesProducts'])->name('fees-products');
        Route::get('/product-categories', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'productCategories'])->name('product-categories');
        
        // Account Settings
        Route::get('/system-accounts', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'systemAccounts'])->name('system-accounts');
        Route::get('/chart-accounts', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'chartAccounts'])->name('chart-accounts');
        Route::get('/account-types', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'accountTypes'])->name('account-types');
        
        // Security & Codes
        Route::get('/security-codes', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'securityCodes'])->name('security-codes');
        Route::get('/transaction-codes', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'transactionCodes'])->name('transaction-codes');
        Route::get('/audit-trail', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'auditTrail'])->name('audit-trail');
        
        // System Configuration
        Route::get('/general-config', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'generalConfig'])->name('general-config');
        Route::get('/email-config', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'emailConfig'])->name('email-config');
        Route::get('/sms-config', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'smsConfig'])->name('sms-config');
        Route::get('/notification-config', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'notificationConfig'])->name('notification-config');
        
        // Maintenance & Tools
        Route::get('/backup', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'backup'])->name('backup');
        Route::get('/database-maintenance', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'databaseMaintenance'])->name('database-maintenance');
        Route::get('/system-logs', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'systemLogs'])->name('system-logs');
        Route::get('/data-import', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'dataImport'])->name('data-import');
    });
});

// School Dashboard Routes (For approved schools)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'approved_school', // ✅ NEW: Ensures only approved schools can access
])->prefix('school')->name('school.')->group(function () {
    // Dashboard (approval check is in controller for better UX)
    Route::get('/dashboard', [\App\Http\Controllers\School\SchoolDashboardController::class, 'index'])->name('dashboard')->withoutMiddleware('approved_school');
    
    // Classes Management
    Route::resource('classes', \App\Http\Controllers\School\ClassesController::class);
    
    // Students Management
    Route::resource('students', \App\Http\Controllers\School\StudentsController::class);
    Route::post('/students/import', [\App\Http\Controllers\School\StudentsController::class, 'import'])->name('students.import');
    Route::get('/students/export/template', [\App\Http\Controllers\School\StudentsController::class, 'downloadTemplate'])->name('students.template');
    
    // Staff Management
    Route::resource('staff', \App\Http\Controllers\School\StaffController::class);
});


