<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoanProductController extends Controller
{
    /**
     * Show the form for creating a new loan product
     */
    public function create()
    {
        $accounts = SystemAccount::where('status', 1)->get();
        return view('admin.products.create', compact('accounts'));
    }

    /**
     * Store a newly created loan product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|integer',
            'loan_type' => 'required|integer',
            'description' => 'nullable|string|max:200',
            'max_amt' => 'required|numeric|min:0',
            'interest' => 'required|numeric|min:0|max:100',
            'period_type' => 'required|integer',
            'cash_sceurity' => 'required|numeric|min:0|max:100',
            'account' => 'required|integer|exists:system_accounts,id',
            'isactive' => 'required|integer|in:0,1',
        ]);

        try {
            // Auto-generate product code: BLN + timestamp
            $validated['code'] = 'BLN' . time();
            $validated['added_by'] = auth()->id();

            Product::create($validated);

            return redirect()->route('admin.settings.loan-products')
                            ->with('success', 'Loan product created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating loan product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified loan product
     */
    public function show(Product $product)
    {
        $product->load('addedBy');
        $account = SystemAccount::find($product->account);
        
        return view('admin.products.show', compact('product', 'account'));
    }

    /**
     * Show the form for editing the specified loan product
     */
    public function edit(Product $product)
    {
        $accounts = SystemAccount::where('status', 1)->get();
        return view('admin.products.edit', compact('product', 'accounts'));
    }

    /**
     * Update the specified loan product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|integer',
            'loan_type' => 'required|integer',
            'description' => 'nullable|string|max:200',
            'max_amt' => 'required|numeric|min:0',
            'interest' => 'required|numeric|min:0|max:100',
            'period_type' => 'required|integer',
            'cash_sceurity' => 'required|numeric|min:0|max:100',
            'account' => 'required|integer|exists:system_accounts,id',
            'isactive' => 'required|integer|in:0,1',
        ]);

        try {
            $product->update($validated);

            return redirect()->route('admin.settings.loan-products')
                            ->with('success', 'Loan product updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating loan product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified loan product
     */
    public function destroy(Product $product)
    {
        try {
            // Check if product is in use (you may want to add this check)
            $product->delete();

            return redirect()->route('admin.settings.loan-products')
                            ->with('success', 'Loan product deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->with('error', 'Error deleting loan product: ' . $e->getMessage());
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(Product $product)
    {
        $product->update([
            'isactive' => $product->isactive === 1 ? 0 : 1
        ]);

        $status = $product->isactive === 1 ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Product {$status} successfully.",
            'isactive' => $product->isactive
        ]);
    }
}
