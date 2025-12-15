<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavingsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $savingsProducts = SavingsProduct::orderBy('name')->get();
        $systemAccounts = DB::table('system_accounts')->select('id', 'name')->orderBy('name')->get();
        return view('admin.settings.savings-products', compact('savingsProducts', 'systemAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:savings_products,code',
            'name' => 'required|string|max:255',
            'interest' => 'required|numeric|min:0|max:100',
            'min_amt' => 'required|numeric|min:0',
            'max_amt' => 'required|numeric|min:0',
            'charge' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'account' => 'required|exists:system_accounts,id',
            'isactive' => 'boolean'
        ]);

        try {
            // If code is empty or not provided, generate it
            if (empty($validated['code']) || !isset($validated['code'])) {
                $validated['code'] = 'BSV' . time();
            }
            
            $validated['isactive'] = $request->has('isactive') ? 1 : 0;
            
            SavingsProduct::create($validated);

            return redirect()->back()->with('success', 'Savings product created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating savings product: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SavingsProduct $savingsProduct)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:savings_products,code,' . $savingsProduct->id,
            'name' => 'required|string|max:255',
            'interest' => 'required|numeric|min:0|max:100',
            'min_amt' => 'required|numeric|min:0',
            'max_amt' => 'required|numeric|min:0',
            'charge' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'account' => 'required|exists:system_accounts,id',
            'isactive' => 'boolean'
        ]);

        try {
            $validated['isactive'] = $request->has('isactive') ? 1 : 0;
            
            $savingsProduct->update($validated);

            return redirect()->back()->with('success', 'Savings product updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating savings product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SavingsProduct $savingsProduct)
    {
        try {
            // Check if product has any associated savings
            if ($savingsProduct->savings()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete product that has associated savings accounts. Deactivate it instead.');
            }

            $savingsProduct->delete();

            return redirect()->back()->with('success', 'Savings product deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting savings product: ' . $e->getMessage());
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(SavingsProduct $savingsProduct)
    {
        try {
            $savingsProduct->update([
                'isactive' => !$savingsProduct->isactive
            ]);

            $status = $savingsProduct->isactive ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Savings product {$status} successfully.",
                'isactive' => $savingsProduct->isactive
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product status: ' . $e->getMessage()
            ], 500);
        }
    }
}
