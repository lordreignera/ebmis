<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\Product;
use App\Models\SavingsProduct;
use App\Models\SystemAccount;

class AdminSettingsController extends Controller
{
    /**
     * Display the settings dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_agencies' => Agency::count(),
            'active_agencies' => Agency::active()->count(),
            'total_branches' => Branch::count(),
            'active_branches' => Branch::active()->count(),
            'loan_products' => Product::count(),
            'savings_products' => SavingsProduct::count(),
            'system_accounts' => SystemAccount::count(),
        ];

        return view('admin.settings.dashboard', compact('stats'));
    }

    /**
     * Organization Settings
     */
    public function agencies()
    {
        $agencies = Agency::with('addedBy')->orderBy('name')->get();
        return view('admin.settings.agencies', compact('agencies'));
    }

    public function branches()
    {
        $branches = Branch::with(['country'])->orderBy('name')->get();
        return view('admin.settings.branches', compact('branches'));
    }

    public function companyInfo()
    {
        return view('admin.settings.company-info');
    }

    /**
     * Product Settings
     */
    public function loanProducts()
    {
        $products = Product::orderBy('product_name')->get();
        return view('admin.settings.loan-products', compact('products'));
    }

    public function savingsProducts()
    {
        $savingsProducts = SavingsProduct::orderBy('product_name')->get();
        return view('admin.settings.savings-products', compact('savingsProducts'));
    }

    public function feesProducts()
    {
        return view('admin.settings.fees-products');
    }

    public function productCategories()
    {
        return view('admin.settings.product-categories');
    }

    /**
     * Account Settings
     */
    public function systemAccounts()
    {
        $systemAccounts = SystemAccount::orderBy('account_name')->get();
        return view('admin.settings.system-accounts', compact('systemAccounts'));
    }

    public function chartAccounts()
    {
        return view('admin.settings.chart-accounts');
    }

    public function accountTypes()
    {
        return view('admin.settings.account-types');
    }

    /**
     * Security & Codes
     */
    public function securityCodes()
    {
        return view('admin.settings.security-codes');
    }

    public function transactionCodes()
    {
        return view('admin.settings.transaction-codes');
    }

    public function auditTrail()
    {
        return view('admin.settings.audit-trail');
    }

    /**
     * System Configuration
     */
    public function generalConfig()
    {
        return view('admin.settings.general-config');
    }

    public function emailConfig()
    {
        return view('admin.settings.email-config');
    }

    public function smsConfig()
    {
        return view('admin.settings.sms-config');
    }

    public function notificationConfig()
    {
        return view('admin.settings.notification-config');
    }

    /**
     * Maintenance & Tools
     */
    public function backup()
    {
        return view('admin.settings.backup');
    }

    public function databaseMaintenance()
    {
        return view('admin.settings.database-maintenance');
    }

    public function systemLogs()
    {
        return view('admin.settings.system-logs');
    }

    public function dataImport()
    {
        return view('admin.settings.data-import');
    }
}