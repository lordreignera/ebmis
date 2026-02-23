

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SchoolRegistrationController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CronController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Cron endpoint for external scheduling (secured with token)
Route::get('/cron/run', [CronController::class, 'runScheduler'])->name('cron.run');

// Custom Logout Route (with success message)
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

// CSRF Token Refresh API (for keeping sessions alive)
Route::get('/api/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('web');

// School Registration Routes (Public)
Route::get('/school/register', [SchoolRegistrationController::class, 'show'])->name('school.register');
Route::post('/school/register', [SchoolRegistrationController::class, 'store'])->name('school.register.store');
Route::get('/school/assessment', [SchoolRegistrationController::class, 'showAssessment'])->name('school.assessment');
Route::post('/school/assessment', [SchoolRegistrationController::class, 'storeAssessment'])->name('school.assessment.store');

// Complete Assessment Routes (Public - for schools with incomplete assessments)
Route::get('/school/complete-assessment', [SchoolRegistrationController::class, 'showCompleteAssessment'])->name('school.complete-assessment');
Route::post('/school/complete-assessment', [SchoolRegistrationController::class, 'continueAssessment'])->name('school.continue-assessment');

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
    
    // Log Viewer Route
    Route::get('/logs', function() {
        return view('admin.logs.viewer');
    })->name('logs.viewer');
    
    Route::get('/logs/download', function() {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            return response()->download($logFile, 'laravel-' . date('Y-m-d') . '.log');
        }
        return redirect()->back()->with('error', 'Log file not found');
    })->name('logs.download');
    
    // Member Management Routes
    Route::get('/members/search', [\App\Http\Controllers\Admin\MemberController::class, 'search'])->name('members.search');
    Route::resource('members', \App\Http\Controllers\Admin\MemberController::class);
    Route::get('/members-pending', [\App\Http\Controllers\Admin\MemberController::class, 'pending'])->name('members.pending');
    Route::post('/members/{member}/approve', [\App\Http\Controllers\Admin\MemberController::class, 'approve'])->name('members.approve');
    Route::post('/members/{member}/reject', [\App\Http\Controllers\Admin\MemberController::class, 'reject'])->name('members.reject');
    Route::post('/members/{member}/suspend', [\App\Http\Controllers\Admin\MemberController::class, 'suspend'])->name('members.suspend');
    Route::get('/members/{member}/details', [\App\Http\Controllers\Admin\MemberController::class, 'getMemberDetails'])->name('members.details');
    Route::post('/members/check-duplicate', [\App\Http\Controllers\Admin\MemberController::class, 'checkDuplicate'])->name('members.check-duplicate');
    
    // Member Loan Assessment Routes (Business, Assets, Liabilities, Documents)
    Route::prefix('members/{member}')->name('members.')->group(function () {
        // Business routes
        Route::get('/businesses', [\App\Http\Controllers\Admin\MemberBusinessController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/create', [\App\Http\Controllers\Admin\MemberBusinessController::class, 'create'])->name('businesses.create');
        Route::post('/businesses', [\App\Http\Controllers\Admin\MemberBusinessController::class, 'store'])->name('businesses.store');
        Route::get('/businesses/{business}/edit', [\App\Http\Controllers\Admin\MemberBusinessController::class, 'edit'])->name('businesses.edit');
        Route::put('/businesses/{business}', [\App\Http\Controllers\Admin\MemberBusinessController::class, 'update'])->name('businesses.update');
        Route::delete('/businesses/{business}', [\App\Http\Controllers\Admin\MemberBusinessController::class, 'destroy'])->name('businesses.destroy');
        
        // Assets routes
        Route::post('/assets', [\App\Http\Controllers\Admin\MemberAssetController::class, 'store'])->name('assets.store');
        Route::put('/assets/{asset}', [\App\Http\Controllers\Admin\MemberAssetController::class, 'update'])->name('assets.update');
        Route::delete('/assets/{asset}', [\App\Http\Controllers\Admin\MemberAssetController::class, 'destroy'])->name('assets.destroy');
        
        // Liabilities routes
        Route::post('/liabilities', [\App\Http\Controllers\Admin\MemberLiabilityController::class, 'store'])->name('liabilities.store');
        Route::put('/liabilities/{liability}', [\App\Http\Controllers\Admin\MemberLiabilityController::class, 'update'])->name('liabilities.update');
        Route::delete('/liabilities/{liability}', [\App\Http\Controllers\Admin\MemberLiabilityController::class, 'destroy'])->name('liabilities.destroy');
        
        // Documents routes
        Route::post('/documents', [\App\Http\Controllers\Admin\MemberDocumentController::class, 'store'])->name('documents.store');
        Route::put('/documents/{document}', [\App\Http\Controllers\Admin\MemberDocumentController::class, 'update'])->name('documents.reupload');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\Admin\MemberDocumentController::class, 'download'])->name('documents.download');
        Route::delete('/documents/{document}', [\App\Http\Controllers\Admin\MemberDocumentController::class, 'destroy'])->name('documents.destroy');
    });
    
    // Cash Security Routes
    Route::prefix('cash-securities')->name('cash-securities.')->group(function () {
        Route::post('/store', [\App\Http\Controllers\Admin\CashSecurityController::class, 'store'])->name('store');
        Route::post('/return', [\App\Http\Controllers\Admin\CashSecurityController::class, 'returnCashSecurity'])->name('return');
        Route::get('/check-status/{transactionRef}', [\App\Http\Controllers\Admin\CashSecurityController::class, 'checkPaymentStatus'])->name('check-status');
        Route::get('/{cashSecurity}', [\App\Http\Controllers\Admin\CashSecurityController::class, 'show'])->name('show');
        Route::get('/{cashSecurity}/receipt', [\App\Http\Controllers\Admin\CashSecurityController::class, 'receipt'])->name('receipt');
        Route::delete('/{cashSecurity}', [\App\Http\Controllers\Admin\CashSecurityController::class, 'destroy'])->name('destroy');
    });
    
    // Tables Demo Route
    Route::get('/tables-demo', function () {
        return view('admin.tables-demo');
    })->name('tables.demo');
    
    // Modern Table Demo Route
    Route::get('/modern-table', function () {
        return view('admin.modern-table');
    })->name('modern.table');
    
    // School Loan Routes (Must be BEFORE regular loan routes to avoid conflicts)
    Route::prefix('school-loans')->name('school.loans.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'create'])->name('create');
        Route::post('/store', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'store'])->name('store');
        Route::get('/approvals', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'approvals'])->name('approvals');
        Route::get('/disbursements', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'disbursements'])->name('disbursements');
        Route::get('/active', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'active'])->name('active');
        Route::get('/repayments', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'repayments'])->name('repayments');
        Route::get('/portfolio', [\App\Http\Controllers\Admin\SchoolLoanController::class, 'portfolio'])->name('portfolio');
    });
    
    // Loan Additional Routes (Must be BEFORE resource route to avoid conflicts)
    Route::get('/loans/esign', [\App\Http\Controllers\Admin\LoanController::class, 'esignIndex'])->name('loans.esign');
    Route::get('/loans/approvals', [\App\Http\Controllers\Admin\LoanController::class, 'approvalsIndex'])->name('loans.approvals');
    Route::get('/loans/export', [\App\Http\Controllers\Admin\LoanController::class, 'export'])->name('loans.export');
    Route::get('/loans/active', [\App\Http\Controllers\Admin\RepaymentController::class, 'activeLoans'])->name('loans.active');
    Route::get('/loans/active/export', [\App\Http\Controllers\Admin\RepaymentController::class, 'exportActiveLoans'])->name('loans.active.export');
    Route::get('/loans/rejected', [\App\Http\Controllers\Admin\LoanController::class, 'rejectedLoans'])->name('loans.rejected');
    Route::get('/loans/rejected/export', [\App\Http\Controllers\Admin\LoanController::class, 'exportRejectedLoans'])->name('loans.rejected.export');
    
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
    Route::post('/loans/store-mobile-money', [\App\Http\Controllers\Admin\LoanController::class, 'storeLoanMobileMoneyPayment'])->name('loans.store-mobile-money');
    Route::post('/loans/{id}/upload-document', [\App\Http\Controllers\Admin\LoanController::class, 'uploadDocument'])->name('loans.upload-document');
    Route::post('/loans/{id}/delete-document', [\App\Http\Controllers\Admin\LoanController::class, 'deleteDocument'])->name('loans.delete-document');
    Route::post('/loans/{id}/revert', [\App\Http\Controllers\Admin\LoanController::class, 'revertLoan'])->name('loans.revert');
    Route::post('/loans/{id}/add-guarantor', [\App\Http\Controllers\Admin\LoanController::class, 'addGuarantor'])->name('loans.add-guarantor');
    Route::delete('/loans/guarantors/{guarantorId}', [\App\Http\Controllers\Admin\LoanController::class, 'removeGuarantor'])->name('loans.remove-guarantor');
    Route::get('/loans/check-mm-status/{reference}', [\App\Http\Controllers\Admin\LoanController::class, 'checkLoanMmStatus'])->name('loans.check-mm-status');
    Route::post('/loans/retry-mobile-money', [\App\Http\Controllers\Admin\LoanController::class, 'retryLoanMobileMoneyPayment'])->name('loans.retry-mobile-money');
    Route::put('/loans/{loan}/update-charge-type', [\App\Http\Controllers\Admin\LoanController::class, 'updateChargeType'])->name('loans.update-charge-type');
    Route::match(['get', 'post'], '/loans/{loan}/close-manual', [\App\Http\Controllers\Admin\LoanController::class, 'closeLoanManually'])->name('loans.close-manual');
    
    // Enhanced Loan Services Integration
    Route::post('/loans/calculate', [\App\Http\Controllers\Admin\LoanController::class, 'calculateLoan'])->name('loans.calculate');
    Route::post('/loans/generate-schedule-service', [\App\Http\Controllers\Admin\LoanController::class, 'generateScheduleWithService'])->name('loans.generate-schedule-service');
    Route::post('/loans/calculate-fees-service', [\App\Http\Controllers\Admin\LoanController::class, 'calculateFeesWithService'])->name('loans.calculate-fees-service');
    Route::post('/loans/check-eligibility-service', [\App\Http\Controllers\Admin\LoanController::class, 'checkEligibilityWithService'])->name('loans.check-eligibility-service');
    
    // Loan Agreement & Signing Routes
    Route::get('/loans/agreements', [\App\Http\Controllers\Admin\LoanController::class, 'agreements'])->name('loans.agreements');
    Route::post('/loans/send-otp', [\App\Http\Controllers\Admin\LoanController::class, 'sendOTP'])->name('loans.send-otp');
    Route::post('/loans/sign-agreement', [\App\Http\Controllers\Admin\LoanController::class, 'signAgreement'])->name('loans.sign-agreement');
    Route::get('/loans/view-agreement/{id}/{type}', [\App\Http\Controllers\Admin\LoanController::class, 'viewAgreement'])->name('loans.view-agreement');
    Route::get('/loans/download-signed-agreement/{id}/{type}', [\App\Http\Controllers\Admin\LoanController::class, 'downloadSignedAgreement'])->name('loans.download-signed-agreement');
    Route::post('/loans/save-esignature/{id}', [\App\Http\Controllers\Admin\LoanController::class, 'saveESignature'])->name('loans.save-esignature');
    Route::post('/loans/regenerate-agreement/{id}', [\App\Http\Controllers\Admin\LoanController::class, 'regenerateAgreement'])->name('loans.regenerate-agreement');
    Route::post('/loans/save-guarantor-signature/{id}', [\App\Http\Controllers\Admin\LoanController::class, 'saveGuarantorSignature'])->name('loans.save-guarantor-signature');
    
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
    Route::get('/disbursements/approve/{id}', [\App\Http\Controllers\Admin\DisbursementController::class, 'showApprove'])->name('disbursements.approve.show');
    Route::post('/disbursements/approve/{id}', [\App\Http\Controllers\Admin\DisbursementController::class, 'approve'])->name('disbursements.approve');
    
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
    
    // Late Fees Management
    Route::get('/late-fees', [\App\Http\Controllers\Admin\LateFeeController::class, 'index'])->name('late-fees.index');
    Route::post('/late-fees/{lateFee}/waive', [\App\Http\Controllers\Admin\LateFeeController::class, 'waive'])->name('late-fees.waive');
    Route::post('/late-fees/bulk-waive', [\App\Http\Controllers\Admin\LateFeeController::class, 'bulkWaive'])->name('late-fees.bulk-waive');
    Route::get('/late-fees/waive-upgrade', [\App\Http\Controllers\Admin\LateFeeController::class, 'showWaiveUpgrade'])->name('late-fees.show-waive-upgrade');
    Route::post('/late-fees/waive-upgrade-period', [\App\Http\Controllers\Admin\LateFeeController::class, 'waiveUpgradePeriod'])->name('late-fees.waive-upgrade-period');
    
    // NEW: Enhanced Repayment Routes for UI
    Route::prefix('loans/repayments')->name('loans.repayments.')->group(function () {
        Route::get('/schedules/{id}', [\App\Http\Controllers\Admin\RepaymentController::class, 'schedules'])->name('schedules');
        Route::post('/quick', [\App\Http\Controllers\Admin\RepaymentController::class, 'quickRepayment'])->name('quick');
        Route::post('/store', [\App\Http\Controllers\Admin\RepaymentController::class, 'storeRepayment'])->name('store');

        Route::post('/pay-balance', [\App\Http\Controllers\Admin\RepaymentController::class, 'payBalance'])->name('pay-balance');
        Route::post('/store-mobile-money', [\App\Http\Controllers\Admin\RepaymentController::class, 'storeMobileMoneyRepayment'])->name('store-mobile-money');
        Route::get('/check-mm-status/{reference}', [\App\Http\Controllers\Admin\RepaymentController::class, 'checkRepaymentMmStatus'])->name('check-mm-status');
        Route::post('/retry-mobile-money', [\App\Http\Controllers\Admin\RepaymentController::class, 'retryMobileMoneyRepayment'])->name('retry-mobile-money');
        Route::get('/get/{id}', [\App\Http\Controllers\Admin\RepaymentController::class, 'getRepayment'])->name('get');
        Route::get('/schedule-pending/{scheduleId}', [\App\Http\Controllers\Admin\RepaymentController::class, 'getSchedulePendingRepayments'])->name('schedule-pending');
    });
    
    // Get all payments for a schedule
    Route::get('/loans/schedules/{id}/payments', [\App\Http\Controllers\Admin\RepaymentController::class, 'getSchedulePayments'])->name('loans.schedules.payments');
    
    // Late Fees Management Routes (Superadmin & Administrator only)
    Route::post('/loans/late-fees/waive', [\App\Http\Controllers\Admin\RepaymentController::class, 'waiveLateFees'])->name('loans.late-fees.waive');
    
    // Carry Over Excess Payment Route
    Route::post('/loans/carry-over', [\App\Http\Controllers\Admin\RepaymentController::class, 'carryOverExcess'])->name('loans.carry-over');
    
    // Loan Reschedule Route
    Route::post('/loans/{loan}/reschedule', [\App\Http\Controllers\Admin\RepaymentController::class, 'rescheduleLoan'])->name('loans.reschedule');
    
    // Stop Loan Route (for duplicate/mistaken loans)
    Route::post('/loans/{loan}/stop', [\App\Http\Controllers\Admin\RepaymentController::class, 'stopLoan'])->name('loans.stop');
    
    // Mobile money payment status check (for 60-second polling)
    Route::get('/check-payment-status/{transactionId}', [\App\Http\Controllers\Admin\RepaymentController::class, 'checkPaymentStatus'])->name('check-payment-status');
    Route::get('/loans/repayments/get-pending-transaction/{scheduleId}', [\App\Http\Controllers\Admin\RepaymentController::class, 'getPendingTransaction'])->name('loans.repayments.get-pending-transaction');
    
    // Loan next schedule route
    Route::get('/loans/{id}/next-schedule', [\App\Http\Controllers\Admin\RepaymentController::class, 'getNextSchedule'])->name('loans.next-schedule');
    
    // NEW: Loan History and Statements Routes
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/{id}/history', [\App\Http\Controllers\Admin\LoanController::class, 'history'])->name('history');
        Route::get('/{id}/restructure', [\App\Http\Controllers\Admin\LoanController::class, 'restructure'])->name('restructure');
        Route::post('/{id}/restructure', [\App\Http\Controllers\Admin\LoanController::class, 'restructureStore'])->name('restructure.store');
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
    Route::post('/fees/store-mobile-money', [\App\Http\Controllers\Admin\FeeController::class, 'storeMobileMoneyPayment'])->name('fees.store-mobile-money');
    Route::get('/fees/check-mm-status/{transactionRef}', [\App\Http\Controllers\Admin\FeeController::class, 'checkMobileMoneyStatus'])->name('fees.check-mm-status');
    Route::post('/fees/retry-mobile-money', [\App\Http\Controllers\Admin\FeeController::class, 'retryMobileMoneyPayment'])->name('fees.retry-mobile-money');
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
    Route::post('/savings/check-payment-status', [\App\Http\Controllers\Admin\SavingController::class, 'checkPaymentStatus'])->name('savings.check-payment-status');
    
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
        Route::post('/investors/{investor}/delete', [\App\Http\Controllers\Admin\InvestmentController::class, 'deleteInvestor'])->name('delete-investor');
        
        // Investment Management
        Route::get('/{investor}/create', [\App\Http\Controllers\Admin\InvestmentController::class, 'createInvestment'])->name('create-investment');
        Route::post('/{investor}', [\App\Http\Controllers\Admin\InvestmentController::class, 'storeInvestment'])->name('store-investment');
        Route::get('/{investment}/show', [\App\Http\Controllers\Admin\InvestmentController::class, 'showInvestment'])->name('show-investment');
        Route::get('/{investment}/edit', [\App\Http\Controllers\Admin\InvestmentController::class, 'editInvestment'])->name('edit-investment');
        Route::put('/{investment}', [\App\Http\Controllers\Admin\InvestmentController::class, 'updateInvestment'])->name('update-investment');
        Route::delete('/{investment}', [\App\Http\Controllers\Admin\InvestmentController::class, 'destroyInvestment'])->name('destroy');
        
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
    Route::get('/schools/dashboard', function () {
        return redirect()->route('admin.schools.index');
    })->name('schools.dashboard');
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
        
        // Agency CRUD Routes (specific routes must come before general routes)
        Route::post('/agencies', [\App\Http\Controllers\Admin\AgencyController::class, 'store'])->name('agencies.store');
        Route::get('/agencies/{id}', [\App\Http\Controllers\Admin\AgencyController::class, 'show'])->name('agencies.show')->where('id', '[0-9]+');
        Route::put('/agencies/{id}', [\App\Http\Controllers\Admin\AgencyController::class, 'update'])->name('agencies.update')->where('id', '[0-9]+');
        Route::delete('/agencies/{id}', [\App\Http\Controllers\Admin\AgencyController::class, 'destroy'])->name('agencies.destroy')->where('id', '[0-9]+');
        
        // Branch CRUD Routes (specific routes must come before general routes)
        Route::post('/branches', [\App\Http\Controllers\Admin\BranchCrudController::class, 'store'])->name('branches.store');
        Route::get('/branches/{id}', [\App\Http\Controllers\Admin\BranchCrudController::class, 'show'])->name('branches.show')->where('id', '[0-9]+');
        Route::put('/branches/{id}', [\App\Http\Controllers\Admin\BranchCrudController::class, 'update'])->name('branches.update')->where('id', '[0-9]+');
        Route::delete('/branches/{id}', [\App\Http\Controllers\Admin\BranchCrudController::class, 'destroy'])->name('branches.destroy')->where('id', '[0-9]+');
        
        // Organization Settings - View Routes
        Route::get('/agencies', [\App\Http\Controllers\Admin\AgencyController::class, 'index'])->name('agencies');
        Route::get('/branches', [\App\Http\Controllers\Admin\BranchCrudController::class, 'index'])->name('branches');
        Route::get('/field-users', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'fieldUsers'])->name('field-users');
        Route::get('/field-users/create', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'createFieldUser'])->name('field-users.create');
        Route::post('/field-users/store', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'storeFieldUser'])->name('field-users.store');
        Route::get('/field-users/{id}', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'showFieldUser'])->name('field-users.show');
        Route::get('/field-users/{id}/edit', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'editFieldUser'])->name('field-users.edit');
        Route::put('/field-users/{id}', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateFieldUser'])->name('field-users.update');
        Route::delete('/field-users/{id}', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'deleteFieldUser'])->name('field-users.delete');
        Route::get('/company-info', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'companyInfo'])->name('company-info');
        
        // Product Settings
        Route::get('/loan-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'loanProducts'])->name('loan-products');
        Route::get('/school-loan-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'schoolLoanProducts'])->name('school-loan-products');
        Route::get('/savings-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'savingsProducts'])->name('savings-products');
        Route::get('/fees-products', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'feesProducts'])->name('fees-products');
        Route::get('/product-categories', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'productCategories'])->name('product-categories');
    });
    
    // Product CRUD Routes (outside settings prefix)
    Route::resource('products', \App\Http\Controllers\Admin\LoanProductController::class)->except(['index'])->names([
        'create' => 'loan-products.create',
        'store' => 'loan-products.store',
        'show' => 'loan-products.show',
        'edit' => 'loan-products.edit',
        'update' => 'loan-products.update',
        'destroy' => 'loan-products.destroy',
    ]);
    
    // Savings Product CRUD Routes
    Route::resource('savings-products', \App\Http\Controllers\Admin\SavingsProductController::class)->except(['index']);
    
    // Product Status Toggle
    Route::post('/products/{product}/toggle-status', [\App\Http\Controllers\Admin\LoanProductController::class, 'toggleStatus'])->name('loan-products.toggle-status');
    Route::post('/savings-products/{savingsProduct}/toggle-status', [\App\Http\Controllers\Admin\SavingsProductController::class, 'toggleStatus'])->name('savings-products.toggle-status');
    
    // Product Charges CRUD
    Route::resource('product-charges', \App\Http\Controllers\Admin\ProductChargeController::class)->only(['store', 'update', 'destroy']);
    
    // Continue with other settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        
        // System Accounts CRUD - using specific route names to avoid conflicts
        Route::get('/system-accounts/create', [\App\Http\Controllers\Admin\SystemAccountController::class, 'create'])->name('system-accounts.create');
        Route::get('/system-accounts/{system_account}/edit', [\App\Http\Controllers\Admin\SystemAccountController::class, 'edit'])->where('system_account', '[0-9]+')->name('system-accounts.edit');
        Route::post('/system-accounts', [\App\Http\Controllers\Admin\SystemAccountController::class, 'store'])->name('system-accounts.store');
        Route::get('/system-accounts/suggest-sub-code', [\App\Http\Controllers\Admin\SystemAccountController::class, 'getSuggestedSubCode'])->name('system-accounts.suggest-sub-code');
        Route::get('/system-accounts/view', [\App\Http\Controllers\Admin\SystemAccountController::class, 'show'])->name('system-accounts.show');
        Route::post('/system-accounts/update/{system_account}', [\App\Http\Controllers\Admin\SystemAccountController::class, 'update'])->where('system_account', '[0-9]+')->name('system-accounts.update');
        Route::post('/system-accounts/delete/{system_account}', [\App\Http\Controllers\Admin\SystemAccountController::class, 'destroy'])->where('system_account', '[0-9]+')->name('system-accounts.destroy');
        
        // Fee Types CRUD
        Route::post('/fees-products', [\App\Http\Controllers\Admin\FeeTypeController::class, 'store'])->name('fees-products.store');
        Route::get('/fees-products/view', [\App\Http\Controllers\Admin\FeeTypeController::class, 'show'])->name('fees-products.show');
        Route::post('/fees-products/update/{fee_type}', [\App\Http\Controllers\Admin\FeeTypeController::class, 'update'])->where('fee_type', '[0-9]+')->name('fees-products.update');
        Route::post('/fees-products/delete/{fee_type}', [\App\Http\Controllers\Admin\FeeTypeController::class, 'destroy'])->where('fee_type', '[0-9]+')->name('fees-products.destroy');
    });
    
    // Fees Management Routes (outside settings prefix, but still in main admin group)
    Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'ebims_module'])->group(function () {
        // Check Current User (Diagnostic)
        Route::get('/check-user', function () {
            return view('admin.check-current-user');
        })->name('check-user');
        
        Route::resource('fees', \App\Http\Controllers\Admin\FeeController::class);
        Route::get('/fees/member/{member}/status', [\App\Http\Controllers\Admin\FeeController::class, 'getMemberFeeStatus'])->name('fees.member.status');
        Route::get('/fees/loan/{loan}/charges', [\App\Http\Controllers\Admin\FeeController::class, 'getLoanChargeStatus'])->name('fees.loan.charges');
        Route::post('/fees/{fee}/mark-paid', [\App\Http\Controllers\Admin\FeeController::class, 'markAsPaid'])->name('fees.mark-paid');
        Route::get('/fees/{fee}/receipt', [\App\Http\Controllers\Admin\FeeController::class, 'receipt'])->name('fees.receipt');
        Route::get('/fees/{fee}/receipt-modal', [\App\Http\Controllers\Admin\FeeController::class, 'getReceiptModal'])->name('fees.receipt-modal');
        
        Route::post('/fees/mobile-money', [\App\Http\Controllers\Admin\FeeController::class, 'storeMobileMoneyPayment'])->name('fees.mobile-money');
        Route::get('/fees/mobile-money/status/{transactionRef}', [\App\Http\Controllers\Admin\FeeController::class, 'checkMobileMoneyStatus'])->name('fees.mobile-money.status');
        Route::post('/fees/mobile-money/retry', [\App\Http\Controllers\Admin\FeeController::class, 'retryMobileMoneyPayment'])->name('fees.mobile-money.retry');
        Route::get('/fees/types/ajax', [\App\Http\Controllers\Admin\FeeController::class, 'getFeeTypes'])->name('fees.types.ajax');
        Route::get('/fees/members/search', [\App\Http\Controllers\Admin\FeeController::class, 'getMembers'])->name('fees.members.search');
        
        // Accounting & GL Routes
        Route::get('/accounting/journal-entries', [\App\Http\Controllers\Admin\AccountingController::class, 'journalEntries'])->name('accounting.journal-entries');
        Route::get('/accounting/journal-entries/download', [\App\Http\Controllers\Admin\AccountingController::class, 'downloadJournalEntries'])->name('accounting.journal-entries.download');
        Route::get('/accounting/journal-entries/{entry}', [\App\Http\Controllers\Admin\AccountingController::class, 'showJournalEntry'])->name('accounting.journal-entry');
        Route::get('/accounting/trial-balance', [\App\Http\Controllers\Admin\AccountingController::class, 'trialBalance'])->name('accounting.trial-balance');
        Route::get('/accounting/trial-balance/download', [\App\Http\Controllers\Admin\AccountingController::class, 'downloadTrialBalance'])->name('accounting.trial-balance.download');
        Route::get('/accounting/balance-sheet', [\App\Http\Controllers\Admin\AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
        Route::get('/accounting/balance-sheet/download', [\App\Http\Controllers\Admin\AccountingController::class, 'downloadBalanceSheet'])->name('accounting.balance-sheet.download');
        Route::get('/accounting/income-statement', [\App\Http\Controllers\Admin\AccountingController::class, 'incomeStatement'])->name('accounting.income-statement');
        Route::get('/accounting/income-statement/download', [\App\Http\Controllers\Admin\AccountingController::class, 'downloadIncomeStatement'])->name('accounting.income-statement.download');
        Route::get('/accounting/chart-of-accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'chartOfAccounts'])->name('accounting.chart-of-accounts');
        Route::get('/accounting/chart-of-accounts/download', [\App\Http\Controllers\Admin\AccountingController::class, 'downloadChartOfAccounts'])->name('accounting.chart-of-accounts.download');
    });
    
    Route::prefix('settings')->name('settings.')->group(function () {
        // Account Settings - List route (no parameter)
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


