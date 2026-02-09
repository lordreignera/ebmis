<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemAccountController extends Controller
{
    /**
     * Show the create form for a new system account
     */
    public function create()
    {
        $parentAccounts = SystemAccount::whereNull('sub_code')
            ->where('status', 1)
            ->orderBy('code')
            ->get();
            
        return view('admin.settings.system-accounts-create', compact('parentAccounts'));
    }

    /**
     * Show the edit form for an existing system account
     */
    public function edit($id)
    {
        $account = SystemAccount::findOrFail($id);
        
        $parentAccounts = SystemAccount::whereNull('sub_code')
            ->where('status', 1)
            ->where('Id', '!=', $id) // Exclude current account
            ->orderBy('code')
            ->get();
            
        return view('admin.settings.system-accounts-edit', compact('account', 'parentAccounts'));
    }

    /**
     * Get suggested sub code for a parent account
     * Used when creating child accounts via AJAX
     */
    public function getSuggestedSubCode(Request $request)
    {
        $parentId = $request->query('parent_id');
        
        if (!$parentId) {
            return response()->json([
                'success' => false,
                'message' => 'Parent account ID is required'
            ], 400);
        }

        try {
            $suggestedSubCode = SystemAccount::getSuggestedSubCode($parentId);
            
            if (!$suggestedSubCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent account not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'sub_code' => $suggestedSubCode
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating sub code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created system account
     */
    public function store(Request $request)
    {
        // Build conditional validation rules: parents require `code` unique; children require `sub_code` unique under parent
        $baseRules = [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:Asset,Liability,Equity,Income,Expense',
            'accountType' => 'nullable|string|max:100',
            'accountSubType' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'parent_account' => 'nullable|integer|exists:system_accounts,Id',
            'status' => 'required|integer|in:0,1',
        ];

        $rules = $baseRules;

        if ($request->filled('parent_account')) {
            // sub_code may be provided, but if omitted the model will auto-generate it
            $rules['sub_code'] = 'nullable|string|max:50';
            $rules['code'] = 'nullable|string|max:50';
        } else {
            $rules['code'] = 'required|string|max:50|unique:system_accounts,code';
            $rules['sub_code'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validate();

        try {
            // Convert empty parent_account to null
            if (empty($validated['parent_account'])) {
                $validated['parent_account'] = null;
            }

            // If creating a child account, attach parent's code and ensure sub_code uniqueness under that parent code
            if (!empty($validated['parent_account'])) {
                $parent = SystemAccount::find($validated['parent_account']);
                if (!$parent) {
                    return response()->json(['success' => false, 'message' => 'Parent account not found'], 404);
                }

                // If sub_code provided ensure no other account has same (code, sub_code)
                if (!empty($validated['sub_code'])) {
                    if (SystemAccount::where('code', $parent->code)->where('sub_code', $validated['sub_code'])->exists()) {
                        return response()->json(['success' => false, 'message' => 'Sub code already exists for this parent'], 422);
                    }
                }

                $validated['code'] = $parent->code;
            }

            $validated['added_by'] = auth()->id();
            $validated['running_balance'] = 0;

            $account = SystemAccount::create($validated);

            // Return JSON if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'System account created successfully.',
                    'account' => $account
                ]);
            }

            return redirect()->back()->with('success', 'System account created successfully.');

        } catch (\Exception $e) {
            // Return JSON error if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating system account: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating system account: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified system account
     */
    public function show(Request $request)
    {
        try {
            $id = $request->query('id');
            
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account ID is required'
                ], 400);
            }
            
            $account = SystemAccount::with('parent')->findOrFail($id);
            
            // Always return JSON for this endpoint as it's only used via AJAX
            return response()->json([
                'success' => true,
                'account' => $account
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified system account
     */
    public function update(Request $request, $system_account)
    {
        $id = $system_account;
        $account = SystemAccount::findOrFail($id);
        // Conditional validation similar to store
        $baseRules = [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:Asset,Liability,Equity,Income,Expense',
            'accountType' => 'nullable|string|max:100',
            'accountSubType' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'parent_account' => 'nullable|integer|exists:system_accounts,Id',
            'status' => 'required|integer|in:0,1',
        ];

        $rules = $baseRules;

        if ($request->filled('parent_account')) {
            // sub_code may be provided, but if omitted the model will auto-generate it
            $rules['sub_code'] = 'nullable|string|max:50';
            $rules['code'] = 'nullable|string|max:50';
        } else {
            $rules['code'] = 'required|string|max:50|unique:system_accounts,code,' . $id . ',Id';
            $rules['sub_code'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validate();

        try {
            if (empty($validated['parent_account'])) {
                $validated['parent_account'] = null;
            }

            // If updating to be a child, ensure uniqueness of sub_code under parent code
            if (!empty($validated['parent_account'])) {
                $parent = SystemAccount::find($validated['parent_account']);
                if (!$parent) {
                    return response()->json(['success' => false, 'message' => 'Parent account not found'], 404);
                }

                // If sub_code provided, check for existing other record with same (code, sub_code)
                if (!empty($validated['sub_code'])) {
                    $exists = SystemAccount::where('code', $parent->code)
                                ->where('sub_code', $validated['sub_code'])
                                ->where('Id', '!=', $id)
                                ->exists();

                    if ($exists) {
                        return response()->json(['success' => false, 'message' => 'Sub code already exists for this parent'], 422);
                    }
                }

                $validated['code'] = $parent->code;
            }

            $account->update($validated);

            // Return JSON if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'System account updated successfully.',
                    'account' => $account
                ]);
            }

            return redirect()->back()->with('success', 'System account updated successfully.');

        } catch (\Exception $e) {
            // Return JSON error if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating system account: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating system account: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified system account
     */
    public function destroy(Request $request, $system_account)
    {
        $id = $system_account;
        $account = SystemAccount::findOrFail($id);

        try {
            // Check if account has child accounts
            if ($account->children()->count() > 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete account with child accounts.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Cannot delete account with child accounts.');
            }

            $account->delete();

            // Return JSON if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'System account deleted successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'System account deleted successfully.');

        } catch (\Exception $e) {
            // Return JSON error if requested via AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting system account: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                            ->with('error', 'Error deleting system account: ' . $e->getMessage());
        }
    }
}
