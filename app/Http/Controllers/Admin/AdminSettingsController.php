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
        $products = Product::orderBy('name')->get();
        return view('admin.settings.loan-products', compact('products'));
    }

    public function savingsProducts()
    {
        $savingsProducts = SavingsProduct::orderBy('name')->get();
        return view('admin.settings.savings-products', compact('savingsProducts'));
    }

    public function feesProducts(Request $request)
    {
        $query = \App\Models\FeeType::with(['addedBy', 'systemAccount']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('systemAccount', function($sq) use ($search) {
                      $sq->where('code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Get paginated results
        $perPage = $request->get('per_page', 15);
        $feeTypes = $query->orderBy('name')->paginate($perPage)->appends($request->except('page'));

        $systemAccounts = \App\Models\SystemAccount::where('status', 1)->orderBy('code')->get();
        return view('admin.settings.fees-products', compact('feeTypes', 'systemAccounts'));
    }

    public function productCategories()
    {
        return view('admin.settings.product-categories');
    }

    /**
     * Account Settings
     */
    public function systemAccounts(Request $request)
    {
        $query = SystemAccount::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('accountType', 'like', "%{$search}%")
                  ->orWhere('accountSubType', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Currency filter
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        // Get paginated results
        $perPage = $request->get('per_page', 25);
        $systemAccounts = $query->orderBy('name')->paginate($perPage);

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