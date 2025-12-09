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
    public function loanProducts(Request $request)
    {
        // Build query
        $query = Product::query();
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Apply loan type filter
        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }
        
        // Apply period type filter
        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            $query->where('isactive', $request->status);
        }
        
        // Get paginated results
        $products = $query->orderBy('datecreated', 'desc')->paginate(15)->withQueryString();
        
        return view('admin.settings.loan-products', compact('products'));
    }

    public function schoolLoanProducts(Request $request)
    {
        // Build query - only show school (4), student (5), and staff (6) loan products
        $query = Product::whereIn('loan_type', [4, 5, 6]);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Apply loan type filter
        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->loan_type);
        }
        
        // Apply period type filter
        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            $query->where('isactive', $request->status);
        }
        
        // Get paginated results
        $products = $query->orderBy('loan_type')
                          ->orderBy('period_type')
                          ->orderBy('name')
                          ->paginate(15)
                          ->withQueryString();
                          
        return view('admin.settings.school-loan-products', compact('products'));
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

    /**
     * Field Users Management
     */
    public function fieldUsers()
    {
        $fieldUsers = \App\Models\Member::where('member_type', 4)
            ->with(['branch', 'country'])
            ->orderBy('datecreated', 'desc')
            ->get();
        
        return view('admin.settings.field-users', compact('fieldUsers'));
    }

    public function createFieldUser()
    {
        $branches = \App\Models\Branch::active()->orderBy('name')->get();
        $countries = \App\Models\Country::orderBy('name')->get();
        
        return view('admin.settings.field-users-create', compact('branches', 'countries'));
    }

    public function storeFieldUser(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'required|string|max:80',
            'lname' => 'required|string|max:80',
            'mname' => 'nullable|string|max:200',
            'nin' => 'required|string|max:80',
            'gender' => 'required|in:m,f',
            'dob' => 'nullable|string|max:200',
            'contact' => 'required|string|max:80',
            'alt_contact' => 'nullable|string|max:200',
            'fixed_line' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'mobile_pin' => 'required|string|min:4|max:10',
            'branch_id' => 'required|exists:branches,id',
            'country_id' => 'required|exists:countries,id',
            'plot_no' => 'nullable|string|max:200',
            'village' => 'required|string|max:200',
            'parish' => 'required|string|max:200',
            'subcounty' => 'required|string|max:200',
            'county' => 'required|string|max:200',
            'pplot_no' => 'nullable|string|max:200',
            'pvillage' => 'required|string|max:200',
            'pparish' => 'required|string|max:200',
            'psubcounty' => 'required|string|max:200',
            'pcounty' => 'required|string|max:200',
            'pcountry_id' => 'required|exists:countries,id',
            'pp_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'id_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            // Generate unique code
            $code = 'FM' . time();

            // Handle file uploads - using permanent public storage
            $ppFile = null;
            $idFile = null;

            if ($request->hasFile('pp_file')) {
                $file = $request->file('pp_file');
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $uploadPath = public_path('uploads/field-users/photos');
                if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
                $file->move($uploadPath, $filename);
                $ppFile = 'uploads/field-users/photos/' . $filename;
            }

            if ($request->hasFile('id_file')) {
                $file = $request->file('id_file');
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $uploadPath = public_path('uploads/field-users/ids');
                if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
                $file->move($uploadPath, $filename);
                $idFile = 'uploads/field-users/ids/' . $filename;
            }

            // Create field user (member with type 4)
            $fieldUser = \App\Models\Member::create([
                'code' => $code,
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'mname' => $validated['mname'] ?? null,
                'nin' => $validated['nin'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'] ?? null,
                'contact' => $validated['contact'],
                'alt_contact' => $validated['alt_contact'] ?? null,
                'fixed_line' => $validated['fixed_line'] ?? null,
                'email' => $validated['email'] ?? null,
                'mobile_pin' => $validated['mobile_pin'], // Store as plain text (4-10 digit PIN)
                'branch_id' => $validated['branch_id'],
                'country_id' => $validated['country_id'],
                'plot_no' => $validated['plot_no'] ?? null,
                'village' => $validated['village'],
                'parish' => $validated['parish'],
                'subcounty' => $validated['subcounty'],
                'county' => $validated['county'],
                'pp_file' => $ppFile,
                'id_file' => $idFile,
                'member_type' => 4, // Field User type
                'verified' => 1, // Auto verify field users
                'status' => 'approved', // Auto approve field users
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'added_by' => Auth::id(),
            ]);

            // Log activity
            \Log::info('Created field user', [
                'field_user_id' => $fieldUser->id,
                'code' => $fieldUser->code,
                'name' => $fieldUser->fname . ' ' . $fieldUser->lname,
                'created_by' => Auth::id()
            ]);

            return redirect()->route('admin.settings.field-users')
                ->with('success', 'Field user added successfully!');

        } catch (\Exception $e) {
            \Log::error('Field user creation failed: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to add field user. Please try again.');
        }
    }

    public function showFieldUser($id)
    {
        $fieldUser = \App\Models\Member::where('member_type', 4)
            ->with(['branch', 'country', 'addedBy'])
            ->findOrFail($id);
        
        return view('admin.settings.field-users-show', compact('fieldUser'));
    }

    public function editFieldUser($id)
    {
        $fieldUser = \App\Models\Member::where('member_type', 4)->findOrFail($id);
        $branches = \App\Models\Branch::active()->orderBy('name')->get();
        $countries = \App\Models\Country::orderBy('name')->get();
        
        return view('admin.settings.field-users-edit', compact('fieldUser', 'branches', 'countries'));
    }

    public function updateFieldUser(Request $request, $id)
    {
        $fieldUser = \App\Models\Member::where('member_type', 4)->findOrFail($id);

        $validated = $request->validate([
            'fname' => 'required|string|max:80',
            'lname' => 'required|string|max:80',
            'mname' => 'nullable|string|max:200',
            'nin' => 'required|string|max:80',
            'gender' => 'required|in:m,f',
            'dob' => 'nullable|string|max:200',
            'contact' => 'required|string|max:80',
            'alt_contact' => 'nullable|string|max:200',
            'fixed_line' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'mobile_pin' => 'nullable|string|min:4|max:10',
            'branch_id' => 'required|exists:branches,id',
            'country_id' => 'required|exists:countries,id',
            'plot_no' => 'nullable|string|max:200',
            'village' => 'required|string|max:200',
            'parish' => 'required|string|max:200',
            'subcounty' => 'required|string|max:200',
            'county' => 'required|string|max:200',
            'pp_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'id_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            // Handle file uploads - using permanent public storage
            if ($request->hasFile('pp_file')) {
                // Delete old file if exists
                if ($fieldUser->pp_file && file_exists(public_path($fieldUser->pp_file))) {
                    unlink(public_path($fieldUser->pp_file));
                }
                $file = $request->file('pp_file');
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $uploadPath = public_path('uploads/field-users/photos');
                if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
                $file->move($uploadPath, $filename);
                $validated['pp_file'] = 'uploads/field-users/photos/' . $filename;
            }

            if ($request->hasFile('id_file')) {
                // Delete old file if exists
                if ($fieldUser->id_file && file_exists(public_path($fieldUser->id_file))) {
                    unlink(public_path($fieldUser->id_file));
                }
                $file = $request->file('id_file');
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $uploadPath = public_path('uploads/field-users/ids');
                if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
                $file->move($uploadPath, $filename);
                $validated['id_file'] = 'uploads/field-users/ids/' . $filename;
            }

            // Update field user
            $updateData = [
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'mname' => $validated['mname'] ?? null,
                'nin' => $validated['nin'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'] ?? null,
                'contact' => $validated['contact'],
                'alt_contact' => $validated['alt_contact'] ?? null,
                'fixed_line' => $validated['fixed_line'] ?? null,
                'email' => $validated['email'] ?? null,
                'branch_id' => $validated['branch_id'],
                'country_id' => $validated['country_id'],
                'plot_no' => $validated['plot_no'] ?? null,
                'village' => $validated['village'],
                'parish' => $validated['parish'],
                'subcounty' => $validated['subcounty'],
                'county' => $validated['county'],
            ];

            // Only update pin if provided
            if (!empty($validated['mobile_pin'])) {
                $updateData['mobile_pin'] = $validated['mobile_pin'];
            }

            // Add file paths if uploaded
            if (isset($validated['pp_file'])) {
                $updateData['pp_file'] = $validated['pp_file'];
            }
            if (isset($validated['id_file'])) {
                $updateData['id_file'] = $validated['id_file'];
            }

            $fieldUser->update($updateData);

            // Log activity
            \Log::info('Updated field user', [
                'field_user_id' => $fieldUser->id,
                'code' => $fieldUser->code,
                'name' => $fieldUser->fname . ' ' . $fieldUser->lname,
                'updated_by' => Auth::id()
            ]);

            return redirect()->route('admin.settings.field-users')
                ->with('success', 'Field user updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Field user update failed: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update field user. Please try again.');
        }
    }

    public function deleteFieldUser($id)
    {
        try {
            $fieldUser = \App\Models\Member::where('member_type', 4)->findOrFail($id);
            
            $name = $fieldUser->fname . ' ' . $fieldUser->lname;
            
            // Soft delete by setting soft_delete = 1
            $fieldUser->update([
                'soft_delete' => 1,
                'del_user' => Auth::id(),
                'del_comments' => 'Deleted from field users management'
            ]);

            // Log activity
            \Log::info('Deleted field user', [
                'field_user_id' => $fieldUser->id,
                'code' => $fieldUser->code,
                'name' => $name,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Field user deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Field user deletion failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete field user.'
            ], 500);
        }
    }
}