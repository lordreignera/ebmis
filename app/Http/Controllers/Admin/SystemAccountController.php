<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAccount;
use Illuminate\Http\Request;

class SystemAccountController extends Controller
{
    /**
     * Store a newly created system account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:system_accounts,code',
            'name' => 'required|string|max:255',
            'accountType' => 'nullable|string|max:100',
            'accountSubType' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'parent_account' => 'nullable|integer|exists:system_accounts,id',
            'status' => 'required|integer|in:0,1',
        ]);

        try {
            // Convert empty parent_account to null
            if (empty($validated['parent_account'])) {
                $validated['parent_account'] = null;
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
            
            $account = SystemAccount::findOrFail($id);
            
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

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:system_accounts,code,' . $id,
            'name' => 'required|string|max:255',
            'accountType' => 'nullable|string|max:100',
            'accountSubType' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'parent_account' => 'nullable|integer|exists:system_accounts,id',
            'status' => 'required|integer|in:0,1',
        ]);

        try {
            // Convert empty parent_account to null
            if (empty($validated['parent_account'])) {
                $validated['parent_account'] = null;
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
